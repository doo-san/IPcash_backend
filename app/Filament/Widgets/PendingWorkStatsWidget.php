<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessageResource;
use App\Filament\Resources\KycDocumentResource;
use App\Filament\Resources\TransactionResource;
use App\Models\ContactMessage;
use App\Models\KycDocument;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

// « À traiter » : ce qui attend une action du staff, en tête du tableau de
// bord. Chaque carte mène à la liste concernée.
class PendingWorkStatsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $pendingKyc = KycDocument::whereIn('status', ['pending', 'review'])->count();
        $pendingTransactions = Transaction::where('status', 'pending')->count();
        $unreadMessages = ContactMessage::whereNull('read_at')->count();

        return [
            Stat::make('KYC à examiner', $pendingKyc)
                ->icon('heroicon-o-identification')
                ->color($pendingKyc > 0 ? 'warning' : 'success')
                ->url(KycDocumentResource::getUrl('index')),
            Stat::make('Transactions en attente', $pendingTransactions)
                ->icon('heroicon-o-clock')
                ->color($pendingTransactions > 0 ? 'warning' : 'success')
                ->url(TransactionResource::getUrl('index')),
            Stat::make('Messages non lus', $unreadMessages)
                ->icon('heroicon-o-envelope')
                ->color($unreadMessages > 0 ? 'warning' : 'success')
                ->url(ContactMessageResource::getUrl('index')),
        ];
    }
}
