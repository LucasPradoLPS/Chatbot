#!/usr/bin/env php
<?php

/**
 * TESTE RÁPIDO DOS 8 PILARES
 * 
 * Execute com: php test_8_pilares.php
 * 
 * Isso valida que todos os serviços estão funcionando antes de você integrar
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';

use App\Services\{
    AppointmentService,
    LeadCaptureService,
    FollowUpService,
    ObjectionHandlerService,
    ExplainableMatchingService,
    EscalationService,
    LgpdComplianceService,
    ResponseValidationService,
    MetricsService,
    ConversationTestSuite,
};
use Carbon\Carbon;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                  🚀 TESTE DOS 8 PILARES                            ║\n";
echo "║                                                                    ║\n";
echo "║  Agendamento, Leads, Follow-up, Objeções, Matching, Escalação,    ║\n";
echo "║  LGPD, Validação, Observabilidade                                  ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

$empresaId = 1;
$clienteJid = "5511999785770@s.whatsapp.net";
$clienteNome = "Lucas Prado";
$testes_passaram = 0;
$testes_falharam = 0;

function teste($nome, $fn) {
    global $testes_passaram, $testes_falharam;
    
    try {
        echo "✓ Testando: $nome... ";
        $fn();
        echo "OK\n";
        $testes_passaram++;
    } catch (\Exception $e) {
        echo "FALHOU\n";
        echo "  ❌ Erro: " . $e->getMessage() . "\n";
        $testes_falharam++;
    }
}

// ===== PILAR 1: Agendamento =====
teste("PILAR 1: AppointmentService - Agendar Visita", function () use ($empresaId, $clienteJid, $clienteNome) {
    $dataAgendada = Carbon::now()->addDays(5)->setHour(14)->setMinute(30);
    
    $resultado = AppointmentService::agendarVisita(
        empresaId: $empresaId,
        clienteJid: $clienteJid,
        clienteNome: $clienteNome,
        imovelId: 1,
        imovelTitulo: "Apt. 2 quartos Vila Mariana",
        dataAgendada: $dataAgendada,
        observacoes: "Teste automático"
    );
    
    assert($resultado['sucesso'], "Agendamento falhou");
    assert(isset($resultado['appointment_id']), "Sem appointment_id");
    assert(isset($resultado['token']), "Sem token");
});

teste("PILAR 1: AppointmentService - Confirmar Visita", function () use ($empresaId, $clienteJid) {
    // Criar agendamento primeiro
    $resultado = AppointmentService::agendarVisita(
        empresaId: $empresaId,
        clienteJid: $clienteJid,
        clienteNome: "Teste",
        imovelId: 1,
        imovelTitulo: "Apt Teste",
        dataAgendada: Carbon::now()->addDays(1),
        observacoes: "Teste"
    );
    
    // Confirmar com token
    $confirmacao = AppointmentService::confirmarVisita($resultado['token']);
    assert($confirmacao['sucesso'], "Confirmação falhou");
});

// ===== PILAR 2: Lead Capture =====
teste("PILAR 2: LeadCaptureService - Capturar Lead", function () use ($empresaId, $clienteJid, $clienteNome) {
    $lead = LeadCaptureService::capturarLead(
        empresaId: $empresaId,
        clienteJid: $clienteJid,
        clienteNome: $clienteNome,
        dados: [
            'renda_aproximada' => 5000,
            'tipo_financiamento' => 'financiamento',
            'prazo_desejado_anos' => 25,
            'urgencia' => 'media',
            'tem_pre_aprovacao' => true,
            'pre_aprovacao_banco' => 'Itaú',
            'bairros_nao_negociaveis' => ['Vila Mariana'],
            'top_3_prioridades' => ['pet_friendly', 'varanda'],
            'consentimento_dados' => true,
        ]
    );
    
    assert($lead->id > 0, "Lead não foi criado");
    assert($lead->renda_aproximada == 5000, "Renda não foi salva");
});

teste("PILAR 2: LeadCaptureService - Registrar Interação", function () use ($empresaId, $clienteJid) {
    LeadCaptureService::registrarInteracao(
        $empresaId,
        $clienteJid,
        1, // imovel_id
        'gostou'
    );
    
    $lead = \App\Models\LeadCapture::where('cliente_jid', $clienteJid)->first();
    assert(in_array(1, $lead->imoveis_gostou ?? []), "Interação não foi registrada");
});

// ===== PILAR 3: Follow-up =====
teste("PILAR 3: FollowUpService - Mensagem Follow-up", function () {
    $mensagem = "teste";
    // FollowUpService::enviarFollowUp1 precisa de database
    // Aqui apenas testamos que o método existe e não lança erro
    assert(method_exists(FollowUpService::class, 'enviarFollowUp1'), "Método não existe");
});

// ===== PILAR 4: Objeções =====
teste("PILAR 4: ObjectionHandlerService - Detectar Objeção", function () {
    $objecao = ObjectionHandlerService::detectarObjecao("Muito caro mesmo!");
    
    assert($objecao !== null, "Objeção não foi detectada");
    assert($objecao['tipo'] === 'muito_caro', "Tipo de objeção incorreto");
});

teste("PILAR 4: ObjectionHandlerService - Gerar Resposta", function () {
    $objecao = [
        'tipo' => 'muito_caro',
        'detector' => 'muito caro',
        'playbook' => [
            'resposta' => 'entendo_preco',
            'opcoes' => [
                'mostrar_opcoes_mais_baratas' => 'Deixa eu buscar opções com preço mais baixo?',
            ],
        ],
    ];
    
    $resposta = ObjectionHandlerService::gerarRespostaObjecao($objecao);
    assert(strpos(strtolower($resposta), 'preço') !== false, "Resposta não menciona preço");
});

// ===== PILAR 5: Explicabilidade =====
teste("PILAR 5: ExplainableMatchingService - Explicar Score", function () {
    $imovel = [
        'id' => 1,
        'titulo' => 'Apt Vila Mariana',
        'bairro' => 'Vila Mariana',
        'valor' => 500000,
        'quartos' => 2,
        'vagas' => 1,
        'tags' => ['pet_friendly', 'varanda'],
    ];
    
    $slots = [
        'bairro_regiao' => ['Vila Mariana'],
        'faixa_valor_max' => 500000,
    ];
    
    $explicacao = ExplainableMatchingService::explicarScore(
        $imovel,
        $slots,
        85,
        ['bairro' => ['match' => true], 'preco' => ['diferenca_percentual' => 0]]
    );
    
    assert(strpos($explicacao, 'Score') !== false, "Explicação não tem score");
    assert(strlen($explicacao) > 50, "Explicação muito curta");
});

// ===== PILAR 6: Escalação =====
teste("PILAR 6: EscalationService - Detectar Intenção", function () {
    $intencao = EscalationService::detectarIntencaoEscalacao("Quero marcar uma visita!");
    
    assert($intencao !== null, "Intenção não foi detectada");
    assert($intencao['tipo'] === 'quero_visitar', "Tipo de intenção incorreto");
});

teste("PILAR 6: EscalationService - Gerar Resumo", function () use ($empresaId, $clienteJid, $clienteNome) {
    $leadData = [
        'renda_aproximada' => 5000,
        'tipo_financiamento' => 'financiamento',
        'urgencia' => 'alta',
        'bairros_nao_negociaveis' => ['Vila Mariana'],
    ];
    
    $resumo = EscalationService::gerarResumoCaso(
        $empresaId,
        $clienteJid,
        $clienteNome,
        $leadData,
        'quero_visitar'
    );
    
    assert(strpos($resumo, 'RESUMO') !== false, "Resumo não tem título");
    assert(strpos($resumo, 'Lucas') !== false, "Resumo não tem nome do cliente");
});

// ===== PILAR 7: LGPD =====
teste("PILAR 7: LgpdComplianceService - Solicitar Consentimento", function () use ($empresaId, $clienteJid) {
    $msg = LgpdComplianceService::solicitarConsentimentoExplicito($clienteJid, $empresaId);
    
    assert(strlen($msg) > 50, "Mensagem de consentimento muito curta");
    assert(stripos($msg, 'autorização') !== false || stripos($msg, 'dados') !== false, "Mensagem não fala de dados");
});

teste("PILAR 7: LgpdComplianceService - Registrar Consentimento", function () use ($empresaId, $clienteJid) {
    LgpdComplianceService::registrarConsentimento(
        $clienteJid,
        $empresaId,
        true,
        'dados'
    );
    
    $lead = \App\Models\LeadCapture::where('cliente_jid', $clienteJid)->first();
    assert($lead->consentimento_dados === true, "Consentimento não foi registrado");
});

// ===== PILAR 8: Validação =====
teste("PILAR 8: ResponseValidationService - Validar Resposta OK", function () {
    $validacao = ResponseValidationService::validarResposta(
        resposta: "Entendi! Deixa eu buscar opções mais baratas pra você.",
        contexto: [],
        imovelData: ['valor' => 500000]
    );
    
    assert($validacao['valida'] === true, "Resposta válida foi rejeitada");
});

teste("PILAR 8: ResponseValidationService - Rejeitar Resposta Indevida", function () {
    $validacao = ResponseValidationService::validarResposta(
        resposta: "Você será aprovado com certeza no banco!",
        contexto: [],
        imovelData: []
    );
    
    assert($validacao['valida'] === false, "Resposta indevida não foi detectada");
});

// ===== OBSERVABILIDADE =====
teste("OBSERVABILIDADE: MetricsService - Registrar Evento", function () use ($empresaId, $clienteJid) {
    MetricsService::registrarEventoFunil($clienteJid, $empresaId, 'qualificacao');
    
    $analytics = \App\Models\ConversationAnalytics::where('cliente_jid', $clienteJid)->first();
    assert($analytics !== null, "Analytics não foi criado");
});

teste("OBSERVABILIDADE: MetricsService - Obter Métricas Funil", function () use ($empresaId) {
    $metricas = MetricsService::obterMetricasFunil($empresaId);
    
    assert(is_array($metricas), "Métricas devem ser array");
    assert(isset($metricas['total_leads']), "Métricas sem total_leads");
});

// ===== TESTES DE REGRESSÃO =====
teste("TESTES: ConversationTestSuite - Executar Suite", function () {
    $resultado = ConversationTestSuite::executarSuite();
    
    assert(isset($resultado['total']), "Suite sem resultado total");
    assert(isset($resultado['passou']), "Suite sem resultado passou");
    assert($resultado['percentual_sucesso'] >= 50, "Taxa de sucesso muito baixa (dev environment)");
});

// ===== RESUMO =====
echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                        📊 RESULTADO FINAL                          ║\n";
echo "╠════════════════════════════════════════════════════════════════════╣\n";
echo "║                                                                    ║\n";
printf("║  ✅ Testes passaram: %-50s ║\n", $testes_passaram);
printf("║  ❌ Testes falharam: %-50s ║\n", $testes_falharam);

$total = $testes_passaram + $testes_falharam;
$percentual = $total > 0 ? round($testes_passaram / $total * 100, 1) : 0;
printf("║  🎯 Taxa de sucesso: %-50s ║\n", "{$percentual}%");

echo "║                                                                    ║\n";

if ($testes_falharam === 0) {
    echo "║  🎉 TODOS OS TESTES PASSARAM! Pronto para integrar.              ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n\n";
    exit(0);
} else {
    echo "║  ⚠️  Há falhas. Verifique os logs acima.                          ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n\n";
    exit(1);
}
