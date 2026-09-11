<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use App\Enums\CardStatus;
use App\Enums\TransactionType;
use App\Models\Card;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Miroir de `CardResource` (geler/dégeler/supprimer), directement sur la
// fiche client — exigence produit : « avoir... ses cartes » depuis
// « Comptes clients ». Mêmes garanties : « Supprimer » reverse toujours
// le solde restant avant de retirer la carte, jamais d'argent perdu.
class CardsRelationManager extends RelationManager
{
    protected static string $relationship = 'cards';

    protected static ?string $title = 'Cartes';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('last4')
            ->columns([
                Tables\Columns\TextColumn::make('last4')
                    ->label('4 derniers chiffres')
                    ->formatStateUsing(fn (string $state) => "•••• {$state}"),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (CardStatus $state) => $state === CardStatus::Active ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('expiry')
                    ->label('Expire')
                    ->state(fn (Card $record) => sprintf('%02d/%d', $record->expiry_month, $record->expiry_year)),
                Tables\Columns\TextColumn::make('balance_xof')
                    ->label('Solde')
                    ->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F'),
                Tables\Columns\TextColumn::make('daily_limit_xof')
                    ->label('Plafond/jour')
                    ->formatStateUsing(fn (?int $state) => $state ? number_format($state, 0, ',', ' ').' F' : '—'),
            ])
            ->headerActions([])
            ->actions([
                // `Tables\Actions\EditAction` plutôt qu'une action
                // personnalisée serait plus court, mais Filament la masque
                // silencieusement dans un RelationManager tant qu'aucune
                // Policy Laravel n'existe pour le modèle (aucune n'existe
                // dans ce projet) — piège déjà rencontré une fois. Une
                // action personnalisée avec son propre formulaire évite le
                // problème, comme geler/dégeler/supprimer ci-dessous.
                Tables\Actions\Action::make('editLimits')
                    ->label('Plafonds')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        Forms\Components\TextInput::make('daily_limit_xof')
                            ->label('Plafond journalier (XOF)')
                            ->numeric(),
                        Forms\Components\TextInput::make('monthly_limit_xof')
                            ->label('Plafond mensuel (XOF)')
                            ->numeric(),
                    ])
                    ->fillForm(fn (Card $record) => [
                        'daily_limit_xof' => $record->daily_limit_xof,
                        'monthly_limit_xof' => $record->monthly_limit_xof,
                    ])
                    ->action(function (Card $record, array $data) {
                        $record->update($data);
                        Notification::make()->title('Plafonds mis à jour')->success()->send();
                    }),
                Tables\Actions\Action::make('freeze')
                    ->label('Geler')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (Card $record) => $record->status === CardStatus::Active)
                    ->requiresConfirmation()
                    ->action(function (Card $record) {
                        $record->status = CardStatus::Frozen;
                        $record->save();
                        Notification::make()->title('Carte gelée')->success()->send();
                    }),
                Tables\Actions\Action::make('unfreeze')
                    ->label('Dégeler')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (Card $record) => $record->status === CardStatus::Frozen)
                    ->requiresConfirmation()
                    ->action(function (Card $record) {
                        $record->status = CardStatus::Active;
                        $record->save();
                        Notification::make()->title('Carte dégelée')->success()->send();
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Supprimer')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer la carte')
                    ->modalDescription('Le solde restant est reversé sur le compte principal du client. Cette suppression est définitive.')
                    ->action(function (Card $record) {
                        DB::transaction(function () use ($record) {
                            $sweptAmount = $record->balance_xof;

                            if ($sweptAmount > 0) {
                                $record->account->increment('balance_xof', $sweptAmount);
                                $record->account->transactions()->create([
                                    'type' => TransactionType::CardWithdrawal,
                                    'status' => 'completed',
                                    'amount_xof' => $sweptAmount,
                                    'reference' => 'CARDDEL-'.strtoupper(Str::random(10)),
                                ]);
                            }

                            $record->delete();
                        });

                        Notification::make()->title('Carte supprimée')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }
}
