<?php

namespace App\Services;

/**
 * Define a estrutura de slots (campos) para diferentes contextos
 */
class SlotsSchema
{
    /**
     * Slots para dados básicos do lead (aplicável a todos)
     */
    public const SLOTS_LEAD = [
        'nome' => null,
        'telefone_whatsapp' => null,
        'email' => null,
        'cidade' => null,
        'preferencia_contato' => null, // WhatsApp / ligação / e-mail
        'melhor_horario_contato' => null,
    ];

    /**
     * Slots adicionais para compra/aluguel
     */
    public const SLOTS_COMPRA_ALUGUEL = [
        'tipo_imovel' => null, // apto/casa/terreno/comercial/kitnet
        'finalidade' => null, // morar/investir
        'bairro_regiao' => null, // lista de preferências
        'cidade_imovel' => null,
        'faixa_valor_min' => null,
        'faixa_valor_max' => null,
        'quartos' => null,
        'banheiros' => null, // opcional
        'vagas' => null,
        'metragem_min' => null, // opcional
        'condominio_max' => null, // opcional
        'tags_prioridades' => null, // lista de prioridades (multi-seleção)
        'pet_friendly' => null, // sim/não
        'mobiliado' => null, // sim/não
        'pronto_ou_planta' => null,
        'prazo_mudanca' => null, // data ou "até X meses"
        'tem_fgts' => null, // sim/não – compra
        'entrada_disponivel' => null, // faixa
        'aprovacao_credito' => null, // sim/não/não sei

        // Agendamento de visita (compra/aluguel)
        'imovel_codigo_escolhido' => null, // código/id do imóvel selecionado
        'visita_data' => null, // AAAA-MM-DD
        'visita_hora' => null, // HH:MM
        'visita_datetime' => null, // alternativa combinada
        'endereco_aproximado' => null, // endereço ou ponto de encontro
        'ponto_encontro' => null, // descrição de ponto de encontro
        'visita_confirmada' => null, // sim/não
        'lembrar_visita_2h' => null, // sim/não
        'pos_visita_feedback' => null, // gostei/talvez/nao_gostei
        'pos_visita_motivo' => null, // preço/localização/estado/outros

        // Proposta / Oferta (compra/aluguel)
        'imovel_proposta_codigo' => null, // código/id do imóvel para o qual fazer proposta
        'valor_proposto' => null, // valor oferecido
        'forma_pagamento' => null, // financiamento / à vista / FGTS / combinado
        'prazo_resposta_dias' => null, // quantos dias para resposta esperada
        'capacidade_financeira_confirmada' => null, // sim/não – se escolher financiamento, precisa confirmar capacidade

        // Simulação de Financiamento
        'valor_imovel_simulacao' => null, // valor do imóvel a simular
        'entrada_disponivel_simulacao' => null, // quanto pode dar de entrada
        'renda_faixa_simulacao' => null, // faixa de renda (ex: "3000-5000", "5000-10000")
        'prazo_anos_simulacao' => null, // prazo em anos (20, 30, 35)
    ];

    /**
     * Slots para captação (vender/anunciar)
     */
    public const SLOTS_CAPTACAO = [
        'endereco_imovel' => null, // bairro + rua opcional
        'bairro_imovel' => null, // bairro específico
        'tipo_imovel' => null,
        'quartos' => null,
        'vagas' => null,
        'area_total' => null,
        'estado_imovel' => null, // novo/usado/reforma
        'tem_documentacao_ok' => null, // sim/não/não sei
        'ocupacao_status' => null, // ocupado/desocupado/em reforma
        'urgencia_venda_locacao' => null, // alta/média/baixa
        'preco_desejado' => null, // ou "não sei"
        'condominio_valor' => null, // valor mensal do condomínio
        'iptu_valor' => null, // valor mensal/anual do IPTU
        'aceita_permutar' => null, // venda
        'esta_ocupado' => null, // sim/não
        'melhor_horario_visita_captacao' => null,
        'avaliacao_agendamento_dia' => null, // AAAA-MM-DD
        'avaliacao_agendamento_hora' => null, // HH:MM
        'fotos_link' => null, // opcional
    ];

    /**
     * Slots para suporte (locação/pós-contrato)
     */
    public const SLOTS_SUPORTE = [
        'suporte_endereco_unidade' => null, // endereço/unidade/bloco/apto
        'suporte_tipo_problema' => null,    // hidráulica, elétrica, gás, porta/janela, eletrodoméstico, outros
        'suporte_urgencia' => null,         // alta/média/baixa
        'suporte_midia_link' => null,       // link de foto/vídeo (opcional)
    ];

    /**
     * Obter estrutura de slots para um determinado objetivo
     *
     * @param string|null $objetivo Objetivo: comprar, alugar, vender, anunciar_aluguel, investir
     * @return array Estrutura de slots mesclada
     */
    public static function getSlotsByObjetivo(?string $objetivo): array
    {
        $baseSlots = self::SLOTS_LEAD;

        return match ($objetivo) {
            'comprar', 'alugar', 'investir' => array_merge($baseSlots, self::SLOTS_COMPRA_ALUGUEL),
            'vender', 'anunciar_aluguel' => array_merge($baseSlots, self::SLOTS_CAPTACAO),
            'suporte' => array_merge($baseSlots, self::SLOTS_SUPORTE),
            default => $baseSlots,
        };
    }

