<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        // 1. Orçamentos no Mês
        $quotesThisMonth = Quote::where('type', 'orcamento')
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->count();
        $quotesLastMonth = Quote::where('type', 'orcamento')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();
        $quotesTrend = $this->getTrendData($quotesThisMonth, $quotesLastMonth);

        // 2. Pedidos no Mês
        $ordersThisMonth = Quote::where('type', 'pedido')
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->count();
        $ordersLastMonth = Quote::where('type', 'pedido')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();
        $ordersTrend = $this->getTrendData($ordersThisMonth, $ordersLastMonth);

        // 3. Valor Aprovado no Mês (Faturamento)
        $approvedThisMonthValue = (float) Quote::where('status', 'aprovado')
            ->whereBetween('approved_at', [$currentMonthStart, $currentMonthEnd])
            ->sum('total');
        $approvedLastMonthValue = (float) Quote::where('status', 'aprovado')
            ->whereBetween('approved_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('total');
        $valueTrend = $this->getTrendData($approvedThisMonthValue, $approvedLastMonthValue);

        // 4. Aguardando Resposta (Total acumulado de orçamentos pendentes de retorno)
        $waitingResponseCount = Quote::where('type', 'orcamento')
            ->whereIn('status', ['enviado', 'visualizado'])
            ->count();

        return [
            Stat::make('Orçamentos no mês', $quotesThisMonth)
                ->description($quotesTrend['description'])
                ->descriptionIcon($quotesTrend['icon'])
                ->color($quotesTrend['color']),

            Stat::make('Pedidos no mês', $ordersThisMonth)
                ->description($ordersTrend['description'])
                ->descriptionIcon($ordersTrend['icon'])
                ->color($ordersTrend['color']),

            Stat::make('Valor Aprovado no mês', 'R$ ' . number_format($approvedThisMonthValue, 2, ',', '.'))
                ->description($valueTrend['description'])
                ->descriptionIcon($valueTrend['icon'])
                ->color($valueTrend['color']),

            Stat::make('Aguardando Resposta', $waitingResponseCount)
                ->description('Orçamentos em aberto / follow-up')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }

    private function getTrendData($current, $previous): array
    {
        if ($previous == 0) {
            return [
                'description' => $current > 0 ? '+100% vs mês anterior' : 'Sem alteração',
                'icon' => $current > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus',
                'color' => $current > 0 ? 'success' : 'gray',
            ];
        }

        $percent = (($current - $previous) / $previous) * 100;
        $formatted = number_format(abs($percent), 1, ',', '.');

        if ($percent > 0) {
            return [
                'description' => "+{$formatted}% vs mês anterior",
                'icon' => 'heroicon-m-arrow-trending-up',
                'color' => 'success',
            ];
        } elseif ($percent < 0) {
            return [
                'description' => "-{$formatted}% vs mês anterior",
                'icon' => 'heroicon-m-arrow-trending-down',
                'color' => 'danger',
            ];
        }

        return [
            'description' => 'Sem alteração vs mês anterior',
            'icon' => 'heroicon-m-minus',
            'color' => 'gray',
        ];
    }
}
