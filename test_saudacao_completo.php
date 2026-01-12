<?php
/**
 * Script completo de teste: Configura ambiente e testa saudação
 * 
 * Este script:
 * 1. Cria empresa, instância e agente de teste (se não existirem)
 * 2. Envia mensagem de teste
 * 3. Mostra logs em tempo real
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Empresa;
use App\Models\InstanciaWhatsapp;
use App\Models\Agente;
use App\Models\AgenteGerado;
use Illuminate\Support\Facades\Http;

echo "═══════════════════════════════════════════════════════════\n";
echo "  TESTE COMPLETO DE SAUDAÇÃO PERSONALIZADA\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// 1. Criar empresa de teste (se não existir)
$empresa = Empresa::firstOrCreate(
    ['nome' => 'Imobiliária Teste'],
    [
        'cnpj' => '00000000000000',
        'endereco' => 'Rua Teste, 123',
        'telefone' => '11999999999',
        'email' => 'teste@teste.com',
        'memoria_limite' => 4,
    ]
);
echo "✓ Empresa: {$empresa->nome} (ID: {$empresa->id})\n";

// 2. Criar instância de WhatsApp de teste (se não existir)
$instancia = InstanciaWhatsapp::firstOrCreate(
    ['instance_name' => 'chatbot-teste'],
    ['empresa_id' => $empresa->id]
);
echo "✓ Instância WhatsApp: {$instancia->instance_name}\n";

// 3. Criar agente (se não existir)
$agente = Agente::firstOrCreate(
    ['empresa_id' => $empresa->id],
    [
        'prompt_base' => 'Você é um assistente imobiliário prestativo.',
        'ia_ativa' => true,
    ]
);
echo "✓ Agente criado (IA Ativa: " . ($agente->ia_ativa ? 'SIM' : 'NÃO') . ")\n";

// 4. Criar agente gerado (Assistant ID)
$agenteGerado = AgenteGerado::firstOrCreate(
    [
        'empresa_id' => $empresa->id,
        'funcao' => 'atendente_ia',
    ],
    [
        'agente_base_id' => 'asst_test_' . time(),
        'prompt_gerado' => 'Assistente de teste',
    ]
);
echo "✓ Agente Gerado: {$agenteGerado->agente_base_id}\n\n";

echo "───────────────────────────────────────────────────────────\n";
echo "  ENVIANDO MENSAGEM DE TESTE\n";
echo "───────────────────────────────────────────────────────────\n\n";

// 5. Enviar mensagem de teste
$saudacao = $argv[1] ?? 'oi';
$mensagemEnviada = strtolower($saudacao) === 'ola' ? 'Olá' : 'Oi';
$numeroTeste = '5511999' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);

echo "Saudação: {$mensagemEnviada}\n";
echo "Número: {$numeroTeste}\n";
echo "Instância: {$instancia->instance_name}\n\n";

$payload = [
    'instance' => $instancia->instance_name,
    'data' => [
        'key' => [
            'remoteJid' => $numeroTeste . '@s.whatsapp.net',
            'id' => 'TEST_' . time() . '_' . rand(1000, 9999),
            'fromMe' => false,
        ],
        'message' => [
            'conversation' => $mensagemEnviada,
        ],
        'source' => 'test-complete-script',
    ],
];

try {
    $response = Http::post('http://127.0.0.1:8000/api/webhook/whatsapp', $payload);
    
    echo "Status HTTP: {$response->status()}\n";
    echo "Resposta: {$response->body()}\n\n";
    
    if ($response->successful()) {
        echo "✓ Mensagem enviada com sucesso!\n\n";
        
        echo "───────────────────────────────────────────────────────────\n";
        echo "  AGUARDANDO PROCESSAMENTO...\n";
        echo "───────────────────────────────────────────────────────────\n";
        
        sleep(3);
        
        // Verificar thread criada
        $thread = \App\Models\Thread::where('empresa_id', $empresa->id)
            ->where('numero_cliente', $numeroTeste)
            ->first();
        
        if ($thread) {
            echo "✓ Thread criada!\n";
            echo "  - Thread ID: {$thread->thread_id}\n";
            echo "  - Saudação detectada: " . ($thread->saudacao_inicial ?? 'NENHUMA') . "\n";
            echo "  - Estado: {$thread->estado_atual}\n";
            echo "  - Etapa fluxo: {$thread->etapa_fluxo}\n\n";
            
            if ($thread->saudacao_inicial === $mensagemEnviada) {
                echo "🎉 SUCESSO! A saudação '{$mensagemEnviada}' foi detectada corretamente!\n";
                echo "   O bot deve responder com '{$mensagemEnviada}!' no início da mensagem.\n\n";
            } else {
                echo "⚠️  ATENÇÃO: Saudação esperada '{$mensagemEnviada}', mas detectada: '" . 
                     ($thread->saudacao_inicial ?? 'NENHUMA') . "'\n\n";
            }
        } else {
            echo "⚠️  Thread não encontrada. Verifique os logs.\n\n";
        }
        
        echo "───────────────────────────────────────────────────────────\n";
        echo "Para ver os logs completos:\n";
        echo "  tail -f storage/logs/laravel.log\n";
        echo "  ou\n";
        echo "  Get-Content storage\\logs\\laravel.log -Tail 50\n";
        echo "═══════════════════════════════════════════════════════════\n";
    } else {
        echo "✗ Erro ao enviar mensagem: {$response->status()}\n";
    }
} catch (\Exception $e) {
    echo "✗ ERRO: {$e->getMessage()}\n";
    echo "  Verifique se o servidor está rodando: php artisan serve\n";
}
