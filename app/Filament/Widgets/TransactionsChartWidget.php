<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TransactionResource;
use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

// Diagramme d'utilisation — volume de transactions sur les 30 derniers
// jours, demandé pour le tableau de bord admin.
class TransactionsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Transactions (30 derniers jours)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    // Lien vers la liste complète — `getDescription()` accepte un
    // `Htmlable` (comme `getHeading()`), rendu tel quel par le composant
    // section de Filament plutôt qu'échappé en texte brut.
    public function getDescription(): string|HtmlString|null
    {
        return new HtmlString(Blade::render(
            '<x-filament::link :href="$href" icon="heroicon-o-arrow-right" icon-position="after" size="sm">Voir toutes les transactions</x-filament::link>',
            ['href' => TransactionResource::getUrl('index')],
        ));
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn (int $daysAgo) => today()->subDays($daysAgo));

        $counts = Transaction::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->where('created_at', '>=', today()->subDays(29))
            ->groupBy('day')
            ->pluck('total', 'day');

        return [
            'datasets' => [
                [
                    'label' => 'Transactions',
                    'data' => $days->map(fn (Carbon $day) => (int) ($counts[$day->toDateString()] ?? 0))->all(),
                    'borderColor' => '#00A05B',
                    'backgroundColor' => 'rgba(0, 160, 91, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $days->map(fn (Carbon $day) => $day->format('d/m'))->all(),
        ];
    }
}
