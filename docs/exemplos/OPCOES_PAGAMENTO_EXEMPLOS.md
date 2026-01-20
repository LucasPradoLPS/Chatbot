# Exemplos Práticos - Opções de Pagamento

## 1. Listar Todas as Opções de Pagamento

```php
use App\Services\OpcoesPagamentoService;

// No ProcessWhatsappMessage ou outro serviço
$opcoes = OpcoesPagamentoService::obterOpcoes();

$mensagem = "💳 *FORMAS DE PAGAMENTO DISPONÍVEIS:*\n\n";
foreach ($opcoes as $chave => $opcao) {
    $mensagem .= "{$opcao['icone']} **{$opcao['nome']}**\n";
    $mensagem .= "   {$opcao['descricao']}\n\n";
}

// Enviar mensagem ao cliente
```

**Resultado:**
```
💳 *FORMAS DE PAGAMENTO DISPONÍVEIS:*

💰 **À Vista**
   Pagamento integral em dinheiro

🏦 **Financiamento Bancário**
   Financiamento com bancos (Caixa, BB, Itaú, Santander, etc.)

📅 **Parcelado Direto**
   Parcelamento direto com construtora ou proprietário

(...)
```

---

## 2. Descrever Uma Forma Específica

```php
// Cliente perguntou: "Como funciona o financiamento?"
$descricao = OpcoesPagamentoService::descreverFormaPagamento('financiamento');

// Enviar descrição completa ao cliente
```

**Resultado:**
```
🏦 **Financiamento Bancário**
━━━━━━━━━━━━━━━━━━━━

*Descrição:*
Financiamento com bancos (Caixa, BB, Itaú, Santander, etc.)

✅ *Vantagens:*
• Não precisa ter valor total
• Prazo longo (até 35 anos)
• Usa FGTS para abater entrada ou parcelas
• Taxas competitivas

⚠️ *Desvantagens:*
• Análise de crédito necessária
• Juros ao longo do tempo
• Entrada mínima (geralmente 20%)
• Burocracia e documentação

📋 *Requisitos:*
• Ter renda comprovada
• Entrada de 20% a 30%
• Aprovação de crédito
```

---

## 3. Calcular Desconto à Vista

```php
// Cliente está interessado em pagar à vista
$valorImovel = 450000;

// Calcular com desconto padrão (10%)
$calculo = OpcoesPagamentoService::calcularDescontoAVista($valorImovel);

$mensagem = "💰 *PAGAMENTO À VISTA*\n\n";
$mensagem .= "Valor do Imóvel: R$ " . number_format($calculo['valor_original'], 2, ',', '.') . "\n";
$mensagem .= "Desconto: " . $calculo['percentual_desconto'] . "%\n";
$mensagem .= "Valor Final: R$ " . number_format($calculo['valor_final'], 2, ',', '.') . "\n";
$mensagem .= "🎉 Você economiza: R$ " . number_format($calculo['economia'], 2, ',', '.') . "\n";

// Enviar ao cliente
```

**Resultado:**
```
💰 *PAGAMENTO À VISTA*

Valor do Imóvel: R$ 450.000,00
Desconto: 10%
Valor Final: R$ 405.000,00
🎉 Você economiza: R$ 45.000,00
```

---

## 4. Simular Parcelamento Direto

```php
// Cliente quer parcelar direto com construtora
$valorImovel = 350000;
$entrada = 105000; // 30%
$numParcelas = 48; // 4 anos
$juros = 0; // Sem juros

$simulacao = OpcoesPagamentoService::calcularParceladoDireto(
    $valorImovel,
    $entrada,
    $numParcelas,
    $juros
);

$mensagem = "📅 *PARCELAMENTO DIRETO*\n\n";
$mensagem .= "Valor do Imóvel: R$ " . number_format($simulacao['valor_imovel'], 2, ',', '.') . "\n";
$mensagem .= "Entrada (30%): R$ " . number_format($simulacao['entrada'], 2, ',', '.') . "\n";
$mensagem .= "Restante: R$ " . number_format($simulacao['valor_a_parcelar'], 2, ',', '.') . "\n\n";
$mensagem .= "💳 Parcelas: {$simulacao['num_parcelas']}x de R$ " . number_format($simulacao['parcela_mensal'], 2, ',', '.') . "\n";
$mensagem .= "Total Pago: R$ " . number_format($simulacao['total_pago'], 2, ',', '.') . "\n";
$mensagem .= "Juros: R$ " . number_format($simulacao['total_juros'], 2, ',', '.') . "\n";

// Enviar ao cliente
```

