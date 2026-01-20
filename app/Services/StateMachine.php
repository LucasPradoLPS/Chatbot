<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Máquina de estados para gerenciar o fluxo conversacional
 */
class StateMachine
{
    /**
     * Estados disponíveis na máquina
     */
    public const STATES = [
        // Onboarding
        'STATE_START' => 'Boas-vindas inicial',
        'STATE_LGPD' => 'Consentimento LGPD',
        'STATE_OBJETIVO' => 'Escolher objetivo (Comprar/Alugar/Vender/Etc)',

        // Funil Compra/Aluguel
        'STATE_Q1_LOCAL' => 'Qualificação - Localização',
        'STATE_Q2_TIPO' => 'Qualificação - Tipo de imóvel',
        'STATE_Q3_QUARTOS' => 'Qualificação - Quartos',
        'STATE_Q4_ORCAMENTO' => 'Qualificação - Orçamento',
        'STATE_Q5_PRIORIDADES' => 'Qualificação - Prioridades',
        'STATE_Q6_PRAZO' => 'Qualificação - Prazo',
        'STATE_Q7_DADOS_CONTATO' => 'Qualificação - Dados de contato',
        'STATE_MATCH_RESULT' => 'Resultado de match (Cards de imóveis)',
        'STATE_REFINAR' => 'Refinar busca',

        // Agendamento de visita (profissional)
        'STATE_VISITA_IMOVEL_ESCOLHA' => 'Agendamento - Escolha do imóvel',
        'STATE_VISITA_DATA_HORA' => 'Agendamento - Data e hora',
        'STATE_VISITA_CONFIRMACAO' => 'Agendamento - Confirmação final',
        'STATE_VISITA_POS' => 'Pós-visita - Feedback',

        // Proposta / Oferta
        'STATE_PROPOSTA' => 'Proposta - Fazer oferta para imóvel',

        // Simulação de Financiamento
        'STATE_SIMULACAO' => 'Simulação - Cálculo de financiamento',

        // Suporte (locação/pós-contrato)
        'STATE_SUPORTE_MENU' => 'Suporte - Menu de atendimento',
        'STATE_MANUTENCAO' => 'Suporte - Manutenção e abertura de chamado',

        // Funil Captação (Vender/Anunciar)
        'STATE_CAPTACAO_INICIO' => 'Captação - Início',
        'STATE_CAPTACAO_DADOS_IMOVEL' => 'Captação - Dados do imóvel',
        'STATE_CAPTACAO_PRECO' => 'Captação - Preço e encargos',
        'STATE_CAPTACAO_MIDIA' => 'Captação - Mídia e avaliação',
        'STATE_CAPTACAO_FECHAMENTO' => 'Captação - Fechamento e próximos passos',
        'STATE_C1_ENDERECO' => 'Captação - Endereço do imóvel',
        'STATE_C2_TIPO' => 'Captação - Tipo de imóvel',
        'STATE_C3_CARACTERISTICAS' => 'Captação - Características (quartos, vagas, área)',
        'STATE_C4_ESTADO' => 'Captação - Estado do imóvel',
        'STATE_C5_DOCUMENTACAO' => 'Captação - Documentação',
        'STATE_C6_URGENCIA' => 'Captação - Urgência',
        'STATE_C7_PRECO' => 'Captação - Preço desejado',
        'STATE_C8_FOTOS' => 'Captação - Fotos/link',
        'STATE_C_RESULT' => 'Captação - Análise e próximos passos',

        // Handoff e encerramento
        'STATE_HANDOFF' => 'Transferência para corretor humano',
        'STATE_SEM_CADASTRO' => 'Atendimento sem cadastro (sem LGPD)',
    ];

