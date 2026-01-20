# 🔧 GUIA DE INTEGRAÇÃO - Melhorias de Conversão + Comercial

## 📋 Resumo das Integrações Necessárias

Este documento mostra como integrar os 8 pilares de melhoria no job `ProcessWhatsappMessage.php`.

---

## 1️⃣ **Agendamento de Visitas** (AppointmentService)

### Local de Integração
Na etapa `resultado_busca` quando cliente clica "Quero visitar"

```php
// No ProcessWhatsappMessage.php, dentro do handle():
use App\Services\AppointmentService;

// Quando usuário diz "Quero visitar este"
if ($mensagemDetetaIntencao === 'quero_visitar') {
    // Solicitar data/hora
    $mensagem = "📅 Que data você prefere?\nEx: 25/01 às 14h";
    
    // Depois de receber resposta:
    $result = AppointmentService::agendarVisita(
        empresaId: $empresaId,
        clienteJid: $clienteId,
        clienteNome: $pushName,
        imovelId: $imovelSelecionadoId,
        imovelTitulo: $imovelTitulo,
        dataAgendada: $dataParseada, // Carbon::parse($mensagem)
        observacoes: "Agendado via WhatsApp"
    );
    
    if ($result['sucesso']) {
        EvolutionApiService::enviarMensagem($clienteId, $result['mensagem']);
        
        // Registrar no analytics
        MetricsService::registrarEventoFunil($clienteId, $empresaId, 'visita');
    }
}
```

---

## 2️⃣ **Captação de Lead Avançada** (LeadCaptureService)

### Local de Integração
Durante a etapa `qualificacao`

```php
use App\Services\LeadCaptureService;

// Quando coletar dados do cliente:
$lead = LeadCaptureService::capturarLead(
    empresaId: $empresaId,
    clienteJid: $clienteId,
    clienteNome: $pushName,
    dados: [
        'renda_aproximada' => $renda, // "5000" ou "5 mil"
        'tipo_financiamento' => $financiamento, // "financiamento"
        'prazo_desejado_anos' => 25,
        'urgencia' => 'alta', // alta/media/baixa
        'tem_pre_aprovacao' => true,
        'pre_aprovacao_valor' => '400000',
        'pre_aprovacao_banco' => 'Itaú',
        'cidade_principal' => 'São Paulo',
        'bairros_nao_negociaveis' => ['Vila Mariana', 'Pinheiros'],
        'top_3_prioridades' => ['pet_friendly', 'varanda', '2_quartos'],
        'consentimento_dados' => true,
    ]
);

// Registrar interação quando cliente gosta/descarta um imóvel
LeadCaptureService::registrarInteracao(
    $empresaId, 
    $clienteId, 
    $imovelId, 
    'gostou' // ou 'descartou'
);

// Registrar preferência aprendida
LeadCaptureService::registrarPreferenciaDescartada(
    $empresaId,
    $clienteId,
    'terreo' // "não quero térreo"
);
```

---

## 3️⃣ **Follow-up Automático** (FollowUpService)

### Local de Integração
Como scheduled job

```php
// No Kernel.php (schedule):
$schedule->job(new ProcessFollowUpAutomaticly($empresaId))
    ->everyTwoHours();

$schedule->job(new SendAppointmentReminders())
    ->dailyAt('09:00'); // 24h antes do agendamento
```

---

## 4️⃣ **Tratamento de Objeções** (ObjectionHandlerService)

### Local de Integração
Dentro do ProcessWhatsappMessage, após receber mensagem

```php
use App\Services\ObjectionHandlerService;

// Detectar objeção na mensagem
$objecao = ObjectionHandlerService::detectarObjecao($mensagemRecebida);

if ($objecao) {
    // Gerar resposta personalizada
    $resposta = ObjectionHandlerService::gerarRespostaObjecao($objecao);
    EvolutionApiService::enviarMensagem($clienteId, $resposta);
    
    // Verificar se requer escalação
    if (ObjectionHandlerService::sugerirEscalacao($objecao['tipo'])) {
        // Escalar para humano
        EscalationService::escalar(
            $empresaId,
            $clienteId,
            $pushName,
            $leadData,
            'objecao_' . $objecao['tipo']
        );
    }
    
    // Registrar para analytics
    MetricsService::registrarEventoFunil($clienteId, $empresaId, 'objecao_tratada');
}
```

---

## 5️⃣ **Explicabilidade do Matching** (ExplainableMatchingService)

### Local de Integração
Ao apresentar imóvel recomendado

