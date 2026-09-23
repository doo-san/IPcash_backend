<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqEntryResource\Pages;
use App\Models\FaqEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Base de connaissance du chatbot support IA (voir
// AiSupportChatService::systemPrompt, qui injecte ces entrées dans le
// prompt système de Claude) — le support doit pouvoir corriger une
// réponse (ex. le montant d'un plafond) sans passer par une release
// mobile ni redéployer le backend. N'est plus affichée telle quelle
// comme liste FAQ côté app (retirée du client, voir `SupportConfig` côté
// Flutter) — reste consommée uniquement via le chat.
class FaqEntryResource extends Resource
{
    protected static ?string $model = FaqEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = 'FAQ';

    protected static ?string $navigationGroup = 'Contenu';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('locale')
                ->label('Langue')
                ->options(['fr' => 'Français', 'en' => 'English'])
                ->default('fr')
                ->required(),
            Forms\Components\TextInput::make('question')
                ->label('Question')
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('answer')
                ->label('Réponse')
                ->required()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('position')
                ->label('Ordre d\'affichage')
                ->numeric()
                ->default(0),
            Forms\Components\Toggle::make('is_published')
                ->label('Publiée')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                Tables\Columns\TextColumn::make('locale')->label('Langue'),
                Tables\Columns\TextColumn::make('question')->label('Question')->limit(60)->searchable(),
                Tables\Columns\TextColumn::make('position')->label('Ordre')->sortable(),
                Tables\Columns\IconColumn::make('is_published')->label('Publiée')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('locale')
                    ->options(['fr' => 'Français', 'en' => 'English']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaqEntries::route('/'),
            'create' => Pages\CreateFaqEntry::route('/create'),
            'edit' => Pages\EditFaqEntry::route('/{record}/edit'),
        ];
    }
}
