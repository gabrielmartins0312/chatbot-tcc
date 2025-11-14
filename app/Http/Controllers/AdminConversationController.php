<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminConversationController extends Controller
{
    public function end(Request $request, Conversation $conversation)
    {
        if ($conversation->concluded) {
            return back()->with('error', 'Essa conversa já foi concluída.');
        }

        if ($conversation->mode !== 'humano') {
            return back()->with('error', 'Encerramento manual só é permitido no modo HUMANO.');
        }

        if ($conversation->awaiting_satisfaction) {
            return back()->with('info', 'Já foi solicitada a avaliação desta conversa.');
        }

        $conversation->awaiting_satisfaction = true;
        $conversation->awaiting_closure_confirmation = false;
        $conversation->save();

        $text = "Antes de encerrar, gostaríamos de saber sua opinião! 😊\n"
            ."Por favor, avalie nosso atendimento de *1 a 5*, sendo:\n"
            ."1️⃣ Muito insatisfeito\n2️⃣ Insatisfeito\n3️⃣ Neutro\n4️⃣ Satisfeito\n5️⃣ Muito satisfeito";

        $apiUrl = env('EVOLUTION_API_URL') . '/message/sendText/' . env('EVOLUTION_SESSION');
        $apiKey = env('EVOLUTION_API_KEY');

        Http::withHeaders(['apikey' => $apiKey])->post($apiUrl, [
            'number' => $conversation->sender,
            'text'   => $text,
        ]);

        $lastIn = Message::where('conversation_id', $conversation->id)
            ->where('direction', 'in')
            ->orderByDesc('sent_at')
            ->first();

        $latencyMs = $lastIn ? now()->diffInMilliseconds($lastIn->sent_at) : null;

        Message::create([
            'conversation_id'     => $conversation->id,
            'direction'           => 'out',
            'text'                => $text,
            'sent_at'             => now(),
            'latency_ms'          => $latencyMs,
            'reply_to_message_id' => $lastIn?->id,
        ]);

        Log::info('📊 Avaliação solicitada manualmente (modo humano).', ['conversation_id' => $conversation->id]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Solicitação de avaliação enviada ao cliente.');
    }
}
