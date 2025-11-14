<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\{Business, Conversation, Message, KnowledgeChunk, ConversationMetric};

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();
        Log::info('📩 Mensagem recebida:', $data);
    
        if (!isset($data['event']) || $data['event'] !== 'messages.upsert') {
            return response('EVENT_RECEIVED', 200);
        }
    
        $messageText = $data['data']['message']['conversation'] ?? null;
        $sender      = $data['data']['key']['remoteJid'] ?? null;
        $pushName    = $data['data']['pushName'] ?? 'Usuário';
        $fromMe      = $data['data']['key']['fromMe'] ?? false;
    
        if ($fromMe) {
            Log::info('📤 Ignorando mensagem enviada pelo próprio sistema.');
            return response('EVENT_RECEIVED', 200);
        }
    
        if (!$messageText || !$sender) {
            return response('EVENT_RECEIVED', 200);
        }
    
        //VERIFICAÇÃO AVALIAÇÃO DE SATISFAÇÃO
        if (preg_match('/^[1-5]$/', trim($messageText))) {
            $conversation = Conversation::where('sender', $sender)
                ->where('awaiting_satisfaction', true)
                ->orderBy('started_at', 'desc')
                ->first();
    
            if ($conversation) {
                Log::info("⭐ Avaliação detectada para conversa ID {$conversation->id}");
    
                $nota = (int) trim($messageText);
    
                $conversation->update([
                    'satisfaction'          => $nota,
                    'awaiting_satisfaction' => false,
                ]);
    
                $businessName = optional($conversation->business)->name ?? 'nosso atendimento';
    
                $respostas = [
                    1 => "Sinto muito que sua experiência não tenha sido positiva 😞. Agradecemos por compartilhar — vamos usar seu feedback para melhorar!",
                    2 => "Agradecemos por sua avaliação sobre {$businessName}! 💬 Vamos trabalhar para oferecer uma experiência melhor na próxima vez.",
                    3 => "Obrigado pela sua avaliação neutra 😊. Continuaremos nos esforçando para aprimorar nossos serviços.",
                    4 => "Ficamos felizes que você tenha ficado satisfeito! 😄 Conte conosco sempre que precisar.",
                    5 => "Ficamos muito felizes em saber que você ficou *muito satisfeito*! 🌟 Se precisar de algo no futuro, estaremos aqui para ajudar.",
                ];
    
                $agradecimento = $respostas[$nota] ?? "Obrigado por sua avaliação!";
    
                $this->sendAutoReply($sender, $agradecimento);
    
                Message::create([
                    'conversation_id' => $conversation->id,
                    'direction'       => 'out',
                    'sender'          => $conversation->mode === 'bot' ? 'Bot' : 'estabelecimento',
                    'text'            => $agradecimento,
                    'sent_at'         => now(),
                ]);
    
                $this->finalizeConversation($conversation);
    
                Log::info("🎯 Conversa {$conversation->id} finalizada com satisfação {$nota}.");
                return response('EVENT_RECEIVED', 200);
            } else {
                Log::info("ℹ️ Mensagem numérica ignorada (nenhuma conversa aguardando satisfação).", [
                    'sender' => $sender,
                    'text'   => $messageText,
                ]);
            }
        }

        $business = Business::first();
        $isAiMode = $business?->mode ?? true;
    
        if ($isAiMode) {
            $this->processAI($sender, $pushName, $messageText, $business);
        } else {
            $this->processHuman($sender, $pushName, $messageText, $business);
        }
    
        return response('EVENT_RECEIVED', 200);
    }    

    //PROCESSAMENTO COM IA
    private function processAI($sender, $pushName, $messageText, $business)
    {
        $conversation = Conversation::firstOrCreate(
            ['sender' => $sender, 'concluded' => false],
            [
                'business_id' => $business->id,
                'channel'     => 'whatsapp',
                'mode'        => 'bot',
                'started_at'  => now(),
            ]
        );        

        if (preg_match('/^[1-5]$/', trim($messageText))) {
            $conversation = Conversation::where('sender', $sender)
                ->where('awaiting_satisfaction', true)
                ->where('concluded', false)
                ->orderBy('started_at','desc')
                ->first();
    
            if ($conversation) {
                $nota = (int) trim($messageText);
                $conversation->update([
                    'satisfaction' => $nota,
                    'awaiting_satisfaction' => false,
                ]);
    
                $this->finalizeConversation($conversation);
    
                $respostas = [
                    1 => "Sinto muito que sua experiência não tenha sido positiva 😞. Agradecemos por compartilhar — vamos usar seu feedback para melhorar!",
                    2 => "Agradecemos por sua avaliação, Gabriel! 💬 Vamos trabalhar para oferecer uma experiência melhor na próxima vez.",
                    3 => "Obrigado pela sua avaliação neutra 😊. Continuaremos nos esforçando para aprimorar nossos serviços.",
                    4 => "Ficamos felizes que você tenha ficado satisfeito, Gabriel Martins! 😄 Conte conosco sempre que precisar.",
                    5 => "Ficamos muito felizes em saber que você ficou *muito satisfeito*! 🌟 Se precisar de algo no futuro, estaremos aqui para ajudar.",
                ];
    
                $agradecimento = $respostas[$nota] ?? "Obrigado por sua avaliação!";
    
                $this->sendAutoReply($sender, $agradecimento);
    
                Message::create([
                    'conversation_id' => $conversation->id,
                    'direction'       => 'out',
                    'sender'          => $conversation->mode === 'bot' ? 'Bot' : 'estabelecimento',
                    'text'            => $agradecimento,
                    'sent_at'         => now(),
                ]);                
    
                Log::info("🎯 Conversa finalizada com satisfação {$nota}.");
            }
    
            return;
        }
    
        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'in',
            'sender' => $sender,
            'text' => $messageText,
            'sent_at' => now(),
        ]);

        if (preg_match('/(atendente|humano|pessoa|ser humano|falar com alguém|Marta|Pedro|Chines|Angelita|falar com uma pessoa|falar com atendente|falar com humano|quero atendimento humano|atendimento humano)/i', $messageText)) {

            Log::info("🙋 Cliente pediu atendimento humano — iniciando fluxo especial.");

            $conversation->update([
                'mode' => 'bot',
                'escalated' => true,
                'awaiting_satisfaction' => true,
            ]);

            $msg2 = "Um atendente já vai entrar em contato contigo.\n" .
                    "Antes disso, poderia nos avaliar rapidamente? 😊\n" .
                    "Envie um número de *1 a 5*, sendo:\n" .
                    "1️⃣ Muito insatisfeito\n2️⃣ Insatisfeito\n3️⃣ Neutro\n4️⃣ Satisfeito\n5️⃣ Muito satisfeito";

            $this->sendAutoReply($sender, $msg2);

            Message::create([
                'conversation_id' => $conversation->id,
                'direction'       => 'out',
                'sender'          => 'Bot',
                'text'            => $msg2,
                'sent_at'         => now(),
            ]);            

            Log::info("📨 Fluxo de escalonamento concluído — aguardando avaliação.");

            return;
        }
    
        if (preg_match('/\b(n(ã|a)o\b|obrigad[ao]|s[óo] isso|tudo bem|por enquanto n(ã|a)o)\b/i', $messageText)) {
            Log::info("🧩 Cliente sinalizou encerramento da conversa.");
            $this->askForSatisfaction($conversation, $sender);
            return;
        }
    
        if ($conversation->messages()->count() <= 2) {
            $lgpd = "Olá {$pushName}! 😊 Bem-vindo ao atendimento humanizado do *{$business->name}*.\n" .
            "Este é um *projeto acadêmico* do curso de Engenharia de Software, e parte das interações podem ser analisadas de forma anônima, conforme a *Lei Geral de Proteção de Dados (LGPD)*.\n" .
            "Como posso te ajudar hoje?";
    
            $this->sendAutoReply($sender, $lgpd);
            Message::create([
                'conversation_id' => $conversation->id,
                'direction'       => 'out',
                'sender'          => 'Bot',
                'text'            => $lgpd,
                'sent_at'         => now(),
            ]);
            
            Log::info('🧾 LGPD enviada (modo IA).');
            return;
        }

        $knowledge = KnowledgeChunk::where('business_id', $business->id)
            ->pluck('content')
            ->implode("\n");
    

        $history = Message::where('conversation_id', $conversation->id)
            ->orderBy('sent_at', 'desc')
            ->take(6)
            ->get()
            ->reverse()
            ->map(fn($msg) => ($msg->direction === 'in' ? 'Cliente: ' : 'Bot: ') . $msg->text)
            ->implode("\n");
    
        $prompt = "
            Você é um assistente virtual da empresa {$business->name}.
            Baseie suas respostas no contexto abaixo.
    
            INFORMAÇÕES DO ESTABELECIMENTO:
            {$knowledge}
    
            HISTÓRICO RECENTE:
            {$history}
    
            MENSAGEM ATUAL DO CLIENTE:
            {$messageText}
    
            Diretrizes:
            - Responda de forma empática e natural.
            - Evite repetir mensagens idênticas.
            - Se o cliente disser 'não' ou 'obrigado', responda educadamente que irá encerrar e peça avaliação de satisfação.
            - Não envie perguntas adicionais depois disso.
        ";
    
        $response = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.8,
            'messages' => [
                ['role' => 'system', 'content' => 'Você é um atendente virtual empático e natural.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);
    
        $aiText = $response->json()['choices'][0]['message']['content'] ?? 'Desculpe, não consegui entender bem. Poderia reformular?';
    
        $lastResponse = Message::where('conversation_id', $conversation->id)
            ->where('direction', 'out')
            ->orderBy('sent_at', 'desc')
            ->first();
    
        if ($lastResponse && trim($lastResponse->text) === trim($aiText)) {
            Log::info('🔁 Evitando resposta repetida.');
            return;
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'out',
            'sender'          => 'Bot',
            'text'            => $aiText,
            'sent_at'         => now(),
        ]);
        
    
        $this->sendAutoReply($sender, $aiText);
        Log::info('🤖 IA respondeu:', ['text' => $aiText]);

        $END_REGEX      = '/(tenha (um )?ótimo dia|até logo|até mais|até breve|foi um prazer (ajudar|atender)|conte conosco|qualquer coisa,? (é só|só) chamar|obrigado(?:a)? e (tenha|um) (um )?bom dia|ficamos à disposição\.?\s*$)/i';
        $KEEP_OPEN_REGEX = '/(posso (te )?ajudar (em|com) mais|há|tem|deseja|precisa(r)? de mais alguma|posso auxiliar|como posso ajudar (hoje)?|em que posso ajudar)/i';
        
        if (
            !$conversation->awaiting_satisfaction
            && Message::where('conversation_id', $conversation->id)->where('direction', 'in')->exists()
            && preg_match($END_REGEX, $aiText)
            && !preg_match($KEEP_OPEN_REGEX, $aiText)
        ) {
            Log::info('🧠 Encerramento natural detectado — enviando pergunta de satisfação.');
            $this->askForSatisfaction($conversation, $sender);
            return;
        }
    }
    
    //PROCESSAMENTO HUMANO
    private function processHuman($sender, $pushName, $messageText, $business, $fromMe = false)
    {
        $conversation = Conversation::firstOrCreate(
            ['sender' => $sender, 'concluded' => false],
            [
                'business_id' => $business->id,
                'channel'     => 'whatsapp',
                'mode'        => 'humano',
                'started_at'  => now(),
            ]
        );
    
        if ($conversation->concluded) {
            Log::info("📭 Conversa já encerrada. Ignorando mensagem posterior.", [
                'conversation_id' => $conversation->id,
                'text' => $messageText
            ]);
            return;
        }

        $direction   = $fromMe ? 'out' : 'in';
        $senderValue = $fromMe ? 'estabelecimento' : $sender;        
    
        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => $direction,
            'sender' => $senderValue,
            'text' => $messageText,
            'sent_at' => now(),
        ]);

        if ($conversation->messages()->count() <= 2) {
            $lgpd = "Olá {$pushName}! 😊 Bem-vindo ao atendimento humanizado do *{$business->name}*.\n" .
                    "Este é um *projeto acadêmico* do curso de Engenharia de Software, e parte das interações podem ser analisadas de forma anônima, conforme a *Lei Geral de Proteção de Dados (LGPD)*.\n" .
                    "Espere um momento que um de nossos atendentes falará com você em breve.";
    
            $this->sendAutoReply($sender, $lgpd);
            Message::create([
                'conversation_id' => $conversation->id,
                'direction' => 'out',
                'text' => $lgpd,
                'sent_at' => now(),
            ]);
            return;
        }

        if (preg_match('/(encerrar|finalizar|fim)/i', $messageText)) {
            $this->askForSatisfaction($conversation, $sender);
            return;
        }
    }   

    //FINALIZAÇÃO DA CONVERSA E CÁLCULO DE MÉTRICAS
    private function finalizeConversation(Conversation $conversation)
    {
        $messages = $conversation->messages()->orderBy('sent_at')->get();
    
        $responseTimes        = [];
        $lastUserMessageTime  = null;
    
        foreach ($messages as $msg) {
            if ($msg->direction === 'in') {
                $lastUserMessageTime = $msg->sent_at;
            } elseif ($msg->direction === 'out' && $lastUserMessageTime) {
                $diffMs         = $lastUserMessageTime->diffInMilliseconds($msg->sent_at, false);
                $responseTimes[] = abs($diffMs);
                $lastUserMessageTime = null;
            }
        }

        $avgResponseTime = !empty($responseTimes)
            ? array_sum($responseTimes) / count($responseTimes)
            : 0;
    
        $totalMessages = $messages->count();
        $userMessages  = $messages->where('direction', 'in')->count();
        $botMessages   = $messages->where('direction', 'out')->count();

        $start = $conversation->started_at;
        $end   = now();
    
        $duration = 0;
        if ($start) {
            $duration = abs($start->diffInMilliseconds($end, false));
        }
    
        $duration        = min(max($duration, 0), PHP_INT_MAX);
        $avgResponseTime = min(max($avgResponseTime, 0), PHP_INT_MAX);

        $firstOut        = $messages->where('direction', 'out')->first();
        $firstResponseAt = $firstOut?->sent_at;
        $firstResponseMs = 0;

        if ($start && $firstResponseAt) {
            $firstResponseMs = abs($start->diffInMilliseconds($firstResponseAt, false));
        }
    
        ConversationMetric::updateOrCreate(
            ['conversation_id' => $conversation->id],
            [
                'avg_response_time_ms' => round($avgResponseTime),
                'total_messages'       => $totalMessages,
                'user_messages'        => $userMessages,
                'bot_messages'         => $botMessages,
                'total_duration_ms'    => (int) round($duration),
                'mode'                 => $conversation->mode,
                'escalated'            => $conversation->escalated ?? false,
                'satisfaction'         => $conversation->satisfaction ?? null,
            ]
        );

        $conversation->update([
            'finished_at'       => $end,
            'concluded'         => true,
            'first_response_at' => $firstResponseAt,
            'first_response_ms' => $firstResponseMs,
            'total_duration_ms' => (int) round($duration),
        ]);
    
        Log::info('✅ Conversa finalizada e métricas calculadas.', [
            'conversation_id'        => $conversation->id,
            'avg_response_time_ms'   => round($avgResponseTime),
            'total_duration_ms'      => $duration,
            'total_messages'         => $totalMessages,
            'satisfaction'           => $conversation->satisfaction,
            'escalated'              => $conversation->escalated,
            'mode'                   => $conversation->mode,
        ]);
    }
    
    private function askForSatisfaction(Conversation $conversation, string $sender)
    {
        $conversation->update(['awaiting_satisfaction' => true]);
    
        $message = "Antes de encerrar, gostaríamos de saber sua opinião! 😊\n" .
            "Por favor, avalie nosso atendimento de *1 a 5*, sendo:\n" .
            "1️⃣ Muito insatisfeito\n2️⃣ Insatisfeito\n3️⃣ Neutro\n4️⃣ Satisfeito\n5️⃣ Muito satisfeito";
    
        $this->sendAutoReply($sender, $message);
    
        Message::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'out',
            'sender'          => $conversation->mode === 'bot' ? 'Bot' : 'estabelecimento',
            'text'            => $message,
            'sent_at'         => now(),
        ]);
    }
    
    private function sendAutoReply($to, $text)
    {
        $apiUrl = env('EVOLUTION_API_URL') . '/message/sendText/' . env('EVOLUTION_SESSION');
        $apiKey = env('EVOLUTION_API_KEY');
    
        Http::withHeaders(['apikey' => $apiKey])->post($apiUrl, [
            'number' => $to,
            'text'   => $text,
        ]);
    }    
}
