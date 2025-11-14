<?php

namespace App\Filament\Resources\ConversationResource\Pages;

use App\Filament\Resources\ConversationResource;
use App\Models\Message;
use Filament\Resources\Pages\Page;
use App\Models\Conversation;

class ViewConversationMessages extends Page
{
    protected static string $resource = ConversationResource::class;

    protected static string $view = 'filament.resources.conversation-resource.pages.view-conversation-messages';

    public Conversation $conversation;

    public $messages;

    public function mount($record): void
    {
        $this->conversation = Conversation::findOrFail($record);

        $this->messages = Message::where('conversation_id', $record)
            ->orderBy('sent_at')
            ->get();
    }

    public function getTitle(): string
    {
        return "Mensagens da Conversa #{$this->conversation->id}";
    }
}
