<?php

namespace App\Filament\Resources\KnowledgeChunkResource\Pages;

use App\Filament\Resources\KnowledgeChunkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Business;
use Filament\Notifications\Notification;

class ListKnowledgeChunks extends ListRecords
{
    protected static string $resource = KnowledgeChunkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('toggleMode')
            ->label(fn () => Business::first()?->mode ? '🔴 Desativar IA (Modo Humano)' : '🟢 Ativar IA (Modo Automático)')
            ->color(fn () => Business::first()?->mode ? 'danger' : 'success')
            ->action(function () {
                $business = Business::first();
                if ($business) {
                    $business->mode = !$business->mode;
                    $business->save();
                }

                Notification::make()
                    ->title($business->mode ? '🤖 IA ativada com sucesso!' : '👤 Modo humano ativado!')
                    ->success()
                    ->send();
            }),

        ];
    }
}
