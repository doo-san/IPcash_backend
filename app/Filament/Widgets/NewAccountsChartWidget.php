<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

// Diagramme d'utilisation — nouveaux clients inscrits sur les 30 derniers
// jours, demandé pour le tableau de bord admin.
class NewAccountsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Nouveaux clients (30 derniers jours)';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    // Lien vers la liste complète — voir le même choix dans
    // `TransactionsChartWidget::getDescription()`.
    public function getDescription(): string|HtmlString|null
    {
        return new HtmlString(Blade::render(
            '<x-filament::link :href="$href" icon="heroicon-o-arrow-right" icon-position="after" size="sm">Voir tous les clients</x-filament::link>',
            ['href' => AccountResource::getUrl('index')],
        ));
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn (int $daysAgo) => today()->subDays($daysAgo));

        $counts = Account::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->where('created_at', '>=', today()->subDays(29))
            ->groupBy('day')
            ->pluck('total', 'day');

        return [
            'datasets' => [
                [
                    'label' => 'Nouveaux clients',
                    'data' => $days->map(fn (Carbon $day) => (int) ($counts[$day->toDateString()] ?? 0))->all(),
                    'borderColor' => '#2563EB',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $days->map(fn (Carbon $day) => $day->format('d/m'))->all(),
        ];
    }
}
