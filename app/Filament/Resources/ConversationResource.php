<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Message;
use Filament\Tables\Table;
use App\Models\Conversation;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;
use App\Filament\Resources\ConversationResource\Pages;
use App\Filament\Resources\ConversationResource\Pages\ListConversations;

class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Atendimento';
    protected static ?string $navigationLabel = 'Conversas';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sender')
                    ->label('Cliente')
                    ->searchable()
                    ->wrap(),
            
                Tables\Columns\BadgeColumn::make('mode')
                    ->label('Modo')
                    ->colors([
                        'success' => 'bot',
                        'warning' => 'humano',
                    ])
                    ->icons([
                        'heroicon-o-cpu-chip' => 'bot',
                        'heroicon-o-user'     => 'humano',
                    ])
                    ->formatStateUsing(fn ($state) => strtoupper($state)),
            
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Início')
                    ->dateTime('d/m/Y H:i'),
            
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Fim')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            
                Tables\Columns\IconColumn::make('escalated')
                    ->label('Escalada')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('danger')
                    ->falseColor('gray'),
            
                Tables\Columns\IconColumn::make('concluded')
                    ->label('Concluída')
                    ->boolean(),
            
                Tables\Columns\TextColumn::make('satisfaction')
                    ->label('Satisfação (1–5)')
                    ->alignCenter()
                    ->placeholder('—'),
            ])        
            ->filters([
                Tables\Filters\Filter::make('abertas')
                    ->label('Abertas (sem fim)')
                    ->query(fn ($q) => $q->whereNull('finished_at')),

                Tables\Filters\SelectFilter::make('mode')
                    ->label('Modo')
                    ->options([
                        'bot'    => 'Bot',
                        'humano' => 'Humano',
                    ]),

                Tables\Filters\TernaryFilter::make('escalated')
                    ->label('Escaladas')
                    ->trueLabel('Somente escaladas')
                    ->falseLabel('Somente não escaladas')
                    ->placeholder('Todas')
                    ->queries(
                        true: fn ($query) => $query->where('escalated', true),
                        false: fn ($query) => $query->where('escalated', false),
                        blank: fn ($query) => $query,
                    ),                
            ])
            ->actions([
                Tables\Actions\Action::make('verMensagens')
                    ->label('Mensagens')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->url(fn ($record) => ConversationResource::getUrl('view-conversation-messages', ['record' => $record->id]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('endAndAsk')
                    ->label('Encerrar e pedir avaliação')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Conversation $record) =>
                        $record->mode === 'humano'
                        && !$record->concluded
                        && !$record->awaiting_satisfaction
                    )
                    ->requiresConfirmation()
                    ->action(function (Conversation $record) {
                        if ($record->concluded) {
                            Notification::make()->danger()->title('Essa conversa já foi concluída.')->send();
                            return;
                        }

                        $record->awaiting_satisfaction = true;
                        $record->awaiting_closure_confirmation = false;
                        $record->save();

                        $text = "Antes de encerrar, gostaríamos de saber sua opinião! 😊\n"
                            ."Por favor, avalie nosso atendimento de *1 a 5*, sendo:\n"
                            ."1️⃣ Muito insatisfeito\n2️⃣ Insatisfeito\n3️⃣ Neutro\n4️⃣ Satisfeito\n5️⃣ Muito satisfeito";

                        $apiUrl = env('EVOLUTION_API_URL') . '/message/sendText/' . env('EVOLUTION_SESSION');
                        $apiKey = env('EVOLUTION_API_KEY');

                        Http::withHeaders(['apikey' => $apiKey])->post($apiUrl, [
                            'number' => $record->sender,
                            'text'   => $text,
                        ]);

                        $lastIn = Message::where('conversation_id', $record->id)
                            ->where('direction', 'in')
                            ->orderByDesc('sent_at')
                            ->first();

                        $latencyMs = $lastIn ? now()->diffInMilliseconds($lastIn->sent_at) : null;

                        Message::create([
                            'conversation_id'     => $record->id,
                            'direction'           => 'out',
                            'text'                => $text,
                            'sent_at'             => now(),
                            'latency_ms'          => $latencyMs,
                            'reply_to_message_id' => $lastIn?->id,
                        ]);

                        Notification::make()->success()->title('Solicitação de avaliação enviada.')->send();
                    }),
                    
            ])
            ->defaultSort('started_at', 'desc')
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConversations::route('/'),
            'view-conversation-messages' => Pages\ViewConversationMessages::route('/{record}/mensagens'),
        ];
    }
}
