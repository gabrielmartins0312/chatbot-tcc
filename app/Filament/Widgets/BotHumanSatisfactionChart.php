<?php

namespace App\Filament\Widgets;

use App\Models\ConversationMetric;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BotHumanSatisfactionChart extends ChartWidget
{
    protected static ?string $heading = '⭐ Satisfação Média';

    protected function getData(): array
    {
        $stats = ConversationMetric::select(
            'mode',
            DB::raw('AVG(COALESCE(satisfaction,0)) as avg_satisfaction')
        )
        ->groupBy('mode')
        ->get()
        ->keyBy('mode');

        return [
            'datasets' => [
                [
                    'label' => 'Bot',
                    'data' => [$stats['bot']->avg_satisfaction ?? 0],
                    'backgroundColor' => 'rgba(54,162,235,0.7)',
                ],
                [
                    'label' => 'Humano',
                    'data' => [$stats['humano']->avg_satisfaction ?? 0],
                    'backgroundColor' => 'rgba(255,99,132,0.7)',
                ],
            ],
            'labels' => ['Satisfação'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