**Resultado:**
```
📅 *PARCELAMENTO DIRETO*

Valor do Imóvel: R$ 350.000,00
Entrada (30%): R$ 105.000,00
Restante: R$ 245.000,00

💳 Parcelas: 48x de R$ 5.104,17
Total Pago: R$ 350.000,00
Juros: R$ 0,00
```

---

## 5. Comparar Todas as Formas de Pagamento

```php
// Cliente está em dúvida sobre qual forma escolher
$valorImovel = 350000;
$entradaDisponivel = 70000; // 20%
$rendaFaixa = "5000-8000";
$prazoFinanciamento = 30; // anos

$comparacao = OpcoesPagamentoService::compararFormasPagamento(
    $valorImovel,
    $entradaDisponivel,
    $rendaFaixa,
    $prazoFinanciamento
);

// Formatar e enviar
$mensagem = OpcoesPagamentoService::formatarComparacao($comparacao);

// Enviar ao cliente
```

**Resultado:**
```
💳 *COMPARAÇÃO DE FORMAS DE PAGAMENTO*
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

💰 **À Vista**
   Entrada: R$ 315.000,00
   Total: R$ 315.000,00
   🎉 Economia: R$ 35.000,00
   💡 Melhor opção se você tem todo o dinheiro disponível.

🏦 **Financiamento**
   Entrada: R$ 70.000,00
   Parcela: R$ 1.957,80 x 360
   Total: R$ 774.808,00
   ⚠️ Atenção
   💡 Parcela acima de 30% da renda. Considere aumentar entrada.

📅 **Parcelado Direto**
   ❌ Entrada mínima de 30% necessária (R$ 105.000,00)

🔀 **Misto (FGTS + Financiamento)**
   Solicite simulação personalizada com nosso especialista.
   💡 Combine FGTS com entrada e financie o restante. Reduz parcelas!

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Qual forma de pagamento te interessa mais?
```

---

## 6. Fluxo Completo no STATE_PROPOSTA

```php
// No ProcessWhatsappMessage.php quando o estado é STATE_PROPOSTA

// 1. Cliente escolheu imóvel e informou valor proposto
$slots['imovel_proposta_codigo'] = '#123';
$slots['valor_proposto'] = 350000;

// 2. Apresentar menu de opções
if (empty($slots['opcao_pagamento_escolhida'])) {
    $opcoes = OpcoesPagamentoService::obterOpcoes();
    
    $mensagem = "💳 *COMO VOCÊ PREFERE PAGAR?*\n\n";
    $contador = 1;
    foreach ($opcoes as $chave => $opcao) {
        $mensagem .= "{$contador}. {$opcao['icone']} **{$opcao['nome']}**\n";
        $mensagem .= "   {$opcao['descricao']}\n\n";
        $contador++;
    }
    $mensagem .= "Digite o número da opção ou o nome.";
    
    // Enviar e aguardar resposta
}

// 3. Cliente escolheu financiamento
$slots['opcao_pagamento_escolhida'] = 'financiamento';

// 4. Oferecer comparação
if (empty($slots['ja_calculou_comparacao'])) {
    $mensagem = "Quer que eu compare as formas de pagamento para você? (Sim/Não)";
    // Se sim, executar comparação
}

// 5. Mostrar comparação
if ($clienteRespondeuSim) {
    $comparacao = OpcoesPagamentoService::compararFormasPagamento(
        $slots['valor_proposto'],
        $slots['entrada_disponivel'] ?? 70000,
        $slots['renda_faixa_simulacao'] ?? '5000-8000',
        30
    );
    
    $mensagem = OpcoesPagamentoService::formatarComparacao($comparacao);
    $slots['ja_calculou_comparacao'] = 'sim';
    
    // Após comparação, perguntar novamente qual escolhe
}

// 6. Coletar detalhes específicos da forma escolhida
if ($slots['opcao_pagamento_escolhida'] === 'financiamento') {
    if (empty($slots['aprovacao_credito'])) {
        $mensagem = "Você já tem aprovação de crédito? (Sim/Não)";
        // Aguardar resposta
    }
    
    if (empty($slots['entrada_disponivel'])) {
        $mensagem = "Quanto você tem disponível para entrada?";
        // Aguardar resposta
    }
    
    if (empty($slots['interesse_usar_fgts'])) {
        $mensagem = "Quer usar FGTS também? (Sim/Não)";
        // Aguardar resposta
    }
}

// 7. Confirmação final
$mensagem = "✅ *RESUMO DA PROPOSTA:*\n\n";
$mensagem .= "Imóvel: {$slots['imovel_proposta_codigo']}\n";
$mensagem .= "Valor Proposto: R$ " . number_format($slots['valor_proposto'], 2, ',', '.') . "\n";
$mensagem .= "Pagamento: " . OpcoesPagamentoService::FORMAS_PAGAMENTO[$slots['opcao_pagamento_escolhida']] . "\n";
$mensagem .= "Entrada: R$ " . number_format($slots['entrada_disponivel'], 2, ',', '.') . "\n";
$mensagem .= "\nConfirma o envio da proposta?";
```

