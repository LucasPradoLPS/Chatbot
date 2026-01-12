<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class IntentDetector
{
    /**
     * Mapa de intenções com palavras-chave associadas
     */
    private const INTENT_KEYWORDS = [
        'saudacao' => [
            'oi', 'olá', 'opa', 'e aí', 'tudo bem', 'boa', 'bom dia', 'boa tarde', 'boa noite',
            'hey', 'como vai', 'beleza', 'e ai', 'eaí',
        ],
        'comprar_imovel' => [
            'comprar', 'compra', 'buscando compra', 'quero comprar', 'procuro compra', 'imóvel para compra',
            'casa para compra', 'apartamento para compra', 'terreno para compra', 'interesse em compra',
            'desejo comprar', 'pretendo comprar', 'penso em comprar',
        ],
        'alugar_imovel' => [
            'alugar', 'aluguel', 'locação', 'arrendar', 'quero alugar', 'procuro aluguel',
            'casa para aluguel', 'apartamento para aluguel', 'imóvel para aluguel',
            'busco aluguel', 'aluguel de imóvel', 'aluguel de casa', 'aluguel de apartamento',
        ],
        'vender_imovel' => [
            'vender', 'venda', 'quero vender', 'procuro vender', 'meu imóvel', 'minha casa',
            'meu apartamento', 'desejo vender', 'pretendo vender', 'penso em vender',
            'para vender', 'imóvel à venda',
        ],
        'anunciar_para_alugar' => [
            'anunciar', 'aluguel', 'propriedade para aluguel', 'quero aluguel', 'imóvel para aluguel',
            'anunciar propriedade', 'proprietário', 'proprietaria', 'meu imóvel para aluguel',
            'captação de aluguel', 'anúncio',
        ],
        'investimento' => [
            'investir', 'investimento', 'rendimento', 'retorno', 'imóvel para investimento',
            'oportunidade de investimento', 'renda extra', 'patrimônio', 'aplicação',
            'rendimento imobiliário', 'fluxo de caixa',
        ],
        'ver_imoveis' => [
            'ver imóveis', 'visualizar', 'mostrar imóveis', 'quero ver', 'me mostre',
            'quais imóveis', 'que imóveis', 'show imoveis', 'lista de imóveis',
            'catálogo', 'opções', 'disponível',
        ],
        'busca_direta' => [
            'buscando', 'procurando', 'procuro', 'estou buscando', 'tenho interesse',
            'gostaria de', 'qual é', 'tem algum', 'você tem',
        ],
        'filtrar' => [
            'bairro', 'região', 'preço', 'valor', 'faixa de preço', 'quartos', 'quarto',
            'vagas', 'garagem', 'tamanho', 'metragem', 'filtro', 'filtrar',
            'mais barato', 'mais caro', 'zona', 'área', 'localidade',
        ],
        'agendar_visita' => [
            'agendar', 'agendar visita', 'marcar visita', 'quando', 'que dia', 'que horas',
            'qual dia', 'qual horário', 'disponibilidade', 'horário', 'data',
            'visita', 'conhecer', 'ver de perto',
        ],
        'fazer_proposta' => [
            'proposta', 'ofereça', 'oferta', 'lance', 'lance proposta', 'fazer oferta',
            'quanto cobram', 'qual o preço', 'qual é o valor', 'valor da proposta',
        ],
        'simulacao_financiamento' => [
            'simular', 'simulação', 'financiamento', 'financiar', 'empréstimo', 'crédito',
            'parcelamento', 'parcelas', 'como financiar', 'taxa de juros', 'juros',
            'quanto fico devendo', 'qual é a prestação',
        ],
        'documentos' => [
            'documentos', 'documento', 'papelada', 'papéis', 'IPTU', 'RG', 'CPF',
            'comprovante', 'contrato', 'registro', 'certidão', 'escritura',
            'o que preciso', 'documentação', 'requisitos', 'necessário',
        ],
        'documentacao_complexa' => [
            'inventário', 'inventario', 'penhora', 'usucapião', 'usucapiao',
            'ação judicial', 'acao judicial', 'processo de inventário', 'herança', 'heranca',
            'matrícula complicada', 'matricula complicada', 'regularização complexa', 'regularizacao complexa',
        ],
        'status_atendimento' => [
            'status', 'progresso', 'como está', 'qual é o status', 'acompanhamento',
            'meu processo', 'meu boleto', 'meu contrato', 'minha proposta',
            'andamento', 'situação', 'onde está',
        ],
        'ameaca_juridica' => [
            'vou processar', 'processo', 'advogado', 'procon', 'reclame aqui', 'ação', 'acao',
            'justiça', 'justica', 'judicial', 'jurídico', 'juridico', 'ameaça', 'ameaçar', 'ameaçar vocês',
        ],
        'falar_com_corretor' => [
            'corretor', 'falar com', 'atendente', 'humano', 'pessoa', 'profissional',
            'especialista', 'gerente', 'suporte', 'telefone', 'ligar',
            'quero falar', 'preciso de', 'pode transferir', 'me passa',
        ],
        'negativa_sair' => [
            'não', 'nao', 'sair', 'tchau', 'adeus', 'até logo', 'bye', 'falou',
            'não quero', 'nao quero', 'sem interesse', 'desinteressado', 'chega',
            'encerrar', 'finalizar', 'sair daqui', 'pronto', 'é isso',
        ],
        'reclamacao_manutencao' => [
            'reclamação', 'reclamacao', 'problema', 'defeito', 'quebrou', 'danificado',
            'manutenção', 'manutencao', 'reparo', 'conserto', 'não funciona',
            'não funciona', 'estrago', 'queixa', 'insatisfeito',
        ],
    ];

    /**
     * Detecta a intenção do usuário baseado no texto da mensagem
     *
     * @param string $mensagem
     * @return string|null A intenção detectada ou 'indefinido' como fallback
     */
    public static function detect(string $mensagem): string
    {
        $mensagemLower = strtolower(trim($mensagem));
        $pontosIntent = [];

        // Iterar por todas as intenções e contar correspondências
        foreach (self::INTENT_KEYWORDS as $intent => $keywords) {
            $pontos = 0;
            foreach ($keywords as $keyword) {
                // Busca por palavras completas (word boundaries)
                if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $mensagem)) {
                    $pontos += 2; // Peso maior para correspondência exata
                } elseif (strpos($mensagemLower, strtolower($keyword)) !== false) {
                    $pontos += 1; // Peso menor para substring
                }
            }
            if ($pontos > 0) {
                $pontosIntent[$intent] = $pontos;
            }
        }

        // Retornar a intenção com maior pontuação, ou 'indefinido'
        if (empty($pontosIntent)) {
            return 'indefinido';
        }

        arsort($pontosIntent);
        return array_key_first($pontosIntent);
    }

    /**
     * Descrever a intenção para o assistant
     *
     * @param string $intent
     * @return string Descrição da intenção
     */
    public static function describe(string $intent): string
    {
        return match($intent) {
            'saudacao' => 'Usuário está cumprimentando. Responda calurosamente e ofereça ajuda.',
            'comprar_imovel' => 'Usuário quer COMPRAR imóvel. Mover para qualificação de comprador.',
            'alugar_imovel' => 'Usuário quer ALUGAR imóvel. Mover para qualificação de locatário.',
            'vender_imovel' => 'Usuário quer VENDER imóvel. Mover para captação (venda).',
            'anunciar_para_alugar' => 'Usuário quer ANUNCIAR imóvel para aluguel (proprietário). Mover para captação (locação).',
            'investimento' => 'Usuário está interessado em INVESTIMENTO imobiliário. Abordar perspectiva de retorno.',
            'ver_imoveis' => 'Usuário quer VER imóveis disponíveis. Mover para catálogo e recomendação.',
            'busca_direta' => 'Usuário está buscando por algo específico. Pergunte mais detalhes.',
            'filtrar' => 'Usuário quer FILTRAR imóveis. Aplicar filtros (bairro, valor, quartos, etc).',
            'agendar_visita' => 'Usuário quer AGENDAR visita. Mover para agendamento com datas e horários.',
            'fazer_proposta' => 'Usuário quer FAZER PROPOSTA / OFERTA. Mover para proposta formal.',
            'simulacao_financiamento' => 'Usuário quer SIMULAR financiamento. Ofereça simulação de crédito/parcelamento.',
            'documentos' => 'Usuário quer informações sobre DOCUMENTOS necessários. Listar requisitos.',
            'documentacao_complexa' => 'Caso com documentação COMPLEXA (inventário/penhora/usucapião). Encaminhar para corretor humano.',
            'status_atendimento' => 'Usuário quer saber STATUS de proposta/contrato/boleto. Fornecer informações.',
            'ameaca_juridica' => 'Usuário sinaliza ameaça jurídica/queixa séria. Encaminhar para atendimento humano imediato.',
            'falar_com_corretor' => 'Usuário quer FALAR com corretor HUMANO. Preparar handoff imediato.',
            'negativa_sair' => 'Usuário está saindo ou perdeu interesse. Despedir calurosamente.',
            'reclamacao_manutencao' => 'Usuário tem RECLAMAÇÃO ou problema de MANUTENÇÃO. Mover para suporte.',
            'indefinido' => 'Intenção não identificada. Pergunte ao usuário o que ele precisa de forma consultiva.',
            default => 'Intenção desconhecida.',
        };
    }
}
