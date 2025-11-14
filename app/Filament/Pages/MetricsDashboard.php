<?php

namespace App\Filament\Pages;

use Filament\Tables;
use Filament\Pages\Page;
use Filament\Tables\Table;
use App\Models\ConversationMetric;
use App\Filament\Widgets\MetricsChart;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Widgets\MetricsSummary;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Widgets\BotHumanDurationChart;
use App\Filament\Widgets\BotHumanResponseTimeChart;
use App\Filament\Widgets\BotHumanSatisfactionChart;
use App\Filament\Widgets\EscalatedConversationsChart;

class MetricsDashboard extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $title = 'Métricas de Conversas';
    protected static ?string $navigationGroup = 'Relatórios';
    protected static string $view = 'filament.pages.metrics-dashboard';

    public function table(Table $table): Table
    {
        return $table
            ->query(ConversationMetric::query()->orderByDesc('id'))
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('mode')
                    ->label('Modo')
                    ->badge()
                    ->color(fn($state) => $state === 'bot' ? 'info' : 'success')
                    ->formatStateUsing(fn($state) => ucfirst($state)),
                TextColumn::make('satisfaction')->label('Satisfação')->sortable(),
                TextColumn::make('avg_response_time_ms')->label('Resp. Média (ms)')->sortable(),
                TextColumn::make('total_duration_ms')->label('Duração (ms)')->sortable(),
                TextColumn::make('total_messages')->label('Mensagens')->sortable(),
            ])
            ->filters([
                SelectFilter::make('mode')
                    ->label('Modo')
                    ->options([
                        '' => 'Todos',
                        'bot' => 'Bot',
                        'humano' => 'Humano',
                    ])
            ])
            ->defaultSort('id', 'asc')
            ->paginated();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MetricsSummary::class,
            BotHumanResponseTimeChart::class,
            BotHumanDurationChart::class,
            BotHumanSatisfactionChart::class,
            EscalatedConversationsChart::class,
        ];
    }

}