    /**
     * Descrição amigável de cada slot para orientar a coleta
     */
    public const SLOT_DESCRIPTIONS = [
        // Lead
        'nome' => 'Nome completo do cliente',
        'telefone_whatsapp' => 'Número de WhatsApp (com DDD)',
        'email' => 'E-mail para contato',
        'cidade' => 'Cidade onde o cliente está localizado',
        'preferencia_contato' => 'Canal preferido: WhatsApp, ligação ou e-mail',
        'melhor_horario_contato' => 'Melhor horário para contato (manhã/tarde/noite)',

        // Compra/Aluguel
        'tipo_imovel' => 'Tipo: apartamento, casa, terreno, comercial, kitnet',
        'finalidade' => 'Finalidade: morar ou investir',
        'bairro_regiao' => 'Bairros ou regiões de interesse (lista)',
        'cidade_imovel' => 'Cidade desejada do imóvel',
        'faixa_valor_min' => 'Valor mínimo disponível',
        'faixa_valor_max' => 'Valor máximo disponível',
        'quartos' => 'Número de quartos desejados',
        'banheiros' => 'Número de banheiros (opcional)',
        'vagas' => 'Número de vagas de garagem',
        'metragem_min' => 'Metragem mínima desejada (opcional)',
        'condominio_max' => 'Valor máximo de condomínio/IPTU (opcional)',
        'pet_friendly' => 'Permite animais de estimação? (sim/não)',
        'mobiliado' => 'Preferência por imóvel mobiliado? (sim/não)',
        'pronto_ou_planta' => 'Pronto para morar ou planta/em construção?',
        'prazo_mudanca' => 'Prazo para mudança (data ou "até X meses")',
        'tem_fgts' => 'Possui FGTS disponível? (compra)',
        'entrada_disponivel' => 'Faixa de entrada que pode dar (compra)',
        'aprovacao_credito' => 'Já tem aprovação de crédito? (sim/não/não sei)',

        // Agendamento de visita
        'imovel_codigo_escolhido' => 'Código do imóvel escolhido para visita (ex.: #123)',
        'visita_data' => 'Data preferida para visita (AAAA-MM-DD)',
        'visita_hora' => 'Horário preferido para visita (ex.: 10:00, 18:00)',
        'visita_datetime' => 'Data e hora da visita em um único campo',
        'endereco_aproximado' => 'Endereço aproximado do imóvel ou ponto de encontro',
        'ponto_encontro' => 'Ponto de encontro combinado (portaria, recepção, etc.)',
        'visita_confirmada' => 'Confirmação final da visita (sim/não)',
        'lembrar_visita_2h' => 'Deseja receber lembrete 2h antes da visita? (sim/não)',
        'pos_visita_feedback' => 'Feedback após visita: Gostei / Talvez / Não gostei',
        'pos_visita_motivo' => 'Se não gostou: motivo (preço, localização, estado, outros) e observações',

        // Proposta / Oferta
        'imovel_proposta_codigo' => 'Código do imóvel para o qual está fazendo proposta (ex.: #123)',
        'valor_proposto' => 'Valor oferecido para o imóvel (em reais)',
        'forma_pagamento' => 'Forma de pagamento: financiamento, à vista, FGTS ou combinado',
        'prazo_resposta_dias' => 'Quantos dias o vendedor/imobiliária tem para responder a proposta?',
        'capacidade_financeira_confirmada' => 'Capacidade financeira confirmada? Necessário para propostas com financiamento',

        // Simulação de Financiamento
        'valor_imovel_simulacao' => 'Valor do imóvel para simular (em reais)',
        'entrada_disponivel_simulacao' => 'Quanto você tem disponível para entrada (em reais)',
        'renda_faixa_simulacao' => 'Sua faixa de renda aproximada (ex: 3000-5000, 5000-10000, 10000+)',
        'prazo_anos_simulacao' => 'Prazo desejado para o financiamento (20, 30 ou 35 anos)',

        // Captação
        'endereco_imovel' => 'Endereço do imóvel (bairro + rua opcional)',
        'bairro_imovel' => 'Bairro onde o imóvel está localizado',
        'tem_documentacao_ok' => 'Documentação em dia? (sim/não/não sei)',
        'ocupacao_status' => 'Status de ocupação atual: ocupado, desocupado ou em reforma',
        'urgencia_venda_locacao' => 'Urgência: alta, média ou baixa',
        'preco_desejado' => 'Preço desejado ou "não sei"',
        'condominio_valor' => 'Valor mensal de condomínio (se houver)',
        'iptu_valor' => 'Valor mensal ou anual do IPTU',
        'aceita_permutar' => 'Aceita permutar o imóvel? (venda)',
        'esta_ocupado' => 'Imóvel está ocupado atualmente? (sim/não)',
        'melhor_horario_visita_captacao' => 'Melhor horário para visita ao imóvel',
        'avaliacao_agendamento_dia' => 'Dia preferido para avaliação do imóvel (AAAA-MM-DD)',
        'avaliacao_agendamento_hora' => 'Horário preferido para avaliação (ex.: 10:00, 18:00)',
        'fotos_link' => 'Link com fotos do imóvel (opcional)',

        // Suporte
        'suporte_endereco_unidade' => 'Endereço e unidade do problema (rua, número, bloco, apto)',
        'suporte_tipo_problema' => 'Tipo do problema: hidráulica/vazamento, elétrica/chuveiro/tomada, gás, porta/janela, eletrodoméstico, outros',
        'suporte_urgencia' => 'Urgência: Alta (risco imediato), Média, Baixa',
        'suporte_midia_link' => 'Link de foto/vídeo do problema (opcional)',
    ];
}
