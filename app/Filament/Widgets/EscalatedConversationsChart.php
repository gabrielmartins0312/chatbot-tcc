<?php

namespace App\Filament\Widgets;

use App\Models\Conversation;
use Filament\Widgets\ChartWidget;

class EscalatedConversationsChart extends ChartWidget
{
    protected static ?string $heading = '🚨 Conversas Escaladas';

    protected function getData(): array
    {
        $totalBot = Conversation::where('mode', 'bot')->count();

        $escalatedBot = Conversation::where('mode', 'bot')
            ->where('escalated', true)
            ->count();

        $notEscalated = $totalBot - $escalatedBot;

        return [
            'datasets' => [
                [
                    'label' => 'Escaladas',
                    'data' => [$escalatedBot],
                    'backgroundColor' => 'rgba(255, 99, 132, 0.7)',
                ],
                [
                    'label' => 'Não Escaladas',
                    'data' => [$notEscalated],
                    'backgroundColor' => 'rgba(54, 162, 235, 0.7)',
                ]
            ],
            'labels' => ['Conversas com BOT'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
