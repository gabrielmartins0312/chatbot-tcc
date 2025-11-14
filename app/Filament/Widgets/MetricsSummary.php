<?php

namespace App\Filament\Widgets;

use App\Models\ConversationMetric;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MetricsSummary extends BaseWidget
{
    protected function getStats(): array
    {
        $stats = ConversationMetric::select(
            'mode',
            DB::raw('AVG(avg_response_time_ms) / 1000 as avg_response'),
            DB::raw('AVG(total_duration_ms) / 1000 as avg_duration'),            
            DB::raw('AVG(COALESCE(satisfaction, 0)) as avg_satisfaction')
        )->groupBy('mode')->get()->keyBy('mode');

        $bot = $stats['bot'] ?? null;
        $human = $stats['humano'] ?? null;

        return [
            Stat::make('⏱ Tempo médio (s)',
            number_format($bot?->avg_response ?? 0, 2).'s (Bot) / '.number_format($human?->avg_response ?? 0, 2).'s (Humano)'
            )
            ->color('info'),
            
            Stat::make('🕒 Duração média (s)',
                number_format($bot?->avg_duration ?? 0, 2).'s (Bot) / '.number_format($human?->avg_duration ?? 0, 2).'s (Humano)'
            )
            ->color('warning'),

            Stat::make('⭐ Satisfação média',
                number_format($bot?->avg_satisfaction ?? 0, 2).' (Bot) / '.number_format($human?->avg_satisfaction ?? 0, 2).' (Humano)'
            )
            ->color('success'),
        ];
    }
}
