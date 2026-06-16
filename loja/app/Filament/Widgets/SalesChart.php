<?php

namespace App\Filament\Widgets;

use App\Models\Quote;
use Filament\Widgets\ChartWidget;

class SalesChart extends ChartWidget
{
    protected ?string $heading = 'Faturamento Mensal (Valor Aprovado)';

    protected static ?int $sort = 2;

    protected string $color = 'success';

    protected function getData(): array
    {
        $data = Quote::where('status', 'aprovado')
            ->whereNotNull('approved_at')
            ->where('approved_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw('DATE_FORMAT(approved_at, "%Y-%m") as month, SUM(total) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $months = [];
        $values = [];

        for ($i = 11; $i >= 0; $i--) {
            $carbon = now()->subMonths($i);
            $monthKey = $carbon->format('Y-m');
            $monthLabel = ucfirst($carbon->translatedFormat('M/y'));

            $months[] = $monthLabel;
            $values[] = (float) ($data[$monthKey] ?? 0.0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Faturamento (R$)',
                    'data' => $values,
                    'fill' => 'start',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
