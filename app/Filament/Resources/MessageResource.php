<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageResource\Pages;
use App\Models\Message;
use App\Models\Conversation;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Form;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static ?string $navigationGroup = 'Atendimento';
    protected static ?string $pluralModelLabel = 'Mensagens';
    protected static ?string $modelLabel = 'Mensagem';
    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('conversation.id')
                    ->label('ID da Conversa')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('conversation.mode')
                    ->label('Modo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'bot' => 'info',
                        'humano' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('direction')
                    ->label('Direção')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'in' ? 'primary' : 'warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sender')
                    ->label('Remetente')
                    ->getStateUsing(function ($record) {
                        if (in_array($record->sender, ['Bot', 'estabelecimento'])) {
                            return ucfirst($record->sender);
                        }
                
                        return str_replace('@s.whatsapp.net', '', $record->sender ?? '');
                    })
                    ->badge()
                    ->color(function ($record) {
                        if ($record->sender === 'Bot') {
                            return 'info';
                        }
                        if ($record->sender === 'estabelecimento') {
                            return 'warning';
                        }
                        return 'gray';
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('text')
                    ->label('Mensagem')
                    ->limit(100)
                    ->wrap()
                    ->searchable(),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('conversation.mode')
                    ->label('Modo da Conversa')
                    ->options([
                        'bot' => 'Bot',
                        'humano' => 'Humano',
                    ]),
                Tables\Filters\Filter::make('direction')
                    ->label('Direção')
                    ->query(fn ($query, array $data) =>
                        $query->when($data['value'] ?? null, fn ($q, $value) => $q->where('direction', $value))
                    )
                    ->form([
                        Forms\Components\Select::make('value')
                            ->label('Direção')
                            ->options(['in' => 'Entrada (Cliente)', 'out' => 'Saída (Empresa)']),
                    ]),
            ])
            ->defaultSort('sent_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalhes')
                    ->icon('heroicon-o-eye'),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('conversation_id')->disabled(),
                Forms\Components\TextInput::make('direction')->disabled(),
                Forms\Components\Textarea::make('text')->label('Conteúdo')->rows(6)->disabled(),
                Forms\Components\TextInput::make('sender')->disabled(),
                Forms\Components\DateTimePicker::make('sent_at')->disabled(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessages::route('/'),
        ];
    }
}
