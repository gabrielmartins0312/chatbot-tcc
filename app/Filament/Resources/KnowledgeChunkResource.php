<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KnowledgeChunkResource\Pages;
use App\Models\KnowledgeChunk;
use App\Models\Business;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KnowledgeChunkResource extends Resource
{
    protected static ?string $model = KnowledgeChunk::class;
    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';
    protected static ?string $navigationGroup = 'IA e Conhecimento';
    protected static ?string $navigationLabel = 'Base de Conhecimento';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('business_id')
                    ->label('Estabelecimento')
                    ->options(Business::all()->pluck('name', 'id'))
                    ->required(),

                Forms\Components\TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('content')
                    ->label('Conteúdo / Informação útil')
                    ->rows(6)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business.name')->label('Negócio'),
                Tables\Columns\TextColumn::make('title')->label('Título'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKnowledgeChunks::route('/'),
            'create' => Pages\CreateKnowledgeChunk::route('/create'),
            'edit' => Pages\EditKnowledgeChunk::route('/{record}/edit'),
        ];
    }
}
