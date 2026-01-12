<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Thread;
use App\Models\Empresa;
use App\Models\Property;
use App\Models\EventLog;
use App\Services\EventService;
use Carbon\Carbon;

class SimulateLeadJourney extends Command
{
    protected $signature = 'simulate:lead-journey';
    protected $description = 'Simula a jornada completa de um lead do início ao fechamento';

    public function handle()
    {
        $this->info('🎬 Simulando jornada completa de um lead...');
        $this->newLine();

        $empresa = Empresa::first();
        if (!$empresa) {
            $this->error('❌ Nenhuma empresa encontrada.');
            return 1;
        }

        $numero = '+5511' . rand(900000000, 999999999);

        // ETAPA 1: Lead Novo
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📝 ETAPA 1: NOVO LEAD');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $thread = Thread::create([
            'numero_cliente' => $numero,
            'empresa_id' => $empresa->id,
            'estado' => 'STATE_WELCOME',
            'slots' => json_encode([
                'objetivo' => 'comprar',
            ]),
            'crm_status' => 'novo_lead',
            'ultimo_contato' => now(),
            'lgpd_consentimento_data' => now(),
            'lgpd_politica_versao' => '1.0',
        ]);

        EventService::leadCreated($empresa->id, $numero, [
            'objetivo' => 'comprar',
            'primeira_mensagem' => 'Olá! Estou procurando um apartamento para comprar.',
        ]);

        $this->line("   ✅ Lead criado: <fg=cyan>{$numero}</>");
        $this->line("   📊 Status: <fg=yellow>novo_lead</>");
        $this->line("   🎯 Objetivo: comprar");
        $this->newLine();
        sleep(1);

        // ETAPA 2: Qualificado
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('✅ ETAPA 2: QUALIFICADO');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $slots = json_decode($thread->slots, true);
        $slots['nome'] = 'Roberto Silva';
        $slots['telefone_whatsapp'] = $numero;
        $slots['tipo_imovel'] = 'apartamento';
        $slots['bairro'] = 'Vila Mariana';
        $slots['quartos'] = '2';
        $slots['orcamento'] = '450000';

        $thread->update([
            'slots' => json_encode($slots),
            'estado' => 'STATE_FILTER',
            'crm_status' => 'qualificado',
            'ultimo_contato' => now(),
            'proximo_followup' => now()->addHours(2),
        ]);

        $this->line("   ✅ Dados coletados:");
        $this->line("      👤 Nome: Roberto Silva");
        $this->line("      🏠 Tipo: Apartamento");
        $this->line("      📍 Bairro: Vila Mariana");
        $this->line("      🛏️  Quartos: 2");
        $this->line("      💰 Orçamento: R$ 450.000");
        $this->line("   📊 Status: <fg=green>qualificado</>");
        $this->line("   ⏰ Follow-up agendado: " . $thread->proximo_followup->format('d/m H:i'));
        $this->newLine();
        sleep(1);

        // ETAPA 3: Visualização de Imóveis
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('👁️  ETAPA 3: VISUALIZAÇÃO DE IMÓVEIS');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // Criar propriedades de exemplo se não existirem
        $property1 = Property::firstOrCreate(
            ['codigo_propriedade' => 'APT-VM-001'],
            [
                'empresa_id' => $empresa->id,
                'titulo' => 'Apartamento 2 quartos Vila Mariana',
                'tipo_imovel' => 'apartamento',
                'bairro' => 'Vila Mariana',
                'endereco' => 'Rua Domingos de Morais, 1234',
                'cidade' => 'São Paulo',
                'estado' => 'SP',
                'preco' => 440000,
                'quartos' => 2,
                'banheiros' => 1,
                'vagas' => 1,
                'area_total' => 65,
                'status' => 'disponivel',
            ]
        );

        $property2 = Property::firstOrCreate(
            ['codigo_propriedade' => 'APT-VM-002'],
            [
                'empresa_id' => $empresa->id,
                'titulo' => 'Apartamento 2 quartos com suíte Vila Mariana',
                'tipo_imovel' => 'apartamento',
                'bairro' => 'Vila Mariana',
                'endereco' => 'Rua Vergueiro, 5678',
                'cidade' => 'São Paulo',
                'estado' => 'SP',
                'preco' => 480000,
                'quartos' => 2,
                'banheiros' => 2,
                'vagas' => 1,
                'area_total' => 70,
                'status' => 'disponivel',
            ]
        );

        EventService::propertyViewed($empresa->id, $numero, $property1->id);
        EventService::propertyViewed($empresa->id, $numero, $property2->id);

        $thread->update([
            'estado' => 'STATE_MATCH_RESULT',
            'ultimo_contato' => now(),
        ]);

        $this->line("   📋 Imóveis apresentados:");
        $this->line("      🏠 {$property1->titulo}");
        $this->line("         💰 R$ " . number_format($property1->preco, 2, ',', '.'));
        $this->line("         📐 {$property1->area_total}m² • {$property1->quartos} quartos");
        $this->newLine();
        $this->line("      🏠 {$property2->titulo}");
        $this->line("         💰 R$ " . number_format($property2->preco, 2, ',', '.'));
        $this->line("         📐 {$property2->area_total}m² • {$property2->quartos} quartos");
        $this->newLine();
        $this->line("   ✅ Eventos registrados: 2 visualizações");
        $this->newLine();
        sleep(1);

        // ETAPA 4: Agendamento de Visita
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📅 ETAPA 4: AGENDAMENTO DE VISITA');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $dataVisita = now()->addDays(2);
        
        EventService::visitScheduled($empresa->id, $numero, $property1->id, [
            'data_agendada' => $dataVisita->format('d/m/Y'),
            'horario' => '14:00',
            'propriedade' => $property1->titulo,
        ]);

        $thread->update([
            'estado' => 'STATE_SCHEDULE_VISIT',
            'crm_status' => 'em_visita',
            'ultimo_contato' => now(),
        ]);

        $this->line("   ✅ Visita agendada:");
        $this->line("      🏠 {$property1->titulo}");
        $this->line("      📅 {$dataVisita->format('d/m/Y')} às 14:00");
        $this->line("   📊 Status: <fg=cyan>em_visita</>");
        $this->newLine();
        sleep(1);

        // ETAPA 5: Proposta
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📑 ETAPA 5: PROPOSTA ENVIADA');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        EventService::proposalSent($empresa->id, $numero, $property1->id, [
            'valor' => 440000,
            'forma_pagamento' => 'financiamento',
            'entrada' => 50000,
            'parcelas' => 360,
            'banco' => 'Caixa',
            'urgencia' => 'alta',
        ]);

        $thread->update([
            'estado' => 'STATE_PROPOSAL',
            'crm_status' => 'proposta_enviada',
            'ultimo_contato' => now(),
        ]);

        $this->line("   ✅ Proposta enviada:");
        $this->line("      💰 Valor: R$ 440.000,00");
        $this->line("      💳 Entrada: R$ 50.000,00");
        $this->line("      📅 Financiamento: 360x via Caixa");
        $this->line("      ⚡ Urgência: ALTA");
        $this->line("   📊 Status: <fg=yellow>proposta_enviada</>");
        $this->newLine();
        sleep(1);

        // ETAPA 6: Fechamento
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🎉 ETAPA 6: FECHAMENTO');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        EventService::propertyClosed($empresa->id, $numero, $property1->id, [
            'valor_final' => 440000,
            'comissao' => 22000,
            'data_assinatura' => now()->addDays(7)->format('d/m/Y'),
        ]);

        $thread->update([
            'crm_status' => 'fechado',
            'ultimo_contato' => now(),
        ]);

        $property1->update([
            'status' => 'vendido',
        ]);

        $this->line("   🎉 <fg=green>VENDA FECHADA!</>");
        $this->line("      💰 Valor: R$ 440.000,00");
        $this->line("      💵 Comissão: R$ 22.000,00");
        $this->line("      📝 Assinatura: " . now()->addDays(7)->format('d/m/Y'));
        $this->line("   📊 Status: <fg=green>fechado</>");
        $this->newLine();

        // RESUMO FINAL
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 RESUMO DA JORNADA');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $eventos = EventLog::where('numero_cliente', $numero)->get();

        $this->line("   👤 Cliente: Roberto Silva ({$numero})");
        $this->line("   🏠 Imóvel: {$property1->titulo}");
        $this->line("   💰 Valor: R$ " . number_format(440000, 2, ',', '.'));
        $this->line("   ⏱️  Tempo total: simulado");
        $this->newLine();
        $this->line("   📋 Timeline de eventos:");
        
        foreach ($eventos as $evento) {
            $emoji = match($evento->event_type) {
                'lead_created' => '📝',
                'property_viewed' => '👁️ ',
                'visit_scheduled' => '📅',
                'proposal_sent' => '📑',
                'fechado' => '🎉',
                default => '📊',
            };
            
            $label = match($evento->event_type) {
                'lead_created' => 'Lead criado',
                'property_viewed' => 'Imóvel visualizado',
                'visit_scheduled' => 'Visita agendada',
                'proposal_sent' => 'Proposta enviada',
                'fechado' => 'Venda fechada',
                default => $evento->event_type,
            };

            $this->line("      $emoji {$label} - " . $evento->created_at->format('H:i:s'));
        }

        $this->newLine();
        $this->info('✅ Jornada completa simulada com sucesso!');
        $this->newLine();

        return 0;
    }
}
