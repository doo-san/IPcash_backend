<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Messages soumis via le formulaire de contact public (voir
// ContactController) — jamais créés depuis l'admin, uniquement consultés.
class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Messages de contact';

    protected static ?string $navigationGroup = 'Contenu';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nom')->disabled(),
            Forms\Components\TextInput::make('email')->label('E-mail')->disabled(),
            Forms\Components\TextInput::make('subject')->label('Sujet')->disabled(),
            Forms\Components\Textarea::make('message')->label('Message')->disabled()->rows(6)->columnSpanFull(),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make()->columns(3)->schema([
                TextEntry::make('name')->label('Nom'),
                TextEntry::make('email')->label('E-mail'),
                TextEntry::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i'),
                TextEntry::make('subject')->label('Sujet')->columnSpanFull(),
                TextEntry::make('message')->label('Message')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('read_at')
                    ->label('Lu')
                    ->boolean()
                    ->state(fn (ContactMessage $record) => $record->read_at !== null),
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('E-mail')->searchable(),
                Tables\Columns\TextColumn::make('subject')->label('Sujet')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->after(function (ContactMessage $record) {
                        if ($record->read_at === null) {
                            $record->update(['read_at' => now()]);
                        }
                    }),
                Tables\Actions\Action::make('delete')
                    ->label('Supprimer')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (ContactMessage $record) => $record->delete()),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
            'view' => Pages\ViewContactMessage::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $unread = static::getModel()::whereNull('read_at')->count();

        return $unread > 0 ? (string) $unread : null;
    }
}
