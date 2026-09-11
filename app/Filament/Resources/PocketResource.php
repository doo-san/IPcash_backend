<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PocketResource\Pages;
use App\Models\Pocket;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Miroir de `Pocket` côté Flutter — sous-comptes d'épargne. Lecture seule
// comme `ForeignBalance`/`Transaction` (solde réel en XOF), avec une action
// « Débloquer » dédiée plutôt qu'un champ `locked_until` éditable en libre.
class PocketResource extends Resource
{
    protected static ?string $model = Pocket::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'Poches d\'épargne';

    protected static ?string $navigationGroup = 'Argent';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account.phone_number')->label('Client')->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable(),
                Tables\Columns\TextColumn::make('balance_xof')
                    ->label('Solde')
                    ->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_locked')
                    ->label('Bloquée')
                    ->boolean()
                    ->state(fn (Pocket $record) => $record->isLocked()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('unlock')
                    ->label('Débloquer')
                    ->icon('heroicon-o-lock-open')
                    ->color('warning')
                    ->visible(fn (Pocket $record) => $record->isLocked())
                    ->requiresConfirmation()
                    ->action(function (Pocket $record) {
                        $record->locked_until = null;
                        $record->save();
                        Notification::make()->title('Poche débloquée')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPockets::route('/'),
            'view' => Pages\ViewPocket::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
