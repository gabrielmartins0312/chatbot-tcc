<?php

namespace App\Filament\Widgets;

use App\Models\ConversationMetric;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BotHumanResponseTimeChart extends ChartWidget
{
    protected static ?string $heading = '⏱ Tempo Médio de Resposta';

    protected function getData(): array
    {
        $stats = ConversationMetric::select(
            'mode',
            DB::raw('AVG(avg_response_time_ms) / 1000 as avg_response')
        )
        ->groupBy('mode')
        ->get()
        ->keyBy('mode');

        return [
            'datasets' => [
                [
                    'label' => 'Bot',
                    'data' => [$stats['bot']->avg_response ?? 0],
                    'backgroundColor' => 'rgba(54,162,235,0.7)',
                ],
                [
                    'label' => 'Humano',
                    'data' => [$stats['humano']->avg_response ?? 0],
                    'backgroundColor' => 'rgba(255,99,132,0.7)',
                ],
            ],
            'labels' => ['Tempo de Resposta'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
