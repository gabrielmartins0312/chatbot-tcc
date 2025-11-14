<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenAI\Laravel\Facades\OpenAI;

class TestChatGPT extends Command
{
    protected $signature = 'test:chatgpt';
    protected $description = 'Testa a integração com o ChatGPT';

    public function handle()
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'Você é um assistente simpático que responde clientes de forma educada.'],
                ['role' => 'user', 'content' => 'Olá, quais são os horários de funcionamento da loja?']
            ],
        ]);

        $this->info("Resposta: " . $response['choices'][0]['message']['content']);
    }
}
