<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AgenteGerado;
use App\Models\Empresa;
use App\Services\OpenAIService;

class RegenerarAssistente extends Command
{
    protected $signature = 'bot:regenerate-assistant {empresa_id}';
    protected $description = 'Regenera o assistente da OpenAI com instruções atualizadas';

    public function handle(OpenAIService $openAI)
    {
        $empresaId = $this->argument('empresa_id');
        $empresa = Empresa::find($empresaId);

        if (!$empresa) {
            $this->error("Empresa $empresaId não encontrada");
            return 1;
        }

        $this->info("Buscando assistente para empresa {$empresa->nome}...");

        $agenteGerado = AgenteGerado::where('empresa_id', $empresaId)
            ->where('funcao', 'atendente_ia')
            ->first();

        if (!$agenteGerado) {
            $this->error("Nenhum agente gerado encontrado");
            return 1;
        }

        $oldAssistantId = $agenteGerado->agente_base_id;
        $this->info("Encontrado assistente: $oldAssistantId");

        // Nova instrução melhorada
        $novasInstrucoes = <<<'EOT'
Você é um assistente de atendimento ao cliente para uma imobiliária. Seu objetivo é:

1. **SEMPRE responder ao que o cliente diz** - leia a mensagem do cliente e responda especificamente ao conteúdo dela
2. **Manter a conversa natural** - não repita a mesma mensagem pronta; adapte sua resposta ao contexto
3. **Ser consultivo** - faça perguntas, refine o entendimento, nunca ignore o que foi dito
4. **Usar os slots** - mantenha um registro dos dados coletados (nome, telefone, objetivo, etc)
5. **Respeitar o fluxo de estado** - siga o estado atual indicado e transicione quando apropriado
6. **Ser breve** - respostas de 2-5 linhas, diretas e claras

IMPORTANTE:
- Se o cliente disser "Oi", responda cumprimentando DE VOLTA e perguntando como posso ajudar
- Se disser um bairro, responda validando e perguntando mais detalhes (valor, tipo de imóvel, etc)
- Se tiver dúvida, pergunte para esclarecer, não ignore
- Sempre inclua os slots atualizados em JSON ao final: [[SLOTS]]{...}[[/SLOTS]]
- Siga o estado e instruções fornecidas, mas sempre contextualize com a mensagem específica do cliente
EOT;

        $this->info("Criando novo assistente com instruções atualizadas...");
        $novoAssistantId = $openAI->createAssistant(
            "Assistente Imobiliário - {$empresa->nome}",
            $novasInstrucoes,
            'gpt-4-turbo'
        );

        if (!$novoAssistantId) {
            $this->error("Erro ao criar novo assistente");
            return 1;
        }

        $agenteGerado->agente_base_id = $novoAssistantId;
        $agenteGerado->save();

        $this->info("✓ Assistente regenerado com sucesso!");
        $this->info("  ID antigo: $oldAssistantId");
        $this->info("  ID novo: $novoAssistantId");

        return 0;
    }
}
