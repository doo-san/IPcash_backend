<?php

namespace App\Filament\Resources\CardResource\Pages;

use App\Enums\CardStatus;
use App\Enums\TransactionType;
use App\Filament\Resources\CardResource;
use App\Models\Card;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Le corps de la page reste le formulaire des plafonds (voir
// CardResource::form). Les actions sur la carte vivent toutes ici, en
// en-tête — pas sur la liste, qui ne fait qu'afficher les informations.
class EditCard extends EditRecord
{
    protected static string $resource = CardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('freeze')
                ->label('Geler')
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->visible(fn (Card $record) => $record->status === CardStatus::Active)
                ->requiresConfirmation()
                ->action(function (Card $record) {
                    $record->status = CardStatus::Frozen;
                    $record->save();
                    Notification::make()->title('Carte gelée')->success()->send();
                }),
            Actions\Action::make('unfreeze')
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
            Actions\Action::make('block')
                ->label('Bloquer')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn (Card $record) => $record->status !== CardStatus::Blocked)
                ->requiresConfirmation()
                ->modalHeading('Bloquer la carte')
                ->modalDescription('Contrairement au gel, le client ne pourra pas lever ce blocage lui-même : seul le staff pourra débloquer la carte.')
                ->action(function (Card $record) {
                    $record->status = CardStatus::Blocked;
                    $record->save();
                    Notification::make()->title('Carte bloquée')->success()->send();
                }),
            Actions\Action::make('unblock')
                ->label('Débloquer')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->visible(fn (Card $record) => $record->status === CardStatus::Blocked)
                ->requiresConfirmation()
                ->action(function (Card $record) {
                    $record->status = CardStatus::Active;
                    $record->save();
                    Notification::make()->title('Carte débloquée')->success()->send();
                }),
            // Nom volontairement `deleteCard` et non `cancel`/`delete` :
            // ces deux-là entrent en collision avec des actions internes
            // de Filament sur une page `EditRecord` (bouton d'annulation de
            // modale, action de suppression standard).
            Actions\Action::make('deleteCard')
                ->label('Supprimer')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Supprimer la carte')
                ->modalDescription('Le solde restant est reversé sur le compte principal du client. Cette suppression est définitive.')
                ->action(function (Card $record) {
                    // Même logique que la suppression côté mobile
                    // (`DELETE /cards/{id}`) : le solde n'est jamais perdu,
                    // toujours reversé — et tracé (voir
                    // TransactionType::CardWithdrawal, `LogsAdminActivity`
                    // pour la suppression elle-même).
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

                    $this->redirect(CardResource::getUrl('index'));
                }),
        ];
    }
}
