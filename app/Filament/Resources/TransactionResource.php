<?php

namespace App\Filament\Resources;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\InsufficientFundsException;
use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Account;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

// Ledger immuable — aucune création/édition/suppression depuis l'admin
// (chaque écriture vient toujours d'une opération API, jamais d'une saisie
// manuelle). Utile pour le support et l'audit uniquement.
class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?string $navigationGroup = 'Argent';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('account.phone_number')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (TransactionStatus $state) => match ($state) {
                        TransactionStatus::Completed => 'success',
                        TransactionStatus::Pending => 'warning',
                        TransactionStatus::Failed => 'danger',
                        TransactionStatus::Reversed => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount_xof')
                    ->label('Montant')
                    ->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F')
                    ->color(fn (int $state) => $state >= 0 ? 'success' : 'danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(array_column(TransactionType::cases(), 'value', 'value')),
                Tables\Filters\SelectFilter::make('status')
                    ->options(array_column(TransactionStatus::cases(), 'value', 'value')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('resolve')
                    ->label('Résoudre')
                    ->icon('heroicon-o-wrench')
                    ->color('warning')
                    ->visible(fn (Transaction $record) => auth()->user()->isSuperAdmin()
                        && $record->status === TransactionStatus::Pending)
                    ->form([
                        Forms\Components\Select::make('outcome')
                            ->label('Issue')
                            ->options([
                                'completed' => 'Confirmer (le montant s\'applique au solde)',
                                'failed' => 'Marquer en échec (aucun effet sur le solde)',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif (obligatoire)')
                            ->required(),
                    ])
                    ->action(function (Transaction $record, array $data) {
                        // Trois états, comme côté mobile (CLAUDE.md règle
                        // 3) : `pending` ne touche jamais le solde tant
                        // qu'elle n'est pas confirmée — donc seul le
                        // passage à `completed` applique le montant ici.
                        DB::transaction(function () use ($record, $data) {
                            if ($data['outcome'] === 'completed') {
                                $record->account->increment('balance_xof', $record->amount_xof);
                                $record->status = TransactionStatus::Completed;
                            } else {
                                $record->status = TransactionStatus::Failed;
                                $record->failure_reason = $data['reason'];
                            }
                            $record->save();
                        });

                        Notification::make()->title('Transaction résolue')->success()->send();
                    }),
                Tables\Actions\Action::make('reverse')
                    ->label('Inverser')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (Transaction $record) => auth()->user()->isSuperAdmin()
                        && $record->status === TransactionStatus::Completed)
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif (obligatoire)')
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription('Le montant est reversé au solde du client. Si ce mouvement a un pendant chez un autre compte (transfert P2P, envoi IPchange), son solde est débité en retour dans la même opération — annulée si ce compte n\'a plus assez pour ça.')
                    ->action(function (Transaction $record, array $data) {
                        try {
                            DB::transaction(function () use ($record, $data) {
                                static::reverseOne($record, $data['reason']);

                                // Un transfert P2P/IPchange crée deux écritures liées
                                // par convention de référence (`REF` chez l'émetteur,
                                // `REF-R` chez le destinataire — voir TransferController
                                // et ForeignBalanceController::send). Inverser une seule
                                // des deux laisserait l'argent dupliqué : le destinataire
                                // garderait la somme reçue tout en la recréditant à
                                // l'émetteur. On inverse donc aussi le pendant s'il existe
                                // et n'a pas déjà été traité.
                                $counterpart = static::findCounterpart($record);
                                if ($counterpart && $counterpart->status === TransactionStatus::Completed) {
                                    static::reverseOne($counterpart, $data['reason']);
                                }
                            });
                        } catch (InsufficientFundsException) {
                            Notification::make()
                                ->title('Inversion impossible')
                                ->body('Le compte en face de ce mouvement n\'a plus un solde suffisant pour être débité en retour — rien n\'a été modifié.')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()->title('Transaction inversée')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    // Annule l'effet d'une écriture sur le solde de son compte (verrou
    // pessimiste, comme `LocksRowsForUpdate` côté API — voir CLAUDE.md).
    // Générique dans les deux sens : annuler un débit (amount_xof < 0)
    // recrédite, annuler un crédit (amount_xof > 0) redébite.
    private static function reverseOne(Transaction $record, string $reason): void
    {
        /** @var Account $account */
        $account = $record->account()->lockForUpdate()->firstOrFail();
        $undoAmount = -$record->amount_xof;

        if ($undoAmount < 0 && $account->balance_xof < abs($undoAmount)) {
            throw new InsufficientFundsException;
        }

        $account->increment('balance_xof', $undoAmount);
        $record->status = TransactionStatus::Reversed;
        $record->failure_reason = $reason;
        $record->save();
    }

    private static function findCounterpart(Transaction $record): ?Transaction
    {
        if ($record->reference === null) {
            return null;
        }

        $counterpartReference = str_ends_with($record->reference, '-R')
            ? substr($record->reference, 0, -2)
            : "{$record->reference}-R";

        return Transaction::where('reference', $counterpartReference)
            ->where('id', '!=', $record->id)
            ->first();
    }
}
