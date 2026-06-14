<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();

        $quotesThisMonth = Quote::where('created_at', '>=', $monthStart)->count();
        $approvedThisMonth = Quote::where('status', 'aprovado')
            ->where('approved_at', '>=', $monthStart)
            ->count();
        $approvedValue = (float) Quote::where('status', 'aprovado')
            ->where('approved_at', '>=', $monthStart)
            ->sum('total');

        $conversionRate = $quotesThisMonth > 0
            ? round(($approvedThisMonth / $quotesThisMonth) * 100)
            : 0;

        return [
            Stat::make('Orçamentos no mês', $quotesThisMonth)
                ->description($approvedThisMonth.' aprovados ('.$conversionRate.'%)')
                ->color('primary'),
            Stat::make('Valor aprovado no mês', 'R$ '.number_format($approvedValue, 2, ',', '.'))
                ->color('success'),
            Stat::make('Aguardando resposta', Quote::whereIn('status', ['enviado', 'visualizado'])->count())
                ->description('Orçamentos enviados sem aprovação')
                ->color('warning'),
            Stat::make('Produtos ativos', Product::where('is_active', true)->count())
                ->description(Customer::count().' clientes cadastrados')
                ->color('gray'),
        ];
    }
}
