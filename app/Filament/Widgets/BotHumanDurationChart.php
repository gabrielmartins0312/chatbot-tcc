<?php

namespace App\Filament\Widgets;

use App\Models\ConversationMetric;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BotHumanDurationChart extends ChartWidget
{
    protected static ?string $heading = '⏳ Duração Média das Conversas';

    protected function getData(): array
    {
        $stats = ConversationMetric::select(
            'mode',
            DB::raw('AVG(total_duration_ms) / 1000 as avg_duration')
        )
        ->groupBy('mode')
        ->get()
        ->keyBy('mode');

        return [
            'datasets' => [
                [
                    'label' => 'Bot',
                    'data' => [$stats['bot']->avg_duration ?? 0],
                    'backgroundColor' => 'rgba(54,162,235,0.7)',
                ],
                [
                    'label' => 'Humano',
                    'data' => [$stats['humano']->avg_duration ?? 0],
                    'backgroundColor' => 'rgba(255,99,132,0.7)',
                ],
            ],
            'labels' => ['Duração'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