```php
use App\Services\ExplainableMatchingService;

// Calcular score do imóvel
$scoreDetalhes = MatchingEngine::calculateScore($imovel, $slots);

// Gerar explicação amigável
$explicacao = ExplainableMatchingService::explicarScore(
    $imovel,
    $slots,
    $scoreDetalhes['score'],
    $scoreDetalhes['detalhes']
);

// Enviar ao cliente
EvolutionApiService::enviarMensagem($clienteId, $explicacao);
```

---

## 6️⃣ **Human Handoff Inteligente** (EscalationService)

### Local de Integração
Detectar sinais de intenção forte

```php
use App\Services\EscalationService;

// Detectar intenção de escalação
$intencao = EscalationService::detectarIntencaoEscalacao($mensagemRecebida);

if ($intencao) {
    // Gerar resumo do caso
    $resumo = EscalationService::gerarResumoCaso(
        $empresaId,
        $clienteId,
        $pushName,
        $leadData,
        $intencao['tipo']
    );
    
    // Escalar
    $escalacao = EscalationService::escalar(
        $empresaId,
        $clienteId,
        $pushName,
        $leadData,
        $intencao['tipo'],
        $intencao['prioridade']
    );
    
    if ($escalacao['escalado']) {
        // Enviar mensagem ao cliente
        EvolutionApiService::enviarMensagem(
            $clienteId, 
            $escalacao['mensagem_cliente']
        );
        
        // Notificar corretor (via webhook/API/email)
        // TODO: Implementar integração com CRM
        
        Log::info("Caso escalado", [
            'cliente_id' => $clienteId,
            'tipo' => $intencao['tipo'],
            'resumo' => $resumo,
        ]);
    }
}
```

---

## 7️⃣ **LGPD Compliance** (LgpdComplianceService)

### Local de Integração
Inicio da conversa e ao coletar dados

```php
use App\Services\LgpdComplianceService;

// No início, solicitar consentimento
$mensagemConsentimento = LgpdComplianceService::solicitarConsentimentoExplicito(
    $clienteId,
    $empresaId
);
EvolutionApiService::enviarMensagem($clienteId, $mensagemConsentimento);

// Quando cliente autoriza
if ($escolheuAutorizar) {
    LgpdComplianceService::registrarConsentimento(
        $clienteId,
        $empresaId,
        true, // autorizou
        'dados'
    );
}

// Processar solicitação de exportação de dados
if ($mensagem === 'quero meus dados') {
    $export = LgpdComplianceService::exportarDadosCliente($clienteId, $empresaId);
    // Enviar arquivo JSON ao cliente
}

// Processar solicitação de deleção
if ($mensagem === 'deletar meus dados') {
    $resultado = LgpdComplianceService::deletarDadosCliente(
        $clienteId,
        $empresaId,
        'solicitacao_cliente'
    );
    EvolutionApiService::enviarMensagem($clienteId, $resultado['mensagem']);
}
```

---

## 8️⃣ **Validação de Respostas** (ResponseValidationService)

### Local de Integração
Antes de enviar qualquer resposta

```php
use App\Services\ResponseValidationService;

// Antes de enviar resposta ao cliente
$validacao = ResponseValidationService::validarResposta(
    resposta: $respostaGerada,
    contexto: [
        'bairros_nao_negociaveis' => $lead->bairros_nao_negociaveis,
        'num_opcoes_apresentadas' => $numOpcoes,
    ],
    imovelData: $imovel
);

if (!$validacao['pode_enviar']) {
    Log::error("Resposta bloqueada por validação", [
        'cliente_id' => $clienteId,
        'erros' => $validacao['erros'],
    ]);
    
    // Enviar resposta genérica em vez de arriscada
    EvolutionApiService::enviarMensagem(
        $clienteId,
        "Desculpe, tive um problema técnico. Um corretor vai te contatar em breve."
    );
    return;
}

if (!empty($validacao['avisos'])) {
    Log::warning("Resposta enviada com avisos", $validacao['avisos']);
}

// Resposta OK, enviar normalmente
EvolutionApiService::enviarMensagem($clienteId, $respostaGerada);

// Registrar validação (auditoria)
ResponseValidationService::registrarValidacao($clienteId, $respostaGerada, $validacao);
```

---

## 📊 **Observabilidade** (MetricsService)

### Local de Integração
Ao longo de toda a conversa

