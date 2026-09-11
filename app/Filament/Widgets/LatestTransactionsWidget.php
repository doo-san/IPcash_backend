<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Filament\Resources\TransactionResource;
use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

// Dernières transactions tous clients confondus — aperçu rapide sur le
// tableau de bord sans ouvrir la ressource « Transactions ». Chaque ligne
// mène directement à la fiche de la transaction (`->recordUrl()`).
class LatestTransactionsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Transaction::query()->latest())
            ->heading('Dernières transactions')
            ->recordUrl(fn (Transaction $record) => TransactionResource::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('account.phone_number')
                    ->label('Client'),
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
                    ->color(fn (int $state) => $state >= 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->copyable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->paginated([5, 10, 25]);
    }
}