---

## 7. Cenário: Cliente Indeciso

```php
// Cliente: "Não sei qual forma escolher"

// Oferecer comparação automaticamente
$comparacao = OpcoesPagamentoService::compararFormasPagamento(
    $valorImovel,
    $entradaDisponivel,
    $rendaFaixa,
    30
);

$mensagem = "Vou te ajudar a decidir! 😊\n\n";
$mensagem .= OpcoesPagamentoService::formatarComparacao($comparacao);
$mensagem .= "\nDepois de ver essa comparação, qual forma faz mais sentido para você?";

// Enviar ao cliente
```

---

## 8. Cenário: Entrada Insuficiente

```php
// Cliente tem apenas 10% de entrada
$valorImovel = 350000;
$entradaBaixa = 35000; // 10%

$comparacao = OpcoesPagamentoService::compararFormasPagamento(
    $valorImovel,
    $entradaBaixa,
    "5000-8000",
    30
);

// Sistema detecta automaticamente e informa:
// - Financiamento: ❌ Entrada mínima de 20% necessária
// - Parcelado Direto: ❌ Entrada mínima de 30% necessária

$mensagem = "⚠️ Com essa entrada, algumas opções não estão disponíveis.\n\n";
$mensagem .= OpcoesPagamentoService::formatarComparacao($comparacao);
$mensagem .= "\n💡 Sugestão: Considere aumentar a entrada para ter mais opções.";

// Enviar ao cliente
```

---

## 9. Uso em Controller/API

```php
// routes/api.php
Route::post('/calcular-pagamento', function(Request $request) {
    $valorImovel = $request->valor_imovel;
    $entrada = $request->entrada;
    $renda = $request->renda_faixa;
    
    $comparacao = OpcoesPagamentoService::compararFormasPagamento(
        $valorImovel,
        $entrada,
        $renda,
        30
    );
    
    return response()->json([
        'sucesso' => true,
        'comparacao' => $comparacao,
        'mensagem_formatada' => OpcoesPagamentoService::formatarComparacao($comparacao)
    ]);
});
```

---

## 10. Integração com IA (OpenAI)

```php
// Adicionar contexto ao prompt do assistente
$systemInstructions = "
Você é um assistente imobiliário. Quando o cliente perguntar sobre formas de pagamento:

1. Use OpcoesPagamentoService::obterOpcoes() para listar todas as formas
2. Use OpcoesPagamentoService::descreverFormaPagamento() para explicar uma forma específica
3. Use OpcoesPagamentoService::compararFormasPagamento() para comparar opções
4. Sempre que possível, mostre a comparação para ajudar o cliente a decidir

Formas disponíveis: À vista, Financiamento, Parcelado Direto, Consórcio, FGTS, Permuta, Misto

Lembre-se:
- À vista: geralmente 10% de desconto
- Financiamento: mínimo 20% de entrada
- Parcelado direto: mínimo 30% de entrada
- Parcela não deve exceder 30% da renda
";

// O assistente agora pode usar essas informações nas respostas
```

---

## Conclusão

Esses exemplos mostram como usar o `OpcoesPagamentoService` em diferentes cenários. O serviço é flexível e pode ser integrado facilmente em qualquer parte do sistema (jobs, controllers, comandos, etc.).

**Principais métodos:**
- `obterOpcoes()` → Lista todas as formas
- `descreverFormaPagamento($forma)` → Detalhes de uma forma
- `calcularDescontoAVista($valor, $desconto)` → Calcula à vista
- `calcularParceladoDireto(...)` → Simula parcelamento
- `compararFormasPagamento(...)` → Compara todas as formas
- `formatarComparacao($comparacao)` → Formata para exibição
