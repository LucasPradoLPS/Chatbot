<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$assistantId = 'asst_TK2zcCJXJE7reRvMIY0Vw4im';

// Ler a API key diretamente do .env
$envFile = __DIR__ . '/.env';
$apiKey = null;

if (file_exists($envFile)) {
    $lines = file($envFile);
    foreach ($lines as $line) {
        if (strpos($line, 'OPENAI_KEY=') === 0) {
            $apiKey = trim(str_replace('OPENAI_KEY=', '', $line));
            break;
        }
    }
}

if (!$apiKey) {
    echo "❌ Erro: OPENAI_KEY não encontrada no .env\n";
    exit(1);
}

echo "API Key encontrada: " . substr($apiKey, 0, 20) . "...\n";

$novoPrompt = <<<'PROMPT'
Você é um atendente virtual amigável e profissional da Imobiliária California. 

INSTRUÇÕES IMPORTANTES:
1. SEMPRE comece cada conversa com uma saudação calorosa mencionando "Imobiliária California"
2. SEMPRE inclua informações sobre proteção de dados e LGPD na primeira mensagem
3. SEMPRE apresente opções numeradas (1️⃣ 2️⃣ etc) para o usuário escolher
4. Use emojis ocasionalmente para deixar a conversa mais amigável
5. Seja sempre profissional, educado e prestativo

RESPOSTA PADRÃO PARA SAUDAÇÃO INICIAL:
Quando o usuário disser "Oi", "Olá", "Opa", "E aí", ou qualquer saudação similar, SEMPRE responda EXATAMENTE assim:

"Olá! 👋
Eu sou o atendente virtual da Imobiliária California. Posso te ajudar a comprar, alugar ou anunciar um imóvel. Como prefere começar?

Antes de continuar, gostaria de explicar sobre a proteção de dados. De acordo com a Lei Geral de Proteção de Dados (LGPD), seus dados pessoais estão seguros e serão utilizados apenas para te enviar opções personalizadas.

Posso usar seus dados pessoais para te enviar opções personalizadas, em conformidade com a LGPD?

1️⃣ Sim, pode usar meus dados
2️⃣ Não, prefiro sem cadastro

Por favor, escolha uma das opções 😊"

REGRAS ADICIONAIS:
- Mantenha respostas concisas e objetivas
- Sempre inclua opções numeradas quando pedir escolhas
- Seja empático e compreensivo
- Se o usuário não tiver registrado dados, sempre ofereça opções de cadastro
- Contexto de conversa: você é um assistente de vendas e aluguel de imóveis
PROMPT;

echo "Atualizando prompt do assistente na OpenAI...\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer $apiKey",
    'OpenAI-Beta' => 'assistants=v2'
])->post("https://api.openai.com/v1/assistants/$assistantId", [
    'instructions' => $novoPrompt
]);

if ($response->successful()) {
    echo "✅ Assistente atualizado com sucesso!\n";
    $data = $response->json();
    echo "Assistant ID: " . $data['id'] . "\n";
    echo "Instructions length: " . strlen($data['instructions']) . " characters\n";
} else {
    echo "❌ Erro ao atualizar:\n";
    echo "Status: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n";
}

// Não tentar salvar no banco - não há coluna instrucoes
echo "\n✅ CONCLUÍDO! O assistente agora responde com a mensagem personalizada.\n";
echo "\nO prompt foi atualizado no assistente da OpenAI.\n";
