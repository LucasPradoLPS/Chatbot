<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Thread;
use App\Models\Empresa;
use App\Models\Property;
use App\Services\EventService;
use Carbon\Carbon;

class TestCrmPipeline extends Command
{
    protected $signature = 'test:crm-pipeline {--fresh : Limpa os dados de teste anteriores}';
    protected $description = 'Testa o pipeline de CRM criando leads de exemplo e verificando eventos';

    public function handle()
    {
        $this->info('🧪 Iniciando teste do pipeline de CRM...');
        $this->newLine();

        // Busca ou cria empresa de teste
        $empresa = Empresa::first();
        
        if (!$empresa) {
            $this->error('❌ Nenhuma empresa encontrada no banco. Execute o seed primeiro.');
            return 1;
        }

        $this->info("✅ Usando empresa: {$empresa->nome}");
        $this->newLine();

        // Limpar dados de teste anteriores se --fresh
        if ($this->option('fresh')) {
            $this->warn('🧹 Limpando leads de teste anteriores...');
            Thread::whereIn('numero_cliente', [
                '+5511999999001',
                '+5511999999002',
                '+5511999999003',
                '+5511999999004',
                '+5511999999005',
                '+5511999999006',
            ])->delete();
            
            \DB::table('event_logs')->whereIn('numero_cliente', [
                '+5511999999001',
                '+5511999999002',
                '+5511999999003',
                '+5511999999004',
                '+5511999999005',
                '+5511999999006',
            ])->delete();
            
            $this->info('   ✅ Dados limpos!');
            $this->newLine();
        }

        // 1. Criar lead novo
        $this->info('📋 Teste 1: Criando novo lead...');
        $lead1 = Thread::create([
            'numero_cliente' => '+5511999999001',
            'empresa_id' => $empresa->id,
            'estado' => 'STATE_WELCOME',
            'slots' => json_encode(['objetivo' => 'comprar']),
            'crm_status' => 'novo_lead',
            'ultimo_contato' => now(),
            'lgpd_consentimento_data' => now(),
            'lgpd_politica_versao' => '1.0',
        ]);
        
        EventService::leadCreated(
            $empresa->id,
            $lead1->numero_cliente,
            [
                'objetivo' => 'comprar',
                'primeira_mensagem' => 'Olá, quero comprar um apartamento'
            ]
        );
        
        $this->info("   ✅ Lead criado: {$lead1->numero_cliente} - Status: {$lead1->crm_status}");

        // 2. Criar lead qualificado (2h atrás para testar follow-up)
        $this->info('📋 Teste 2: Criando lead qualificado (2h sem resposta)...');
        $lead2 = Thread::create([
            'numero_cliente' => '+5511999999002',
            'empresa_id' => $empresa->id,
            'estado' => 'STATE_FILTER',
            'slots' => json_encode([
                'objetivo' => 'comprar',
                'nome' => 'João Silva',
                'telefone_whatsapp' => '+5511999999002',
                'tipo_imovel' => 'apartamento',
                'bairro' => 'Vila Mariana',
            ]),
            'crm_status' => 'qualificado',
            'ultimo_contato' => now()->subHours(2)->subMinutes(5),
            'proximo_followup' => now()->subMinutes(5),
            'followup_tentativas' => 0,
            'lgpd_consentimento_data' => now()->subHours(2),
            'lgpd_politica_versao' => '1.0',
        ]);
        
        $this->info("   ✅ Lead qualificado: {$lead2->numero_cliente} - Status: {$lead2->crm_status}");
        $this->info("   ⏰ Último contato: {$lead2->ultimo_contato->format('d/m/Y H:i')}");
        $this->info("   📅 Próximo follow-up: {$lead2->proximo_followup->format('d/m/Y H:i')}");

        // 3. Criar lead em visita (24h atrás para testar follow-up)
        $this->info('📋 Teste 3: Criando lead em visita (24h sem resposta)...');
        $lead3 = Thread::create([
            'numero_cliente' => '+5511999999003',
            'empresa_id' => $empresa->id,
            'estado' => 'STATE_SCHEDULE_VISIT',
            'slots' => json_encode([
                'objetivo' => 'comprar',
                'nome' => 'Maria Santos',
                'telefone_whatsapp' => '+5511999999003',
                'tipo_imovel' => 'casa',
                'bairro' => 'Moema',
            ]),
            'crm_status' => 'em_visita',
            'ultimo_contato' => now()->subHours(25),
            'proximo_followup' => now()->subHours(1),
            'followup_tentativas' => 1,
            'lgpd_consentimento_data' => now()->subHours(25),
            'lgpd_politica_versao' => '1.0',
        ]);
        
        EventService::visitScheduled($empresa->id, $lead3->numero_cliente, null, [
            'data_agendada' => now()->addDays(2)->format('d/m/Y'),
        ]);
        
        $this->info("   ✅ Lead em visita: {$lead3->numero_cliente} - Status: {$lead3->crm_status}");
        $this->info("   ⏰ Último contato: {$lead3->ultimo_contato->format('d/m/Y H:i')}");
        $this->info("   📅 Próximo follow-up: {$lead3->proximo_followup->format('d/m/Y H:i')}");

        // 4. Criar lead com proposta enviada
        $this->info('📋 Teste 4: Criando lead com proposta enviada...');
        $lead4 = Thread::create([
            'numero_cliente' => '+5511999999004',
            'empresa_id' => $empresa->id,
            'estado' => 'STATE_PROPOSAL',
            'slots' => json_encode([
                'objetivo' => 'comprar',
                'nome' => 'Carlos Oliveira',
                'telefone_whatsapp' => '+5511999999004',
                'tipo_imovel' => 'apartamento',
                'bairro' => 'Pinheiros',
                'orcamento' => '500000',
            ]),
            'crm_status' => 'proposta_enviada',
            'ultimo_contato' => now()->subHours(1),
            'lgpd_consentimento_data' => now()->subHours(3),
            'lgpd_politica_versao' => '1.0',
        ]);
        
        EventService::proposalSent($empresa->id, $lead4->numero_cliente, null, [
            'valor' => 500000,
            'forma_pagamento' => 'financiamento',
            'urgencia' => 'alta',
        ]);
        
        $this->info("   ✅ Lead com proposta: {$lead4->numero_cliente} - Status: {$lead4->crm_status}");

        // 5. Criar lead perdido
        $this->info('📋 Teste 5: Criando lead perdido...');
        $lead5 = Thread::create([
            'numero_cliente' => '+5511999999005',
            'empresa_id' => $empresa->id,
            'estado' => 'STATE_FILTER',
            'slots' => json_encode([
                'objetivo' => 'comprar',
                'nome' => 'Ana Costa',
            ]),
            'crm_status' => 'perdido',
            'motivo_perda' => 'preço acima do orçamento',
            'ultimo_contato' => now()->subDays(3),
            'lgpd_consentimento_data' => now()->subDays(3),
            'lgpd_politica_versao' => '1.0',
        ]);
        
        EventService::leadLost($empresa->id, $lead5->numero_cliente, null, 'preço acima do orçamento');
        
        $this->info("   ✅ Lead perdido: {$lead5->numero_cliente} - Motivo: {$lead5->motivo_perda}");

        // 6. Criar lead com opt-out LGPD
        $this->info('📋 Teste 6: Criando lead com opt-out (não deve receber follow-up)...');
        $lead6 = Thread::create([
            'numero_cliente' => '+5511999999006',
            'empresa_id' => $empresa->id,
            'estado' => 'STATE_FILTER',
            'slots' => json_encode([
                'objetivo' => 'alugar',
                'nome' => 'Pedro Lima',
                'telefone_whatsapp' => '+5511999999006',
            ]),
            'crm_status' => 'qualificado',
            'ultimo_contato' => now()->subHours(3),
            'proximo_followup' => now()->subMinutes(30),
            'lgpd_consentimento_data' => now()->subHours(3),
            'lgpd_opt_out' => true,
            'lgpd_politica_versao' => '1.0',
        ]);
        
        $this->info("   ✅ Lead com opt-out: {$lead6->numero_cliente} - Não receberá follow-ups");

        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // Estatísticas
        $this->info('📊 ESTATÍSTICAS DO PIPELINE:');
        $this->newLine();
        
        $stats = [
            'novo_lead' => Thread::where('crm_status', 'novo_lead')->count(),
            'qualificado' => Thread::where('crm_status', 'qualificado')->count(),
            'em_visita' => Thread::where('crm_status', 'em_visita')->count(),
            'proposta_enviada' => Thread::where('crm_status', 'proposta_enviada')->count(),
            'fechado' => Thread::where('crm_status', 'fechado')->count(),
            'perdido' => Thread::where('crm_status', 'perdido')->count(),
        ];
        
        $this->table(
            ['Status CRM', 'Quantidade'],
            [
                ['Novo Lead', $stats['novo_lead']],
                ['Qualificado', $stats['qualificado']],
                ['Em Visita', $stats['em_visita']],
                ['Proposta Enviada', $stats['proposta_enviada']],
                ['Fechado', $stats['fechado']],
                ['Perdido', $stats['perdido']],
            ]
        );

        $this->newLine();
        $this->info('📋 EVENTOS REGISTRADOS:');
        $this->newLine();
        
        $eventos = \DB::table('event_logs')
            ->select('event_type', \DB::raw('count(*) as total'))
            ->groupBy('event_type')
            ->get();
        
        if ($eventos->isEmpty()) {
            $this->warn('   Nenhum evento registrado ainda.');
        } else {
            $this->table(
                ['Tipo de Evento', 'Total'],
                $eventos->map(fn($e) => [$e->event_type, $e->total])->toArray()
            );
        }

        $this->newLine();
        $this->info('⏰ LEADS AGUARDANDO FOLLOW-UP:');
        $this->newLine();
        
        $pendentes = Thread::where('crm_status', 'qualificado')
            ->whereNotNull('proximo_followup')
            ->where('proximo_followup', '<=', now())
            ->where(function($q) {
                $q->whereNull('lgpd_opt_out')
                  ->orWhere('lgpd_opt_out', false);
            })
            ->get();
        
        if ($pendentes->isEmpty()) {
            $this->info('   ✅ Nenhum lead pendente de follow-up no momento.');
        } else {
            foreach ($pendentes as $lead) {
                $this->info("   📞 {$lead->numero_cliente} - Tentativas: {$lead->followup_tentativas} - Próximo: {$lead->proximo_followup->format('d/m/Y H:i')}");
            }
        }

        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        $this->info('✅ Teste concluído!');
        $this->newLine();
        $this->info('📌 PRÓXIMOS PASSOS:');
        $this->info('   1. Execute: php artisan app:schedule-followups');
        $this->info('      (para testar o envio de follow-ups manualmente)');
        $this->newLine();
        $this->info('   2. Execute: php artisan schedule:work');
        $this->info('      (para rodar o scheduler continuamente em modo de teste)');
        $this->newLine();
        $this->info('   3. Em produção, configure o cron:');
        $this->info('      * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1');

        return 0;
    }
}
