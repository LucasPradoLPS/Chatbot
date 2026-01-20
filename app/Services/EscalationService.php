<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Serviço de Escalonamento Inteligente para Humano
 * 
 * Detecta quando é hora de chamar um corretor de verdade
 * com resumo automático do caso
 */
class EscalationService
{
    /**
     * Detectar intenções que requerem escalação
     */
    public static function detectarIntencaoEscalacao(string $mensagem): ?array
    {
        $sinais = [
            'quero_visitar' => [
                'quero visitar', 'gostei', 'quer marcar', 'agendar', 'quando posso ir',
            ],
            'quero_proposta' => [
                'quero proposta', 'faça uma proposta', 'quanto fica', 'qual a documentação',
            ],
            'tenho_entrada' => [
                'tenho entrada', 'minha entrada é', 'consigo pagar', 'qual seria a parcela',
            ],
            'quero_negociar' => [
                'quer negociar', 'consiga descontar', 'abaixa', 'dá desconto',
            ],
            'urgente' => [
                'preciso rápido', 'preciso logo', 'é urgente', 'preciso em breve',
            ],
            'duvida_complexa' => [
                'não entendo', 'como funciona', 'qual a diferença', 'pode explicar',
            ],
        ];
        
        $mensagemLower = strtolower($mensagem);
        
        foreach ($sinais as $tipo => $palavras) {
            foreach ($palavras as $palavra) {
                if (stripos($mensagemLower, $palavra) !== false) {
                    return [
                        'tipo' => $tipo,
                        'sinal' => $palavra,
                        'prioridade' => self::calcularPrioridade($tipo),
                    ];
                }
            }
        }
        
        return null;
    }
    
    /**
     * Calcular prioridade de escalação
     */
    private static function calcularPrioridade(string $tipo): string
    {
        $prioridades = [
            'quero_visitar' => 'alta',
            'quero_proposta' => 'alta',
            'urgente' => 'critica',
            'tenho_entrada' => 'media',
            'quero_negociar' => 'media',
            'duvida_complexa' => 'baixa',
        ];
        
        return $prioridades[$tipo] ?? 'media';
    }
    
    /**
     * Gerar resumo do caso para o corretor
     */
    public static function gerarResumoCaso(
        int $empresaId,
        string $clienteJid,
        string $clienteNome,
        array $leadData,
        string $tipoEscalacao
    ): string {
        $renda = $leadData['renda_aproximada'] 
            ? 'R$ ' . number_format($leadData['renda_aproximada'], 0, ',', '.')
            : 'Não informada';
        
        $financiamento = $leadData['tipo_financiamento'] ?? 'Não informado';
        
        $bairros = $leadData['bairros_nao_negociaveis'] 
            ? implode(', ', (array)$leadData['bairros_nao_negociaveis'])
            : 'Flexível';
        
        $prioridades = $leadData['top_3_prioridades']
            ? implode(', ', (array)$leadData['top_3_prioridades'])
            : 'Nenhuma informada';
        
        $urgencia = $leadData['urgencia'] ?? 'Não informada';
        
        $preAprov = $leadData['tem_pre_aprovacao'] 
            ? "✅ Sim ({$leadData['pre_aprovacao_banco']})"
            : '❌ Não';
        
        return <<<RESUMO
📋 *RESUMO DO LEAD* 

👤 **Cliente**: {$clienteNome}
📱 **JID**: {$clienteJid}

💰 **Renda**: {$renda}
🏦 **Financiamento**: {$financiamento}
⏰ **Prazo**: {$leadData['prazo_desejado_anos']} anos
🚨 **Urgência**: {$urgencia}
✅ **Pré-Aprovação**: {$preAprov}

📍 **Localização (não-negociável)**: {$bairros}
⭐ **Prioridades**: {$prioridades}

🎯 **Motivo da Escalação**: {$tipoEscalacao}

**Ação**: Chamar corretor imediatamente
RESUMO;
    }
    
    /**
     * Fazer a escalação (notificar corretor, atualizar status)
     */
    public static function escalar(
        int $empresaId,
        string $clienteJid,
        string $clienteNome,
        array $leadData,
        string $tipoEscalacao,
        string $prioridade = 'media'
    ): array {
        try {
            $resumo = self::gerarResumoCaso($empresaId, $clienteJid, $clienteNome, $leadData, $tipoEscalacao);
            
            // TODO: Integrar com sistema de roteamento de corretores
            // Por enquanto, apenas registrar em log
            
            Log::info("ESCALAÇÃO DETECTADA", [
                'empresa_id' => $empresaId,
                'cliente_jid' => $clienteJid,
                'tipo' => $tipoEscalacao,
                'prioridade' => $prioridade,
                'resumo' => $resumo,
            ]);
            
            // Enviar mensagem ao cliente informando
            $mensagem = self::gerarMensagemEscalacao($clienteNome, $tipoEscalacao);
            
            return [
                'escalado' => true,
                'tipo' => $tipoEscalacao,
                'prioridade' => $prioridade,
                'resumo' => $resumo,
                'mensagem_cliente' => $mensagem,
            ];
        } catch (\Exception $e) {
            Log::error("Erro ao escalar", [
                'erro' => $e->getMessage(),
                'cliente_jid' => $clienteJid,
            ]);
            
            return ['escalado' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Gerar mensagem para cliente informando escalação
     */
    private static function gerarMensagemEscalacao(string $clienteNome, string $tipoEscalacao): string
    {
        $mensagens = [
            'quero_visitar' => "Ótimo {$clienteNome}! 🎯\n\nVou chamar um corretor pra agendar sua visita e tirar qualquer dúvida no caminho.\n\nUm momento...",
            'quero_proposta' => "Perfeito {$clienteNome}! 📋\n\nVou preparar uma proposta profissional com simulação de financiamento.\n\nUm momento...",
            'tenho_entrada' => "Excelente {$clienteNome}! 💰\n\nCom sua entrada, vou buscar as melhores opções de financiamento.\n\nUm momento...",
            'quero_negociar' => "Entendido {$clienteNome}! 🤝\n\nVou chamar quem pode negociar direto pelo preço.\n\nUm momento...",
            'urgente' => "Ok {$clienteNome}! 🚀\n\nVamos acelerar isso. Chamando um corretor agora.\n\nUm momento...",
            'duvida_complexa' => "Ótimo pergunta {$clienteNome}! 💡\n\nVou trazer um especialista para explicar bem.\n\nUm momento...",
        ];
        
        return $mensagens[$tipoEscalacao] ?? "Perfeito {$clienteNome}! Vou chamar um corretor para você.\n\nUm momento...";
    }
    
    /**
     * Roteamento inteligente de corretores
     * Por região, tipo de imóvel, disponibilidade, etc
     */
    public static function roteadorPorRegiaoDisponibilidade(
        int $empresaId,
        array $bairrosPrincipais,
        string $urgencia = 'media'
    ): ?string {
        // TODO: Implementar lógica de roteamento
        // Buscar corretor disponível que trabalha naquela região
        // Preferir por urgência
        
        Log::info("Roteamento de corretor", [
            'empresa_id' => $empresaId,
            'bairros' => $bairrosPrincipais,
            'urgencia' => $urgencia,
        ]);
        
        return null; // Corretor ID a ser implementado
    }
}