    /**
     * Transições de estado permitidas (origem => destinos possíveis)
     */
    public const TRANSITIONS = [
        'STATE_START' => ['STATE_LGPD'],
        'STATE_LGPD' => ['STATE_OBJETIVO', 'STATE_SEM_CADASTRO'],
        'STATE_OBJETIVO' => ['STATE_Q1_LOCAL', 'STATE_CAPTACAO_INICIO', 'STATE_C1_ENDERECO', 'STATE_SUPORTE_MENU', 'STATE_HANDOFF'],
        
        // Compra/Aluguel
        'STATE_Q1_LOCAL' => ['STATE_Q2_TIPO'],
        'STATE_Q2_TIPO' => ['STATE_Q3_QUARTOS'],
        'STATE_Q3_QUARTOS' => ['STATE_Q4_ORCAMENTO'],
        'STATE_Q4_ORCAMENTO' => ['STATE_Q5_PRIORIDADES'],
        'STATE_Q5_PRIORIDADES' => ['STATE_Q6_PRAZO'],
        'STATE_Q6_PRAZO' => ['STATE_Q7_DADOS_CONTATO'],
        'STATE_Q7_DADOS_CONTATO' => ['STATE_MATCH_RESULT'],
        'STATE_MATCH_RESULT' => ['STATE_REFINAR', 'STATE_VISITA_IMOVEL_ESCOLHA', 'STATE_PROPOSTA', 'STATE_SUPORTE_MENU', 'STATE_HANDOFF'],
        'STATE_REFINAR' => ['STATE_MATCH_RESULT', 'STATE_PROPOSTA', 'STATE_SUPORTE_MENU', 'STATE_HANDOFF'],

        // Agendamento de visita
        'STATE_VISITA_IMOVEL_ESCOLHA' => ['STATE_VISITA_DATA_HORA'],
        'STATE_VISITA_DATA_HORA' => ['STATE_VISITA_CONFIRMACAO'],
        'STATE_VISITA_CONFIRMACAO' => ['STATE_VISITA_POS', 'STATE_HANDOFF'],
        'STATE_VISITA_POS' => ['STATE_MATCH_RESULT', 'STATE_PROPOSTA', 'STATE_SUPORTE_MENU', 'STATE_HANDOFF'],

        // Proposta
        'STATE_PROPOSTA' => ['STATE_MATCH_RESULT', 'STATE_SIMULACAO', 'STATE_SUPORTE_MENU', 'STATE_HANDOFF'],

        // Simulação de Financiamento
        'STATE_SIMULACAO' => ['STATE_PROPOSTA', 'STATE_SUPORTE_MENU', 'STATE_HANDOFF'],

        // Suporte (locação/pós-contrato)
        'STATE_SUPORTE_MENU' => ['STATE_MANUTENCAO', 'STATE_HANDOFF'],
        'STATE_MANUTENCAO' => ['STATE_HANDOFF'],

        // Captação
        'STATE_CAPTACAO_INICIO' => ['STATE_CAPTACAO_DADOS_IMOVEL'],
        'STATE_CAPTACAO_DADOS_IMOVEL' => ['STATE_CAPTACAO_PRECO'],
        'STATE_CAPTACAO_PRECO' => ['STATE_CAPTACAO_MIDIA'],
        'STATE_CAPTACAO_MIDIA' => ['STATE_CAPTACAO_FECHAMENTO'],
        'STATE_CAPTACAO_FECHAMENTO' => ['STATE_SUPORTE_MENU', 'STATE_HANDOFF'],
        'STATE_C1_ENDERECO' => ['STATE_C2_TIPO'],
        'STATE_C2_TIPO' => ['STATE_C3_CARACTERISTICAS'],
        'STATE_C3_CARACTERISTICAS' => ['STATE_C4_ESTADO'],
        'STATE_C4_ESTADO' => ['STATE_C5_DOCUMENTACAO'],
        'STATE_C5_DOCUMENTACAO' => ['STATE_C6_URGENCIA'],
        'STATE_C6_URGENCIA' => ['STATE_C7_PRECO'],
        'STATE_C7_PRECO' => ['STATE_C8_FOTOS'],
        'STATE_C8_FOTOS' => ['STATE_C_RESULT'],
        'STATE_C_RESULT' => ['STATE_SUPORTE_MENU', 'STATE_HANDOFF'],

        // Atendimento sem cadastro
        'STATE_SEM_CADASTRO' => ['STATE_SUPORTE_MENU', 'STATE_HANDOFF'],

        // Handoff é terminal
        'STATE_HANDOFF' => [],
    ];

