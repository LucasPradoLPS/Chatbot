<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AgenteGerado;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Log;

class AtualizarPromptBot extends Command
{
    protected $signature = 'bot:atualizar-prompt';
    protected $description = 'Atualiza o prompt do bot na OpenAI com a nova personalidade';

    public function handle()
    {
        $this->info('🤖 ATUALIZANDO PROMPT DO BOT NA OPENAI');
        $this->newLine();

        // Buscar agente gerado
        $agenteGerado = AgenteGerado::where('funcao', 'atendente_ia')->first();
        
        if (!$agenteGerado) {
            $this->error('❌ Nenhum agente gerado encontrado!');
            $this->line('   Execute: php artisan agentes:generate');
            return 1;
        }

        $assistantId = $agenteGerado->agente_base_id;
        $this->info("✅ Agente encontrado: {$assistantId}");
        $this->newLine();

        // Novo prompt melhorado
        $novoPrompt = "Você é o assistente virtual da Imobiliária California, especializado em atendimento imobiliário.

PERSONALIDADE E TOM:
- SEMPRE comece com: \"Oi, bom dia! 👋\" (ou boa tarde/noite conforme horário)
- Seja educado, prestativo e use emojis apropriados
- Mostre as opções disponíveis logo na primeira mensagem
- Seja objetivo mas amigável
- Use linguagem clara e acessível

SAUDAÇÃO INICIAL (SEMPRE que for primeiro contato):
\"Oi, bom dia! 👋

Sou o assistente virtual da Imobiliária California! 🏠

Estou aqui para te ajudar a:
🔍 Ver imóveis disponíveis
📅 Agendar visitas  
💬 Falar com um corretor

Como posso te ajudar hoje?\"

OPÇÕES PRINCIPAIS:
Sempre ofereça estas opções de forma clara:
1️⃣ Comprar imóvel 🏠
2️⃣ Alugar imóvel 🔑
3️⃣ Vender imóvel 💰
4️⃣ Anunciar para aluguel 📢
5️⃣ Investimento 📈
6️⃣ Falar com corretor 👤

REGRAS IMPORTANTES:
1. Seja sempre educado e use saudações apropriadas
2. Cite o nome \"Imobiliária California\" na apresentação
3. Mostre as opções principais logo no início
4. Use emojis para facilitar a leitura
5. Seja prestativo e empático
6. Peça LGPD de forma clara mas sem ser invasivo

LGPD:
Após a saudação inicial, pergunte de forma educada:
\"Antes de continuar, preciso da sua autorização para usar seus dados pessoais e te enviar opções personalizadas conforme a LGPD. Posso continuar?

1️⃣ Sim, pode usar meus dados
2️⃣ Não, prefiro sem cadastro\"

Você trabalha com uma máquina de estados (StateMachine) que controla o fluxo da conversa. Sempre siga os prompts de cada estado e faça transições válidas entre estados.

Lembre-se: A primeira impressão é fundamental! Seja sempre educado, prestativo e mostre que está ali para ajudar.";

        $this->line('📝 Novo prompt:');
        $this->line(str_repeat('─', 60));
        $this->line($novoPrompt);
        $this->line(str_repeat('─', 60));
        $this->newLine();

        if (!$this->confirm('Deseja atualizar o Assistant na OpenAI com este prompt?', true)) {
            $this->warn('Operação cancelada.');
            return 0;
        }

        try {
            $openai = app(OpenAIService::class);
            
            $this->line('🔄 Atualizando Assistant na OpenAI...');
            
            // Atualizar via OpenAIService
            $response = $openai->updateAssistant(
                $assistantId,
                'Assistente California',
                $novoPrompt
            );
            
            $this->newLine();
            $this->info('✅ PROMPT ATUALIZADO COM SUCESSO!');
            $this->newLine();
            
            $this->line('📊 Detalhes do Assistant:');
            $this->info("   • ID: {$response['id']}");
            $this->info("   • Nome: {$response['name']}");
            $this->info("   • Modelo: {$response['model']}");
            $this->newLine();
            
            $this->info('🎉 Pronto! O bot agora vai usar a nova saudação educada.');
            $this->line('   Teste agora: php artisan bot:testar "oi" --numero=5511777777777');
            $this->newLine();
            
            Log::info('Prompt do bot atualizado com sucesso', [
                'assistant_id' => $assistantId,
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ ERRO ao atualizar prompt!');
            $this->error("   {$e->getMessage()}");
            
            Log::error('Erro ao atualizar prompt do bot', [
                'assistant_id' => $assistantId,
                'error' => $e->getMessage(),
            ]);
            
            return 1;
        }
    }
}
