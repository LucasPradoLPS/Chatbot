<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ObjectionHandlerService
{
    private static array $playbooks = [
        'muito_caro' => [
            'detectores' => ['muito caro', 'acima do orçamento', 'sai do meu bolso', 'não tenho'],
            'resposta' => 'entendo_preco',
            'opcoes' => [
                'mostrar_opcoes_mais_baratas' => 'Deixa eu buscar opções com preço mais baixo?',
                'mostrar_formas_pagamento' => 'Posso mostrar como parcelar? Às vezes fica mais viável.',
                'negociacao' => 'Vou passar pro corretor pra ver se consegue negociar preço.',
            ],
        ],
        'bairro_longe' => [
            'detectores' => ['longe', 'longe demais', 'fica muito distante', 'preciso perto'],
            'resposta' => 'entendo_localizacao',
            'opcoes' => [
                'vizinhos' => 'Tem bairros vizinhos bem perto. Quer ver opções lá?',
                'transporte' => 'Vou filtrar por metrô/ônibus perto. Ajuda?',
                'mapa' => 'Deixa eu mostrar no mapa pra ver a distância real.',
            ],
        ],
        'quer_negociar' => [
            'detectores' => ['negociar', 'consegue descontar', 'é o máximo que pago', 'pode abaixar'],
            'resposta' => 'preparar_negociacao',
            'opcoes' => [
                'escalacao' => 'Perfeito! Vou passar pra um corretor que pode negociar direto com o proprietário.',
                'condicoes' => 'Qual a sua proposta? Deixa eu verificar se é viável.',
            ],
        ],
        'medo_financiamento' => [
            'detectores' => ['medo de financiar', 'banco é complicado', 'não aprova', 'juros altos'],
            'resposta' => 'tranquilizar_financiamento',
            'opcoes' => [
                'pre_aprovacao' => 'Já tem pré-aprovação? Pode ficar bem mais seguro.',
                'simulacao' => 'Quer eu fazer uma simulação realista pra você?',
                'alternativas' => 'Tem outras formas (consórcio, parcelado direto) que pode interessar.',
            ],
        ],
        'nao_eh_agora' => [
            'detectores' => ['não é agora', 'depois a gente vê', 'agora não dá', 'tô em dúvida ainda'],
            'resposta' => 'respeitar_timing',
            'opcoes' => [
                'salvar' => 'Quer eu salvar essas opções? Quando quiser, pede pra eu mostrar de novo.',
                'acompanhar' => 'Posso buscar novas imóveis conforme saem. Mantém aberto?',
            ],
        ],
    ];
    
    /**
     * Detectar objeção na mensagem do cliente
     */
    public static function detectarObjecao(string $mensagem): ?array
    {
        $mensagemLower = strtolower($mensagem);
        
        foreach (self::$playbooks as $tipo => $playbook) {
            foreach ($playbook['detectores'] as $detector) {
                if (stripos($mensagemLower, $detector) !== false) {
                    return [
                        'tipo' => $tipo,
                        'detector' => $detector,
                        'playbook' => $playbook,
                    ];
                }
            }
        }
        
        return null;
    }
    
    /**
     * Gerar resposta ao playbook detectado
     * Retorna a mensagem formatada para enviar ao cliente
     */
    public static function gerarRespostaObjecao(array $objecao): string
    {
        $tipo = $objecao['tipo'];
        $playbook = $objecao['playbook'];
        
        $respostaInicial = self::gerarRespostaInicial($playbook['resposta']);
        
        $opcoes = collect($playbook['opcoes'])
            ->map(fn($texto, $chave) => "• {$texto}")
            ->join("\n");
        
        return <<<MSG
{$respostaInicial}

O que acha?
{$opcoes}
MSG;
    }
    
    /**
     * Gerar resposta inicial personalizadapor tipo de objeção
     */
    private static function gerarRespostaInicial(string $tipo): string
    {
        $respostas = [
            'entendo_preco' => '💰 Entendo, preço é importante mesmo! Deixa eu ajudar.',
            'entendo_localizacao' => '📍 Sim, localização faz toda diferença. Vamos refinar?',
            'preparar_negociacao' => '🤝 Ótimo! A gente consegue negociar. Vou chamar um especialista.',
            'tranquilizar_financiamento' => '🏦 Financiamento assusta mesmo, mas tenho boas notícias.',
            'respeitar_timing' => '⏰ Tudo bem! Imóvel é decisão pro longo prazo mesmo.',
        ];
        
        return $respostas[$tipo] ?? 'Entendi sua preocupação!';
    }
    
    /**
     * Registrar que uma objeção foi tratada (para analytics)
     */
    public static function registrarTratamento(
        int $empresaId,
        string $clienteJid,
        string $tipoObjecao,
        string $tratamento
    ): void {
        Log::info("Objeção tratada", [
            'empresa_id' => $empresaId,
            'cliente_jid' => $clienteJid,
            'tipo_objecao' => $tipoObjecao,
            'tratamento' => $tratamento,
        ]);
        
        // TODO: Registrar em ConversationAnalytics
    }
    
    /**
     * Sugerir escalação para humano se objeção é complexa
     */
    public static function sugerirEscalacao(string $tipoObjecao): bool
    {
        // Objeções que DEVEM ir pra humano
        $requerEscalacao = ['quer_negociar', 'medo_financiamento'];
        
        return in_array($tipoObjecao, $requerEscalacao);
    }
    
    /**
     * Obter todos os tipos de objeções disponíveis
     */
    public static function listarPlaybooks(): array
    {
        return array_map(fn($pb) => [
            'tipo' => key($pb),
            'exemplos_detectores' => array_slice($pb['detectores'], 0, 2),
        ], self::$playbooks);
    }
}