```php
use App\Services\MetricsService;

// Registrar evento no funil
MetricsService::registrarEventoFunil($clienteId, $empresaId, 'qualificacao');
MetricsService::registrarEventoFunil($clienteId, $empresaId, 'opcoes');
MetricsService::registrarEventoFunil($clienteId, $empresaId, 'visita');

// Coletar NPS após conversação
if ($clientePartiuConversa) {
    $perguntaNps = MetricsService::coletarNps($clienteId, $empresaId);
    EvolutionApiService::enviarMensagem($clienteId, $perguntaNps);
}

// Registrar NPS quando recebe resposta
if ($preguIniciouComNumero) {
    MetricsService::registrarNps($clienteId, $empresaId, (int)$resposta);
}

// Analisar motivo de não-conversão
if ($clientePartiuSemConvertir) {
    MetricsService::analisarNaoConversao($clienteId, $empresaId, [
        'objecoes_detectadas' => $objecoes,
        'num_opcoes_apresentadas' => 2,
    ]);
}

// Dashboard executivo
$dashboard = MetricsService::obterDashboard($empresaId);
// Enviar para admin/painel
```

---

## 🧪 **Testes de Regressão** (ConversationTestSuite)

### Local de Integração
CI/CD antes de deploy

```php
use App\Services\ConversationTestSuite;

// Antes de fazer merge/deploy:
$resultado = ConversationTestSuite::executarSuite();

if (!ConversationTestSuite::preDeploy()) {
    echo "❌ Testes falharam - Deploy bloqueado";
    exit(1);
}

echo "✅ Todos os testes passaram - Deploy OK";

// Ou adicionar test customizado:
ConversationTestSuite::adicionarTestCase([
    'nome' => 'Cliente chega fora de horário',
    'mensagem' => 'Olá',
    'contexto' => ['hora_atual' => 20],
    'esperado' => ['fora do horário', '08h'],
    'nao_esperado' => ['como posso ajudar'],
]);
```

---

## 🔌 **Fluxo Completo de Uma Conversa** (Pseudocódigo)

```php
// 1. Webhook chega
POST /webhook/whatsapp
{
    "instance": "N8n",
    "data": {
        "pushName": "Lucas Prado",
        "message": { "conversation": "Olá" }
    }
}

// 2. ProcessWhatsappMessage job inicia
ProcessWhatsappMessage::handle()

// 2a. Verificar horário
if (!verificarHorarioAtendimento()) {
    return enviarMensagemForaHorario();
}

// 2b. LGPD: Solicitar consentimento se novo
if (leadNovo) {
    return solicitar Consentimento(); // LgpdComplianceService
}

// 2c. Extrair dados do cliente
$lead = LeadCaptureService::capturarLead($empresaId, $clienteId, $pushName);

// 2d. Detectar objeção ou intenção
$objecao = ObjectionHandlerService::detectarObjecao($mensagem);
$intencao = EscalationService::detectarIntencaoEscalacao($mensagem);

if ($objecao) {
    return responderObjecao(); // ObjectionHandlerService
}

if ($intencao) {
    return escalar(); // EscalationService
}

// 2e. Gerar recomendações
$recomendacoes = MatchingEngine::generateRecommendations($lead->toArray());

// 2f. Explicar score de cada um
foreach ($recomendacoes as $imovel) {
    $explicacao = ExplainableMatchingService::explicarScore($imovel);
}

// 2g. Validar resposta antes de enviar
$validacao = ResponseValidationService::validarResposta($respostaFinal);

// 2h. Enviar
EvolutionApiService::enviarMensagem($clienteId, $respostaFinal);

// 2i. Registrar no funil
MetricsService::registrarEventoFunil($clienteId, $empresaId, 'opcoes');

// 2j. Agendar follow-up se apropriado
if ($cliente->dias_inativo > 2 && !$cliente->enviou_follow_up_1) {
    dispatch(new ProcessFollowUpAutomaticly($empresaId));
}
```

---

## 🚀 **Checklist de Implementação**

- [ ] Criar migrations: `php artisan migrate`
- [ ] Implementar Models (LeadCapture, Appointment, etc)
- [ ] Integrar AppointmentService no ProcessWhatsappMessage
- [ ] Integrar LeadCaptureService para coleta avançada
- [ ] Integrar FollowUpService como scheduled job
- [ ] Integrar ObjectionHandlerService para tratamento
- [ ] Integrar ExplainableMatchingService para transparência
- [ ] Integrar EscalationService para human handoff
- [ ] Integrar LgpdComplianceService para conformidade
- [ ] Integrar ResponseValidationService como guardrail
- [ ] Integrar MetricsService para observabilidade
- [ ] Executar testes: `php ConversationTestSuite::executarSuite()`
- [ ] Deploy com confiança ✅

---

**Pronto! Seu chatbot agora é um motor de vendas inteligente e completo.** 🎯
