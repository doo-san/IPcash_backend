<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChatbotAnswerResource\Pages;
use App\Models\ChatbotAnswer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Remplace la base de connaissances mot-clé → réponse codée en dur dans
// `support_chat_fake_data_source.dart` — chaque réponse se déclenche
// quand un des mots-clés apparaît dans le message du client.
class ChatbotAnswerResource extends Resource
{
    protected static ?string $model = ChatbotAnswer::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'Chatbot support';

    protected static ?string $navigationGroup = 'Contenu';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('locale')
                ->label('Langue')
                ->options(['fr' => 'Français', 'en' => 'English'])
                ->default('fr')
                ->required(),
            Forms\Components\TagsInput::make('keywords')
                ->label('Mots-clés déclencheurs')
                ->helperText('La réponse s\'affiche si un de ces mots apparaît dans le message du client.')
                ->required(fn (Forms\Get $get) => ! $get('is_fallback'))
                ->columnSpanFull(),
            Forms\Components\Textarea::make('answer')
                ->label('Réponse')
                ->required()
                ->columnSpanFull(),
            Forms\Components\Toggle::make('is_fallback')
                ->label('Réponse par défaut')
                ->helperText('Utilisée quand aucun mot-clé ne correspond — une seule devrait l\'être.')
                ->live(),
            Forms\Components\Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('locale')->label('Langue'),
                Tables\Columns\TextColumn::make('keywords')
                    ->label('Mots-clés')
                    ->state(fn (ChatbotAnswer $record) => implode(', ', $record->keywords))
                    ->searchable(query: fn ($query, string $search) => $query->where('keywords', 'like', "%{$search}%")),
                Tables\Columns\TextColumn::make('answer')->label('Réponse')->limit(60)->searchable(),
                Tables\Columns\IconColumn::make('is_fallback')->label('Par défaut')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('Actif')->boolean(),
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
            'index' => Pages\ListChatbotAnswers::route('/'),
            'create' => Pages\CreateChatbotAnswer::route('/create'),
            'edit' => Pages\EditChatbotAnswer::route('/{record}/edit'),
        ];
    }
}