    /**
     * Prompts para cada estado
     */
    public const STATE_PROMPTS = [
        'STATE_START' => "SEMPRE comece com uma saudação educada e completa:\n\n\"Oi, bom dia! 👋\n\nSou o assistente virtual da Imobiliária California! 🏠\n\nAntes de começar, preciso da sua autorização para usar seus dados. Posso continuar?\"\n\nSempre seja educado, use emojis apropriados. Próximo: perguntar sobre LGPD.",

        'STATE_LGPD' => "Pergunte de forma educada: \"Posso usar seus dados pessoais para te enviar opções personalizadas e em conformidade com a LGPD?\"\n\nOfereça claramente:\n1️⃣ Sim, pode usar meus dados\n2️⃣ Não, prefiro sem cadastro\n\nSe aceitar (opção 1): agradeça e vá para STATE_OBJETIVO\nSe recusar (opção 2): vá para STATE_SEM_CADASTRO (entregar informações gerais + botão Falar com corretor)",

        'STATE_OBJETIVO' => "Pergunte de forma amigável: \"Ótimo! Como posso te ajudar hoje? 😊\"\n\nOfereça as opções com emojis:\n1️⃣ Comprar imóvel 🏠\n2️⃣ Alugar imóvel 🔑\n3️⃣ Vender imóvel 💰\n4️⃣ Anunciar para aluguel 📢\n5️⃣ Investimento 📈\n6️⃣ Falar com corretor 👤\n\nAguarde a escolha do usuário e sempre seja gentil e prestativo.",
    // Novo funil de Captação (preferencial)
    'STATE_CAPTACAO_INICIO' => "Vamos iniciar a captação do seu imóvel.\nPergunte: \"Em qual bairro fica o imóvel? Quer informar a rua?\"\nColeta em slots[bairro_imovel] e slots[endereco_imovel] (rua opcional).\nPróximo: STATE_CAPTACAO_DADOS_IMOVEL.",

    'STATE_CAPTACAO_DADOS_IMOVEL' => "Dados do imóvel.\nPergunte: \"Qual é o tipo de imóvel? Quantos quartos e vagas? Qual a metragem aproximada?\"\nOfereça opções para estado do imóvel (Novo/Usado/Precisa reforma) e documentação.\nColeta em slots[tipo_imovel], slots[quartos], slots[vagas], slots[area_total], slots[estado_imovel], slots[tem_documentacao_ok], slots[ocupacao_status].\nPróximo: STATE_CAPTACAO_PRECO.",

    'STATE_CAPTACAO_PRECO' => "Preço e encargos.\nPergunte: \"Qual é o preço desejado?\" Se \"não sei\", ofereça avaliação de mercado.\nPergunte também: \"Qual o valor de condomínio e IPTU?\" e \"Aceita permuta?\"\nColeta em slots[preco_desejado], slots[condominio_valor], slots[iptu_valor], slots[aceita_permutar].\nPróximo: STATE_CAPTACAO_MIDIA.",

    'STATE_CAPTACAO_MIDIA' => "Mídia e avaliação.\nPergunte: \"Você tem fotos ou um link?\"\nOfereça agendar avaliação: \"Qual dia e horário prefere para uma visita técnica?\"\nColeta em slots[fotos_link], slots[avaliacao_agendamento_dia], slots[avaliacao_agendamento_hora], slots[melhor_horario_visita_captacao].\nPróximo: STATE_CAPTACAO_FECHAMENTO.",

    'STATE_CAPTACAO_FECHAMENTO' => "Resumo e próximos passos.\nRecapitule: bairro/endereço, tipo, quartos, vagas, metragem, estado, documentação, ocupação, preço, condomínio/IPTU e mídia/agendamento.\nDiga: \"Vamos preparar uma avaliação de mercado e um plano de anúncio. Um corretor entrará em contato.\"\nPróximo: STATE_HANDOFF.",

        'STATE_Q1_LOCAL' => "Pergunta: \"Em qual cidade ou bairro você procura?\" Seja consultivo:\n- Ofereça 3-5 cidades/regiões principais (Centro, Zona Sul, Zona Norte, etc.)\n- Se usuário mencionar vários bairros, salve como lista em slots[bairro_regiao]\n- Se disser \"não sei\", ofereça: \"Qual é seu ponto de referência? (Perto do trabalho, da família, transporte público?)\"\nPróximo: STATE_Q2_TIPO",

        'STATE_Q2_TIPO' => "Pergunta: \"Qual tipo de imóvel você procura?\" Ofereça botões:\n- Apartamento → (pergunta opcional: \"Prefere com ou sem condomínio? Elevador?\")\n- Casa → (pergunta opcional: \"Aceita condomínio fechado?\")\n- Kitnet\n- Comercial\n- Terreno\nSalve em slots[tipo_imovel]. Próximo: STATE_Q3_QUARTOS",

        'STATE_Q3_QUARTOS' => "Pergunta: \"Quantos quartos você precisa?\" Ofereça botões:\n- 1 quarto\n- 2 quartos\n- 3 quartos\n- 4 ou mais\nSalve em slots[quartos]. Próximo: STATE_Q4_ORCAMENTO",

        'STATE_Q4_ORCAMENTO' => "Pergunta: \"Qual é sua faixa de valor?\" Seja específico:\nPara COMPRA: \"Qual é o valor mínimo e máximo que você pode investir?\"\nPara ALUGUEL: \"Qual é o valor máximo de aluguel? E condomínio?\"\nOfereça ranges: \"Menos de 500k\", \"500k-800k\", \"800k-1M\", \"1M+\"\nSalve em slots[faixa_valor_min] e slots[faixa_valor_max]. Próximo: STATE_Q5_PRIORIDADES",

        'STATE_Q5_PRIORIDADES' => "Pergunta: \"O que é indispensável para você?\" Ofereça tags (multi-seleção):\n- Vaga de garagem\n- Varanda/Sacada\n- Pet friendly\n- Suíte\n- Mobiliado\n- Perto do metrô\n- Lavanderia\n- Área de lazer\nSalve como lista em slots (criar campo tags_prioridades). Próximo: STATE_Q6_PRAZO",

        'STATE_Q6_PRAZO' => "Pergunta: \"Para quando você pretende mudar/fechar o negócio?\" Ofereça:\n- Urgente (até 1 mês)\n- 1-3 meses\n- 3-6 meses\n- Sem pressa\nSe \"urgente\": nota interna para priorizar agendamento. Salve em slots[prazo_mudanca]. Próximo: STATE_Q7_DADOS_CONTATO",

        'STATE_Q7_DADOS_CONTATO' => "Pergunta: \"Para te enviar as melhores opções, qual seu nome e WhatsApp?\" Se resistir, ofereça: \"Posso mandar 3 opções aqui mesmo e depois você me diz se quer visitar.\" Coleta em slots[nome], slots[telefone_whatsapp], slots[email]. Próximo: STATE_MATCH_RESULT",

        'STATE_MATCH_RESULT' => "Entregar 3-8 imóveis em format de CARDS com:\n- 🏠 Título/Descrição\n- 📍 Bairro\n- 💰 Preço\n- 🛏️ Quartos\n- 🚗 Vagas\n- 💵 Condomínio (se houver)\nCTAs: \"Ver fotos\", \"Ver no mapa\", \"Agendar visita\", \"Falar com corretor\"\nPergunta: \"Gostou de algum? Quer ajustar algo? (bairro, valor, quartos)\" Aguarde resposta.",

        'STATE_REFINAR' => "Interprete o filtro mencionado (bairro, valor, quartos, etc.) e ATUALIZE o slot correspondente. Então volte para STATE_MATCH_RESULT com resultados refinados. Pergunta: \"Melhor assim? Quer agendar uma visita?\"\nSe satisfeito: ofereça agendar ou falar com corretor.",

        // Agendamento de visita (profissional)
        'STATE_VISITA_IMOVEL_ESCOLHA' => "Agendamento de visita (passo 1/4) – Escolha do imóvel.\nSe o usuário está visualizando um imóvel específico (ex.: acabou de pedir detalhes de um card), pergunte de forma direta: \"Perfeito. Quer agendar visita para este imóvel?\"\nSe não estiver claro qual imóvel: pergunte \"Qual imóvel você quer visitar?\" e explique: \"Pode enviar o código (ex.: #123) ou escolher um da lista acima.\"\nAo receber a escolha, salve em slots[imovel_codigo_escolhido] (apenas o número/código). Próximo: STATE_VISITA_DATA_HORA.",

        'STATE_VISITA_DATA_HORA' => "Agendamento de visita (passo 2/4) – Data e horário.\nPergunte: \"Qual dia e horário você prefere?\"\nOfereça 3 sugestões (ajuste dinamicamente): \"Hoje 18h / Amanhã 10h / Sábado 11h\".\nSe o horário indicado for fora do comercial (antes de 09:00, depois de 19:00 ou domingos), sugira alternativas no horário comercial.\nSalve data e hora em slots[visita_data] e slots[visita_hora] (ou slots[visita_datetime] quando responder combinado). Próximo: STATE_VISITA_CONFIRMACAO.",

        'STATE_VISITA_CONFIRMACAO' => "Agendamento de visita (passo 3/4) – Confirmação.\nRecapitule claramente e peça confirmação:\n- Imóvel (código e título se disponível)\n- Endereço aproximado / ponto de encontro (coletar em slots[endereco_aproximado] ou slots[ponto_encontro])\n- Dia e horário (slots[visita_data] e slots[visita_hora])\n- Nome e telefone (slots[nome], slots[telefone_whatsapp])\nEnvie a política: \"Leve documento, chegue 10 min antes.\"\nPergunte: \"Posso te lembrar 2h antes? (Sim/Não)\" e salve em slots[lembrar_visita_2h].\nSomente após confirmação final do usuário marque slots[visita_confirmada] = \"sim\". Próximo: STATE_VISITA_POS.",

        'STATE_VISITA_POS' => "Pós-visita (passo 4/4).\nPergunte: \"O que achou? (Gostei / Talvez / Não gostei)\"\nSe responder \"Não gostei\": pergunte o motivo (Preço / Localização / Estado do imóvel / Outros) e salve em slots[pos_visita_motivo]; em seguida, AJUSTE a busca (refinar filtros) e volte para STATE_MATCH_RESULT com novas opções.\nSe \"Gostei\": ofereça avançar para proposta/negociação ou falar com corretor (STATE_HANDOFF).\nSe \"Talvez\": ofereça mais 2-3 similares e mantenha acompanhamento.",

        'STATE_PROPOSTA' => "Proposta / Oferta - Fazer oferta para imóvel.\nPergunte: \"Você quer fazer proposta para qual imóvel?\" Se necessário, peça o código (ex.: #123).\nDepois de identificado, pergunte os dados MÍNIMOS:\n1. \"Qual é seu valor proposto?\"\n2. \"Como prefere pagar? (Financiamento / À vista / FGTS / Combinado)\"\n3. \"Quantos dias o vendedor tem para responder? (3 / 5 / 7 / 10 dias)\"\nSalve em slots[imovel_proposta_codigo], slots[valor_proposto], slots[forma_pagamento], slots[prazo_resposta_dias].\nSe escolher FINANCIAMENTO: pergunte \"Você já tem aprovação de crédito?\"\n- Se SIM: \"Ótimo! Posso guardar sua aprovação para acelerar.\"\n- Se NÃO: \"Posso fazer uma SIMULAÇÃO grátis com você para você saber a capacidade e enviar uma proposta mais realista.\" Ofereça STATE_SIMULACAO antes de enviar proposta.\nAo final: \"Vou encaminhar sua proposta ao corretor responsável e você recebe a resposta em [prazo_dias] dias. Você será avisado por WhatsApp.\"\nPróximo: STATE_HANDOFF.",

        'STATE_SIMULACAO' => "Simulação de Financiamento.\nPergunte em ordem:\n1. \"Qual é o valor do imóvel que você está considerando?\"\n2. \"Quanto você tem disponível para entrada?\"\n3. \"Qual é sua renda mensal aproximada? (ou faixa: 3000-5000, 5000-10000, etc.)\"\n4. \"Qual prazo você prefere? (20 / 30 / 35 anos)\"\nSalve em slots[valor_imovel_simulacao], slots[entrada_disponivel_simulacao], slots[renda_faixa_simulacao], slots[prazo_anos_simulacao].\nAPÓS COLETAR TUDO: Calcule a simulação usando SimuladorFinanciamento e mostre:\n- Valor a financiar\n- Parcela mensal aproximada\n- Comparação com renda (%)\n- Recomendações (aumentar entrada, aumentar prazo, etc.)\nAO FINAL: \"Quer que um especialista te ligue para simular certinho?\"\n→ SIM: STATE_HANDOFF com callback\n→ NÃO: volta para STATE_PROPOSTA ou STATE_MATCH_RESULT.",

        // Suporte
        'STATE_SUPORTE_MENU' => "Suporte de locação e pós-contrato.\nOfereça opções claras:\n- Segunda via de boleto\n- Status de proposta/contrato\n- Solicitar manutenção (reparo)\n- Falar com atendimento humano\nSe escolher manutenção: siga para STATE_MANUTENCAO.",

        'STATE_MANUTENCAO' => "Manutenção / Reparo. Pergunte UMA COISA por vez:\n1. Endereço/unidade do problema (ex.: Rua X, Apto Y, Bloco Z) → slots[suporte_endereco_unidade]\n2. Tipo de problema (hidráulica/vazamento, elétrica/chuveiro/tomada, gás, porta/janela, eletrodoméstico, outros) → slots[suporte_tipo_problema]\n3. Urgência (Alta – risco imediato, Média, Baixa) → slots[suporte_urgencia]\n4. Tem foto/vídeo/link? (opcional) → slots[suporte_midia_link]\nRegra de segurança: se for água/vazamento → orientar fechar registro; se for elétrica → orientar desligar disjuntor; se for gás → orientar fechar registro e ventilar.\nApós coletar os dados mínimos, informe que o chamado será aberto com prazo estimado (sem prometer datas exatas) e siga para handoff.",

        'STATE_C1_ENDERECO' => "Pergunta: \"Qual é o endereço do seu imóvel?\" Coleta em slots[endereco_imovel]. Peça: \"Qual bairro? E a rua?\" Se não quiser compartilhar rua, bairro é suficiente. Próximo: STATE_C2_TIPO",

        'STATE_C2_TIPO' => "Pergunta: \"Qual é o tipo de imóvel?\" Ofereça: Apartamento, Casa, Comercial, Terreno, Kitnet. Salve em slots[tipo_imovel]. Próximo: STATE_C3_CARACTERISTICAS",

        'STATE_C3_CARACTERISTICAS' => "Pergunta: \"Quantos quartos, vagas e qual é a metragem?\" Coleta em slots[quartos], slots[vagas], slots[area_total]. Próximo: STATE_C4_ESTADO",

        'STATE_C4_ESTADO' => "Pergunta: \"Em que estado está o imóvel?\" Ofereça: Novo, Usado (bem conservado), Usado (precisa reforma), Para reforma. Salve em slots[estado_imovel]. Próximo: STATE_C5_DOCUMENTACAO",

        'STATE_C5_DOCUMENTACAO' => "Pergunta: \"A documentação está em dia?\" Ofereça: Sim, Não, Não sei. Se Não/Não sei, ofereça ajuda: \"Sem problema, nossa equipe ajuda com documentação\". Salve em slots[tem_documentacao_ok]. Próximo: STATE_C6_URGENCIA",

        'STATE_C6_URGENCIA' => "Pergunta: \"Qual é sua urgência em vender/alugar?\" Ofereça: Alta (até 30 dias), Média (1-3 meses), Baixa (sem pressa). Salve em slots[urgencia_venda_locacao]. Próximo: STATE_C7_PRECO",

        'STATE_C7_PRECO' => "Pergunta: \"Qual é seu preço desejado?\" Se responder \"não sei\", ofereça: \"Vou fazer uma análise de mercado grátis baseado no seu imóvel.\" Salve em slots[preco_desejado]. Próximo: STATE_C8_FOTOS",

        'STATE_C8_FOTOS' => "Pergunta: \"Tem fotos do imóvel? Você pode mandar um link ou WhatsApp?\" (Opcional) Salve em slots[fotos_link]. Próximo: STATE_C_RESULT",

        'STATE_C_RESULT' => "Resuma o imóvel (endereço, tipo, quartos, metragem, estado, preço desejado). Ofereça:\n\"Vou fazer uma análise de mercado de HOJE e entrar em contato em até 24h com avaliação e opções de venda/aluguel.\"\nOfereça: \"Falar com corretor AGORA\" ou \"Aguardar análise (até 24h)\".\nPróximo: STATE_HANDOFF (se falar com corretor) ou encerramento",

        'STATE_SEM_CADASTRO' => "Diga: \"Sem problema! Posso te mostrar algumas opções gerais aqui.\" Ofereça 2-3 imóveis genéricos + CTA: \"Para opções personalizadas, fale com um corretor.\" Próximo: STATE_HANDOFF",

        'STATE_HANDOFF' => "Prepare transição para humano:\n\"Um de nossos corretores especializados vai te contatar em breve. Seu atendimento foi registrado e vamos dar continuidade pessoalmente.\"\nTransfira todos os dados da conversa (slots) para o corretor.\nEstado terminal.",
    ];

