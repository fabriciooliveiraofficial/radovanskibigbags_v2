<?php

namespace App\Filament\Widgets;

use App\Models\Quote;
use Filament\Widgets\ChartWidget;

class QuotesStatusChart extends ChartWidget
{
    protected ?string $heading = 'Funil de Status (Orçamentos do Mês)';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();

        $data = Quote::where('type', 'orcamento')
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statuses = [
            'rascunho'    => ['label' => 'Rascunho', 'color' => '#9CA3AF'],
            'enviado'     => ['label' => 'Enviado', 'color' => '#3B82F6'],
            'visualizado' => ['label' => 'Visualizado', 'color' => '#F59E0B'],
            'aprovado'    => ['label' => 'Aprovado', 'color' => '#10B981'],
            'recusado'    => ['label' => 'Recusado', 'color' => '#EF4444'],
            'expirado'    => ['label' => 'Expirado', 'color' => '#6B7280'],
        ];

        $labels = [];
        $values = [];
        $backgroundColor = [];

        foreach ($statuses as $statusKey => $config) {
            $count = $data[$statusKey] ?? 0;
            // Display status if there is at least one quote in that status or to have a clean funnel view
            $labels[] = $config['label'];
            $values[] = $count;
            $backgroundColor[] = $config['color'];
        }

        // If all values are 0, return a placeholder to avoid empty chart display errors
        if (array_sum($values) === 0) {
            return [
                'datasets' => [
                    [
                        'label' => 'Orçamentos',
                        'data' => [1],
                        'backgroundColor' => ['#E5E7EB'],
                    ],
                ],
                'labels' => ['Nenhum orçamento este mês'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Orçamentos',
                    'data' => $values,
                    'backgroundColor' => $backgroundColor,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
