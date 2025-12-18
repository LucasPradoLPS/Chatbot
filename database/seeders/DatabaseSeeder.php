<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;
use App\Models\InstanciaWhatsapp;
use App\Models\Agente;
use App\Models\AgenteGerado;
use App\Services\OpenAIService;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Criar empresa
        $empresa = Empresa::create([
            'nome' => 'Minha Empresa',
            'memoria_limite' => 4,
        ]);

        echo "✓ Empresa criada (ID: {$empresa->id})\n";

        // Criar instância WhatsApp
        // IMPORTANTE: Altere 'nome_da_sua_instancia' para o nome real da sua instância na Evolution
        $instancia = InstanciaWhatsapp::create([
            'instance_name' => 'N8n',
            'empresa_id' => $empresa->id,
        ]);

        echo "✓ Instância WhatsApp criada (instance_name: {$instancia->instance_name})\n";

        // Criar agente (IA ativa)
        $agente = Agente::create([
            'empresa_id' => $empresa->id,
            'ia_ativa' => true,
            'responder_grupo' => false, // true se quiser que responda em grupos
        ]);

        echo "✓ Agente criado (IA ativa)\n";

        // Criar Assistant automaticamente se OPENAI_KEY estiver configurada
        $openaiKey = config('services.openai.key');
        if ($openaiKey) {
            try {
                $service = app(OpenAIService::class);
                $assistantId = $service->createAssistant(
                    'Atendente IA',
                    'Você é um atendente virtual da empresa ' . ($empresa->nome ?? 'do cliente') .
                    '. Responda com cordialidade, clareza e objetividade. ' .
                    'Se recebido mídia, explique educadamente limitações e peça detalhes em texto. '
                );

                $agenteGerado = AgenteGerado::create([
                    'empresa_id' => $empresa->id,
                    'funcao' => 'atendente_ia',
                    'agente_base_id' => $assistantId,
                ]);

                echo "✓ Assistant criado na OpenAI (ID: {$assistantId})\n";
                echo "✓ Agente Gerado criado (agente_base_id: {$agenteGerado->agente_base_id})\n";
            } catch (\Throwable $e) {
                echo "⚠️ Falha ao criar Assistant automaticamente: {$e->getMessage()}\n";
                echo "   Configure OPENAI_KEY no .env e tente novamente.\n";
            }
        } else {
            echo "⚠️ OPENAI_KEY não configurada. Pulei criação automática de Assistant.\n";
            echo "   Use a rota API /api/agentes/generate após definir a chave.\n";
        }
    }
}
