# Chatbot de atendimento no WhatsApp com IA

Trabalho de Conclusão de Curso em Engenharia de Software (UniAmérica Descomplica, 2025).

Sistema de atendimento ao cliente pelo WhatsApp que responde com IA (OpenAI) usando a base de conhecimento do estabelecimento, passa a conversa para um atendente humano quando o cliente pede e mede cada atendimento. O objetivo do TCC foi comparar, com dados reais, o atendimento feito pelo bot e o feito por pessoas: tempo de resposta, duração e satisfação do cliente.

## O que o sistema faz

- **Responde no WhatsApp com IA.** Cada mensagem recebida chega por webhook, e a resposta é gerada pela API da OpenAI com base nas informações do estabelecimento e nas últimas mensagens da conversa.
- **Base de conhecimento editável.** O estabelecimento cadastra no painel os trechos de informação (horários, produtos, regras) que a IA usa para responder.
- **Dois modos de atendimento.** No modo IA, o bot conduz a conversa. No modo humano, a mensagem é registrada para um atendente responder, e o encerramento é feito pelo painel.
- **Passagem para humano.** Se o cliente pede para falar com uma pessoa, a conversa é marcada como escalada e o cliente é avisado de que um atendente vai entrar em contato.
- **Aviso de LGPD.** A primeira resposta informa que o atendimento faz parte de um projeto acadêmico e que as interações podem ser analisadas de forma anônima.
- **Pesquisa de satisfação.** Ao fim da conversa, o cliente dá uma nota de 1 a 5, e o sistema agradece de acordo com a nota.
- **Métricas por conversa.** Ao encerrar, o sistema calcula tempo médio de resposta, tempo até a primeira resposta, duração total, número de mensagens de cada lado, se houve escalonamento e a nota de satisfação.
- **Painel administrativo.** Em Filament, com conversas, mensagens, base de conhecimento e um painel de métricas que compara bot e humano em tempo de resposta, duração, satisfação e conversas escaladas.

## Como funciona

```
Cliente no WhatsApp
      │
      ▼
Evolution API ──webhook──▶ POST /api/webhook (Laravel)
                                 │
                 ┌───────────────┴───────────────┐
                 ▼                               ▼
            Modo IA                         Modo humano
  base de conhecimento + histórico     registra a mensagem
  ──▶ OpenAI ──▶ resposta              para o atendente
                 │                               │
                 └───────────────┬───────────────┘
                                 ▼
             Pesquisa de satisfação ──▶ métricas da conversa
                                 │
                                 ▼
                     Painel Filament (gráficos)
```

1. A Evolution API recebe a mensagem do WhatsApp e a envia para `POST /api/webhook`.
2. O sistema identifica a conversa aberta do cliente, ou cria uma nova, e guarda a mensagem.
3. No modo IA, monta o contexto com a base de conhecimento do estabelecimento e as seis mensagens mais recentes, chama a OpenAI e devolve a resposta pelo WhatsApp. Respostas idênticas em sequência são descartadas.
4. Quando o cliente se despede, pede um humano ou a própria IA encerra o assunto, o sistema pede a nota de 1 a 5.
5. Com a nota, a conversa é encerrada e as métricas são gravadas para o painel.

## Tecnologias

- PHP 8.2 e Laravel 12
- Filament 3 (painel administrativo e gráficos)
- API da OpenAI (chat completions)
- Evolution API (envio e recebimento de mensagens no WhatsApp)
- Banco relacional via Eloquent (SQLite por padrão, MySQL compatível)

## Estrutura principal

| Caminho | O que tem |
|---|---|
| `app/Http/Controllers/WebhookController.php` | Recebe as mensagens, decide o modo, chama a IA, faz a pesquisa de satisfação e calcula as métricas |
| `app/Http/Controllers/AdminConversationController.php` | Encerramento manual de conversas no modo humano |
| `app/Models` | Estabelecimento, conversa, mensagem, trecho de conhecimento e métricas |
| `app/Filament/Resources` | Telas de conversas, mensagens e base de conhecimento |
| `app/Filament/Widgets` | Gráficos que comparam bot e humano |
| `routes/api.php` | Rota do webhook |

## Como rodar

Pré-requisitos: PHP 8.2+, Composer, Node.js, uma chave da OpenAI e uma instância da Evolution API conectada a um número de WhatsApp.

```bash
git clone https://github.com/gabrielmartins0312/chatbot-tcc.git
cd chatbot-tcc
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan make:filament-user
php artisan serve
```

Acrescente ao `.env`:

```
OPENAI_API_KEY=sua-chave
EVOLUTION_API_URL=https://sua-instancia-evolution
EVOLUTION_SESSION=nome-da-sessao
EVOLUTION_API_KEY=chave-da-evolution
```

Depois, configure na Evolution API o webhook do evento `messages.upsert` apontando para `https://seu-endereco/api/webhook`, cadastre um estabelecimento e a base de conhecimento no painel (`/admin`) e mande uma mensagem para o número conectado.

## Próximos passos

- Buscar só os trechos da base de conhecimento relevantes para cada pergunta, em vez de enviar a base inteira, para reduzir custo e permitir bases maiores.
- Detectar despedidas e pedidos de humano com a própria IA, em vez de expressões fixas.
- Processar as mensagens em fila, para o webhook responder na hora mesmo com a IA lenta.
- Testes automatizados do fluxo do webhook.

## Autor

Gabriel Martins Nascimento · [LinkedIn](https://www.linkedin.com/in/gabrielmartins0312/) · [GitHub](https://github.com/gabrielmartins0312)
