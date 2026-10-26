<?php

namespace App\Filament\Resources\AccountResource\Widgets;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use App\Services\Accounts\AccountClosureBlockedException;
use App\Services\Accounts\AccountClosureService;
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

    // Circuit normal (identique à la demande du client depuis l'app) :
    // accès coupé, données légales conservées puis purgées à échéance.
    public function closeAction(): Action
    {
        return Action::make('close')
            ->label('Clôturer le compte')
            ->icon('heroicon-o-archive-box-x-mark')
            ->color('danger')
            ->visible(fn () => auth()->user()->isSuperAdmin() && ! $this->record->isClosed())
            ->requiresConfirmation()
            ->modalHeading('Clôturer le compte')
            ->modalDescription('Le client perd immédiatement tout accès et son numéro est libéré. Identité, pièces KYC, transactions et contrats sont conservés '.config('accounts.closed_retention_years').' ans (obligation légale) puis supprimés automatiquement.')
            ->action(function (AccountClosureService $closure) {
                try {
                    $closure->close($this->record);
                } catch (AccountClosureBlockedException $e) {
                    Notification::make()
                        ->title('Clôture impossible')
                        ->body(self::describeBlockers($e->blockers))
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()->title('Compte clôturé')->success()->send();
                $this->record->refresh();
            });
    }

    // Réservée aux comptes de test : efface aussi les données que la
    // réglementation impose de conserver pour un vrai client.
    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label('Supprimer définitivement')
            ->icon('heroicon-o-trash')
            ->color('gray')
            ->visible(fn () => auth()->user()->isSuperAdmin())
            ->requiresConfirmation()
            ->modalHeading('Supprimer définitivement')
            ->modalDescription('Réservé aux comptes de test. Efface sans retour le client, son historique, ses pièces KYC et ses attestations — données qu\'un vrai client clôturé doit conserver '.config('accounts.closed_retention_years').' ans. Pour un vrai client, utilisez « Clôturer le compte ».')
            ->action(function (AccountClosureService $closure) {
                $blockers = array_values(array_intersect($closure->blockers($this->record), [
                    AccountClosureService::MAIN_BALANCE,
                    AccountClosureService::POCKET_BALANCE,
                    AccountClosureService::CARD_BALANCE,
                    AccountClosureService::EPHEMERAL_CARD_BALANCE,
                    AccountClosureService::FOREIGN_BALANCE,
                ]));
                if ($blockers !== []) {
                    Notification::make()
                        ->title('Suppression impossible')
                        ->body(self::describeBlockers($blockers))
                        ->danger()
                        ->send();

                    return;
                }

                $closure->purge($this->record);

                Notification::make()->title('Client supprimé définitivement')->success()->send();
                $this->redirect(AccountResource::getUrl('index'));
            });
    }

    /** @param list<string> $blockers */
    private static function describeBlockers(array $blockers): string
    {
        $labels = [
            AccountClosureService::MAIN_BALANCE => 'solde principal non nul',
            AccountClosureService::POCKET_BALANCE => 'argent sur une poche',
            AccountClosureService::CARD_BALANCE => 'argent sur une carte',
            AccountClosureService::EPHEMERAL_CARD_BALANCE => 'argent sur la carte éphémère',
            AccountClosureService::FOREIGN_BALANCE => 'argent sur un sous-compte devise',
            AccountClosureService::PENDING_TRANSACTION => 'opération en cours',
            AccountClosureService::PENDING_INSURANCE => 'contrat d\'assurance en attente de validation',
        ];

        return 'À régler d\'abord : '.implode(', ', array_map(fn ($b) => $labels[$b] ?? $b, $blockers)).'.';
    }
}
