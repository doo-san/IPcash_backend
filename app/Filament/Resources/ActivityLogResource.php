<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

// Journal d'audit — exigence produit : « toute modification doit avoir une
// traçabilité ». Alimenté automatiquement par `LogsAdminActivity` (voir
// app/Models/Concerns/LogsAdminActivity.php) sur chaque création/
// modification/suppression effectuée depuis ce panneau admin — jamais
// depuis l'API mobile. Ressource strictement en lecture seule : un journal
// d'audit qui peut être modifié ou effacé par ceux qu'il est censé
// surveiller ne prouve plus rien.
class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = "Journal d'audit";

    protected static ?string $navigationGroup = 'Audit';

    protected static ?string $modelLabel = "entrée d'audit";

    protected static ?string $pluralModelLabel = "Journal d'audit";

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Quand')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Qui')
                    ->state(fn (Activity $record) => $record->causer?->name ?? $record->causer?->phone_number ?? 'Système')
                    ->searchable(),
                Tables\Columns\TextColumn::make('event')
                    ->label('Action')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Sur quoi')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—'),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('Référence')
                    ->limit(12)
                    ->copyable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('Action')
                    ->options([
                        'created' => 'Création',
                        'updated' => 'Modification',
                        'deleted' => 'Suppression',
                    ]),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Ressource')
                    ->options(fn () => Activity::query()
                        ->whereNotNull('subject_type')
                        ->distinct()
                        ->pluck('subject_type', 'subject_type')
                        ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                        ->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            TextEntry::make('created_at')->label('Quand')->dateTime('d/m/Y H:i:s'),
            TextEntry::make('causer.name')
                ->label('Qui')
                ->state(fn (Activity $record) => $record->causer?->name ?? $record->causer?->phone_number ?? 'Système'),
            TextEntry::make('event')->label('Action'),
            TextEntry::make('subject_type')->label('Ressource')->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—'),
            TextEntry::make('subject_id')->label('Référence')->copyable(),
            KeyValueEntry::make('properties.attributes')->label('État / changements')->columnSpanFull(),
            KeyValueEntry::make('properties.old')->label('Valeurs précédentes')->columnSpanFull()
                ->visible(fn (Activity $record) => filled($record->properties['old'] ?? null)),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