    /**
     * Validar se uma transição é permitida
     *
     * @param string $estadoAtual
     * @param string $proximoEstado
     * @return bool
     */
    public static function isValidTransition(string $estadoAtual, string $proximoEstado): bool
    {
        return in_array($proximoEstado, self::TRANSITIONS[$estadoAtual] ?? []);
    }

    /**
     * Obter descrição do estado
     *
     * @param string $estado
     * @return string
     */
    public static function describe(string $estado): string
    {
        return self::STATES[$estado] ?? 'Estado desconhecido';
    }

    /**
     * Obter prompt para um estado
     *
     * @param string $estado
     * @return string
     */
    public static function getPrompt(string $estado): string
    {
        return self::STATE_PROMPTS[$estado] ?? 'Estado desconhecido.';
    }

    /**
     * Detectar próximo estado baseado em intent e estado atual
     *
     * @param string $estadoAtual
     * @param string $intent
     * @param string|null $objetivo
     * @return string|null Próximo estado válido ou null se não houver transição
     */
    public static function detectNextState(string $estadoAtual, string $intent, ?string $objetivo = null): ?string
    {
        $estadosPermitidos = self::TRANSITIONS[$estadoAtual] ?? [];

        // Em caso de intenção desconhecida, não avançar automaticamente
        if ($intent === 'indefinido') {
            return null;
        }

        // Em STATE_START, saudação leva direto para LGPD
        if ($estadoAtual === 'STATE_START' && $intent === 'saudacao') {
            return 'STATE_LGPD';
        }

        // Se estamos em STATE_OBJETIVO, usar intent para decidir
        if ($estadoAtual === 'STATE_OBJETIVO') {
            if (in_array('comprar_imovel', [$intent]) || in_array('alugar_imovel', [$intent]) || in_array('investimento', [$intent])) {
                return 'STATE_Q1_LOCAL';
            } elseif (in_array($intent, ['vender_imovel', 'anunciar_para_alugar'])) {
                return 'STATE_CAPTACAO_INICIO';
            } elseif ($intent === 'falar_com_corretor') {
                return 'STATE_HANDOFF';
            }
        }

        // Se estamos em STATE_LGPD, verificar se foi negativa
        if ($estadoAtual === 'STATE_LGPD') {
            if ($intent === 'negativa_sair') {
                return 'STATE_SEM_CADASTRO';
            }
            return 'STATE_OBJETIVO';
        }

        // Agendar visita a partir dos resultados/refino
        if (in_array($estadoAtual, ['STATE_MATCH_RESULT', 'STATE_REFINAR']) && $intent === 'agendar_visita') {
            if (in_array('STATE_VISITA_IMOVEL_ESCOLHA', $estadosPermitidos)) {
                return 'STATE_VISITA_IMOVEL_ESCOLHA';
            }
        }

        // Fazer proposta a partir dos resultados/refino/pós-visita
        if (in_array($estadoAtual, ['STATE_MATCH_RESULT', 'STATE_REFINAR', 'STATE_VISITA_POS']) && $intent === 'fazer_proposta') {
            if (in_array('STATE_PROPOSTA', $estadosPermitidos)) {
                return 'STATE_PROPOSTA';
            }
        }

        // Simulação a partir de proposta
        if ($estadoAtual === 'STATE_PROPOSTA' && $intent === 'simulacao_financiamento') {
            if (in_array('STATE_SIMULACAO', $estadosPermitidos)) {
                return 'STATE_SIMULACAO';
            }
        }

        // Suporte – menu a partir de diversos estados
        if (in_array($estadoAtual, [
            'STATE_OBJETIVO','STATE_MATCH_RESULT','STATE_REFINAR','STATE_VISITA_POS','STATE_PROPOSTA','STATE_SIMULACAO','STATE_CAPTACAO_FECHAMENTO','STATE_C_RESULT','STATE_SEM_CADASTRO'
        ]) && $intent === 'status_atendimento') {
            if (in_array('STATE_SUPORTE_MENU', $estadosPermitidos)) {
                return 'STATE_SUPORTE_MENU';
            }
        }

        // Suporte – manutenção direta
        if (in_array($estadoAtual, [
            'STATE_OBJETIVO','STATE_MATCH_RESULT','STATE_REFINAR','STATE_VISITA_POS','STATE_PROPOSTA','STATE_SIMULACAO','STATE_CAPTACAO_FECHAMENTO','STATE_C_RESULT','STATE_SEM_CADASTRO','STATE_SUPORTE_MENU'
        ]) && $intent === 'reclamacao_manutencao') {
            if (in_array('STATE_MANUTENCAO', $estadosPermitidos)) {
                return 'STATE_MANUTENCAO';
            }
        }

        // Durante os passos de agendamento, não avançar automaticamente sem confirmação explícita
        if (in_array($estadoAtual, [
            'STATE_VISITA_IMOVEL_ESCOLHA',
            'STATE_VISITA_DATA_HORA',
            'STATE_VISITA_CONFIRMACAO',
            'STATE_VISITA_POS',
        ])) {
            return null; // espere coleta de dados/confirmacao no estado atual
        }

        // Durante a proposta, não avançar automaticamente sem confirmação explícita
        if ($estadoAtual === 'STATE_PROPOSTA') {
            return null; // espere coleta de dados/confirmacao no estado atual
        }

        // Durante a simulação, não avançar automaticamente sem confirmação explícita
        if ($estadoAtual === 'STATE_SIMULACAO') {
            return null; // espere coleta de dados e cálculo no estado atual
        }

        // Durante manutenção/suporte, não avançar automaticamente
        if ($estadoAtual === 'STATE_MANUTENCAO') {
            return null; // coleta e abertura de chamado no estado atual
        }

        // Para outros estados, seguir a sequência linear
        return $estadosPermitidos[0] ?? null;
    }

    /**
     * Registrar transição de estado no histórico
     *
     * @param array $historico
     * @param string $estadoAnterior
     * @param string $estadoNovo
     * @return array Histórico atualizado
     */
    public static function registerTransition(array $historico, string $estadoAnterior, string $estadoNovo): array
    {
        $historico[] = [
            'de' => $estadoAnterior,
            'para' => $estadoNovo,
            'em' => now()->toIso8601String(),
        ];
        return $historico;
    }
}
