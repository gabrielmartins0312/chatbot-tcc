<?php

namespace App\Filament\Widgets;

use App\Models\ConversationMetric;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MetricsChart extends ChartWidget
{
    protected static ?string $heading = '📊 Comparativo Bot vs Humano';

    protected function getData(): array
    {
        $stats = ConversationMetric::select(
            'mode',
            DB::raw('AVG(avg_response_time_ms) / 1000 as avg_response'),
            DB::raw('AVG(total_duration_ms) / 1000 as avg_duration'),
            DB::raw('AVG(COALESCE(satisfaction, 0)) as avg_satisfaction')            
        )->groupBy('mode')->get()->keyBy('mode');

        return [
            'datasets' => [
                [
                    'label' => 'Bot',
                    'data' => [
                        $stats['bot']->avg_response ?? 0,
                        $stats['bot']->avg_duration ?? 0,
                        $stats['bot']->avg_satisfaction ?? 0,
                    ],
                    'backgroundColor' => 'rgba(54,162,235,0.7)',
                ],
                [
                    'label' => 'Humano',
                    'data' => [
                        $stats['humano']->avg_response ?? 0,
                        $stats['humano']->avg_duration ?? 0,
                        $stats['humano']->avg_satisfaction ?? 0,
                    ],
                    'backgroundColor' => 'rgba(255,99,132,0.7)',
                ],
            ],
            'labels' => ['Tempo de Resposta', 'Duração', 'Satisfação'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
