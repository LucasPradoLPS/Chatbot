<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Thread;
use App\Models\EventLog;
use Carbon\Carbon;

class CrmReport extends Command
{
    protected $signature = 'crm:report';
    protected $description = 'Relatório visual do pipeline de CRM e eventos';

    public function handle()
    {
        $this->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->info('║           📊 RELATÓRIO DE CRM - PIPELINE DE VENDAS            ║');
        $this->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // FUNIL DE VENDAS
        $this->info('🔹 FUNIL DE VENDAS:');
        $this->newLine();

        $stats = [
            'novo_lead' => Thread::where('crm_status', 'novo_lead')->count(),
            'qualificado' => Thread::where('crm_status', 'qualificado')->count(),
            'em_visita' => Thread::where('crm_status', 'em_visita')->count(),
            'proposta_enviada' => Thread::where('crm_status', 'proposta_enviada')->count(),
            'fechado' => Thread::where('crm_status', 'fechado')->count(),
            'perdido' => Thread::where('crm_status', 'perdido')->count(),
        ];

        $total = array_sum($stats) - $stats['perdido'];
        $maxBar = 50;

        foreach ($stats as $status => $count) {
            if ($count == 0 && $status == 'fechado') continue;
            
            $label = match($status) {
                'novo_lead' => '📝 Novo Lead',
                'qualificado' => '✅ Qualificado',
                'em_visita' => '🏠 Em Visita',
                'proposta_enviada' => '📑 Proposta Enviada',
                'fechado' => '🎉 Fechado',
                'perdido' => '❌ Perdido',
            };

            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
            $barLength = $total > 0 ? (int)(($count / $total) * $maxBar) : 0;
            $bar = str_repeat('█', $barLength) . str_repeat('░', $maxBar - $barLength);
            
            $color = match($status) {
                'fechado' => 'green',
                'perdido' => 'red',
                'proposta_enviada' => 'yellow',
                default => 'white'
            };

            $this->line("   <fg=$color>$label</>");
            $this->line("   <fg=$color>$bar</> {$count} ({$percentage}%)");
            $this->newLine();
        }

        // TAXA DE CONVERSÃO
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        $this->info('🎯 TAXA DE CONVERSÃO:');
        $this->newLine();

        $conversoes = [
            'Lead → Qualificado' => [
                'de' => $stats['novo_lead'] + $stats['qualificado'] + $stats['em_visita'] + $stats['proposta_enviada'] + $stats['fechado'],
                'para' => $stats['qualificado'] + $stats['em_visita'] + $stats['proposta_enviada'] + $stats['fechado'],
            ],
            'Qualificado → Visita' => [
                'de' => $stats['qualificado'] + $stats['em_visita'] + $stats['proposta_enviada'] + $stats['fechado'],
                'para' => $stats['em_visita'] + $stats['proposta_enviada'] + $stats['fechado'],
            ],
            'Visita → Proposta' => [
                'de' => $stats['em_visita'] + $stats['proposta_enviada'] + $stats['fechado'],
                'para' => $stats['proposta_enviada'] + $stats['fechado'],
            ],
            'Proposta → Fechado' => [
                'de' => $stats['proposta_enviada'] + $stats['fechado'],
                'para' => $stats['fechado'],
            ],
        ];

        foreach ($conversoes as $label => $conv) {
            $taxa = $conv['de'] > 0 ? round(($conv['para'] / $conv['de']) * 100, 1) : 0;
            $emoji = $taxa >= 50 ? '🟢' : ($taxa >= 25 ? '🟡' : '🔴');
            $this->line("   $emoji $label: <fg=cyan>$taxa%</> ({$conv['para']}/{$conv['de']})");
        }

        // EVENTOS
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        $this->info('📅 EVENTOS REGISTRADOS (últimas 24h):');
        $this->newLine();

        $eventos = EventLog::where('created_at', '>=', now()->subDay())
            ->selectRaw('event_type, count(*) as total')
            ->groupBy('event_type')
            ->get();

        if ($eventos->isEmpty()) {
            $this->warn('   Nenhum evento nas últimas 24h');
        } else {
            $eventLabels = [
                'lead_created' => '📝 Lead Criado',
                'property_viewed' => '👁️  Imóvel Visualizado',
                'visit_scheduled' => '📅 Visita Agendada',
                'proposal_sent' => '📑 Proposta Enviada',
                'fechado' => '🎉 Fechado',
                'perdido' => '❌ Perdido',
                'followup_light' => '💬 Follow-up 2h',
                'followup_checkin24h' => '🔔 Follow-up 24h',
            ];

            foreach ($eventos as $evento) {
                $label = $eventLabels[$evento->event_type] ?? "📊 {$evento->event_type}";
                $this->line("   $label: <fg=cyan>{$evento->total}</>");
            }
        }

        // FOLLOW-UPS PENDENTES
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        $this->info('⏰ FOLLOW-UPS PENDENTES:');
        $this->newLine();

        $pendentes = Thread::where('crm_status', 'qualificado')
            ->whereNotNull('proximo_followup')
            ->where('proximo_followup', '<=', now()->addHours(2))
            ->where(function($q) {
                $q->whereNull('lgpd_opt_out')
                  ->orWhere('lgpd_opt_out', false);
            })
            ->orderBy('proximo_followup')
            ->get();

        if ($pendentes->isEmpty()) {
            $this->line("   <fg=green>✓ Nenhum follow-up pendente nas próximas 2 horas</>");
        } else {
            foreach ($pendentes as $lead) {
                $slots = json_decode($lead->slots, true);
                $nome = $slots['nome'] ?? 'Sem nome';
                $diff = now()->diffInMinutes($lead->proximo_followup, false);
                
                if ($diff < 0) {
                    $status = "<fg=red>⚠️  ATRASADO (" . abs($diff) . " min)</>";
                } else if ($diff < 60) {
                    $status = "<fg=yellow>⏳ em {$diff} min</>";
                } else {
                    $status = "<fg=green>⏰ em " . round($diff / 60, 1) . "h</>";
                }
                
                $this->line("   {$nome} ({$lead->numero_cliente}) - Tentativa {$lead->followup_tentativas} - $status");
            }
        }

        // MOTIVOS DE PERDA
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        $this->info('📉 PRINCIPAIS MOTIVOS DE PERDA:');
        $this->newLine();

        $motivos = Thread::where('crm_status', 'perdido')
            ->whereNotNull('motivo_perda')
            ->selectRaw('motivo_perda, count(*) as total')
            ->groupBy('motivo_perda')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($motivos->isEmpty()) {
            $this->line("   <fg=green>✓ Nenhuma perda registrada ainda</>");
        } else {
            foreach ($motivos as $motivo) {
                $this->line("   • {$motivo->motivo_perda}: <fg=red>{$motivo->total}</>");
            }
        }

        // LGPD
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        $this->info('🔒 COMPLIANCE LGPD:');
        $this->newLine();

        $lgpdStats = [
            'total' => Thread::count(),
            'com_consentimento' => Thread::whereNotNull('lgpd_consentimento_data')->count(),
            'opt_out' => Thread::where('lgpd_opt_out', true)->count(),
        ];

        $this->line("   ✅ Leads com consentimento: <fg=green>{$lgpdStats['com_consentimento']}/{$lgpdStats['total']}</>");
        $this->line("   🚫 Opt-outs (não podem receber follow-up): <fg=yellow>{$lgpdStats['opt_out']}</>");
        
        $compliance = $lgpdStats['total'] > 0 ? round(($lgpdStats['com_consentimento'] / $lgpdStats['total']) * 100, 1) : 0;
        $complianceColor = $compliance >= 90 ? 'green' : ($compliance >= 70 ? 'yellow' : 'red');
        $this->line("   📊 Taxa de compliance: <fg=$complianceColor>{$compliance}%</>");

        $this->newLine();
        $this->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        return 0;
    }
}
