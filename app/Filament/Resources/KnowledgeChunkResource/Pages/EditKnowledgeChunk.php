<?php

namespace App\Filament\Resources\KnowledgeChunkResource\Pages;

use App\Filament\Resources\KnowledgeChunkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKnowledgeChunk extends EditRecord
{
    protected static string $resource = KnowledgeChunkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
