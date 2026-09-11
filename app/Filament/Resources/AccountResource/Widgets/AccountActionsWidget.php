<?php

namespace App\Filament\Resources\AccountResource\Widgets;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

// Widget de pied de page de la fiche client (`ViewAccount::getFooterWidgets()`)
// — rendu après la liste de transactions et les autres onglets, comme
// demandé (« en bas après la partie transaction »). Reprend exactement les
// actions de déverrouillage/blocage/réinitialisation/suppression qui
// vivaient jusqu'ici en actions de ligne sur « Comptes clients » (voir
// AccountResource::table(), désormais réduite à Voir/Modifier — demande
// explicite du produit) : mêmes noms d'action et même logique, seul
// l'emplacement change.
class AccountActionsWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?Account $record = null;

    protected static string $view = 'filament.resources.account-resource.widgets.account-actions-widget';

    protected int|string|array $columnSpan = 'full';

    public function unlockAction(): Action
    {
        return Action::make('unlock')
            ->label('Déverrouiller')
            ->icon('heroicon-o-lock-open')
            ->color('warning')
            ->visible(fn () => $this->record->isLocked())
            ->requiresConfirmation()
            ->action(function () {
                $this->record->failed_pin_attempts = 0;
                $this->record->locked_until = null;
                $this->record->save();

                Notification::make()->title('Compte déverrouillé')->success()->send();
            });
    }

    public function blockTemporarilyAction(): Action
    {
        return Action::make('blockTemporarily')
            ->label('Bloquer temporairement')
            ->icon('heroicon-o-clock')
            ->color('danger')
            ->visible(fn () => ! $this->record->isBlocked())
            ->form([
                Forms\Components\DateTimePicker::make('blocked_until')
                    ->label('Bloqué jusqu\'au')
                    ->native(false)
                    ->minDate(now())
                    ->required(),
                Forms\Components\Textarea::make('block_reason')
                    ->label('Motif')
                    ->required(),
            ])
            ->action(function (array $data) {
                $this->record->blocked_at = null;
                $this->record->blocked_until = $data['blocked_until'];
                $this->record->block_reason = $data['block_reason'];
                $this->record->save();

                Notification::make()->title('Compte bloqué temporairement')->success()->send();
            });
    }

    public function blockPermanentlyAction(): Action
    {
        return Action::make('blockPermanently')
            ->label('Bloquer définitivement')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->visible(fn () => ! $this->record->isBlocked())
            ->requiresConfirmation()
            ->modalHeading('Bloquer définitivement ce client')
            ->modalDescription('Coupe tout accès à son compte (connexion et sessions déjà ouvertes comprises) jusqu\'à un déblocage manuel explicite.')
            ->form([
                Forms\Components\Textarea::make('block_reason')
                    ->label('Motif')
                    ->required(),
            ])
            ->action(function (array $data) {
                $this->record->blocked_at = now();
                $this->record->blocked_until = null;
                $this->record->block_reason = $data['block_reason'];
                $this->record->save();

                Notification::make()->title('Compte bloqué définitivement')->success()->send();
            });
    }

    public function unblockAction(): Action
    {
        return Action::make('unblock')
            ->label('Débloquer')
            ->icon('heroicon-o-lock-open')
            ->color('success')
            ->visible(fn () => $this->record->isBlocked())
            ->requiresConfirmation()
            ->action(function () {
                $this->record->blocked_at = null;
                $this->record->blocked_until = null;
                $this->record->block_reason = null;
                $this->record->save();

                Notification::make()->title('Compte débloqué')->success()->send();
            });
    }

    public function resetPinAction(): Action
    {
        return Action::make('resetPin')
            ->label('Réinitialiser le code secret')
            ->icon('heroicon-o-key')
            ->color('warning')
            ->visible(fn () => $this->record->pin_hash !== null)
            ->requiresConfirmation()
            ->modalHeading('Réinitialiser le code secret')
            ->modalDescription("L'admin ne connaît jamais le code secret d'un client et ne peut donc jamais en définir un nouveau à sa place : cette action l'efface simplement. Au prochain lancement, le client redevient « nouveau » côté connexion et doit repasser par téléphone → OTP → nouveau code secret. Les sessions déjà ouvertes (voir « Sessions actives ») ne sont pas révoquées automatiquement.")
            ->action(function () {
                $this->record->pin_hash = null;
                $this->record->failed_pin_attempts = 0;
                $this->record->locked_until = null;
                $this->record->save();

                Notification::make()->title('Code secret réinitialisé')->success()->send();
            });
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label('Supprimer le client')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Supprimer le client')
            ->modalDescription('Cette action supprime définitivement le client et tout son historique (transactions, KYC, cartes, poches, sessions…). Irréversible.')
            ->action(function () {
                $hasFunds = $this->record->balance_xof !== 0
                    || $this->record->cards()->where('balance_xof', '!=', 0)->exists()
                    || $this->record->pockets()->where('balance_xof', '!=', 0)->exists()
                    || $this->record->foreignBalances()->where('amount_minor_units', '!=', 0)->exists();

                if ($hasFunds) {
                    Notification::make()
                        ->title('Suppression impossible')
                        ->body('Ce client détient encore des fonds (solde principal, carte, poche ou sous-compte devise). Le solde doit revenir à zéro avant de pouvoir supprimer le compte.')
                        ->danger()
                        ->send();

                    return;
                }

                $this->record->delete();

                Notification::make()->title('Client supprimé')->success()->send();

                // Contrairement à une action de ligne sur la liste (qui y
                // reste), on est ici sur la fiche du client qu'on vient de
                // supprimer — plus rien à afficher, retour à la liste.
                $this->redirect(AccountResource::getUrl('index'));
            });
    }
}
