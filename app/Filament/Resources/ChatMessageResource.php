<?php

namespace App\Filament\Resources;

use App\Enums\ChatRole;
use App\Filament\Resources\ChatMessageResource\Pages;
use App\Models\ChatMessage;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Historique de l'assistant support — pas de saisie libre d'une nouvelle
// conversation (`canCreate() = false`), mais un staff peut répondre dans
// un fil existant (action « Répondre », toujours rattachée au même
// compte que le message cliqué). Suppression possible au cas par cas pour
// une purge à la demande d'un client.
class ChatMessageResource extends Resource
{
    protected static ?string $model = ChatMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Historique support';

    protected static ?string $navigationGroup = 'Clients';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('account.phone_number')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Rôle')
                    ->badge()
                    ->color(fn (ChatRole $state) => $state === ChatRole::Assistant ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('text')
                    ->label('Message')
                    ->limit(80)
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('reply')
                    ->label('Répondre')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('primary')
                    ->form([
                        Forms\Components\Textarea::make('text')
                            ->label('Réponse')
                            ->required(),
                    ])
                    ->action(function (ChatMessage $record, array $data) {
                        $record->account->chatMessages()->create([
                            'role' => ChatRole::Assistant,
                            'text' => $data['text'],
                        ]);

                        Notification::make()->title('Réponse envoyée')->success()->send();
                    }),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListChatMessages::route('/'),
            'view' => Pages\ViewChatMessage::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
