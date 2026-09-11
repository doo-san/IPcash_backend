<?php

namespace App\Filament\Widgets;

use App\Enums\KycVerificationStatus;
use App\Filament\Resources\AccountResource;
use App\Filament\Resources\CardResource;
use App\Filament\Resources\TransactionResource;
use App\Models\Account;
use App\Models\Card;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

// Vue d'ensemble — première rangée du tableau de bord admin, demandée par
// le produit (« nombre d'utilisateurs... des statistiques »). Chiffres
// bruts sur toute la base ; les graphiques plus bas couvrent l'évolution
// récente. Chaque carte pointe vers la page admin correspondante
// (`->url()`) — le tableau de bord doit servir de raccourci, pas
// seulement d'affichage.
class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalAccounts = Account::count();
        $verifiedAccounts = Account::where('kyc_status', KycVerificationStatus::Verified)->count();
        $totalBalance = (int) Account::sum('balance_xof');
        $totalTransactions = Transaction::count();
        $transactionsToday = Transaction::whereDate('created_at', today())->count();
        $activeCards = Card::where('status', 'active')->count();

        return [
            Stat::make('Clients', number_format($totalAccounts, 0, ',', ' '))
                ->description("{$verifiedAccounts} vérifiés (KYC)")
                ->icon('heroicon-o-users')
                ->color('success')
                ->url(AccountResource::getUrl('index')),
            Stat::make('Solde total détenu', number_format($totalBalance, 0, ',', ' ').' F')
                ->description('Somme des soldes de tous les comptes')
                ->icon('heroicon-o-banknotes')
                ->color('primary')
                ->url(AccountResource::getUrl('index')),
            Stat::make('Transactions', number_format($totalTransactions, 0, ',', ' '))
                ->description("{$transactionsToday} aujourd'hui")
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->url(TransactionResource::getUrl('index')),
            Stat::make('Cartes actives', number_format($activeCards, 0, ',', ' '))
                ->icon('heroicon-o-credit-card')
                ->color('gray')
                ->url(CardResource::getUrl('index')),
        ];
    }
}
