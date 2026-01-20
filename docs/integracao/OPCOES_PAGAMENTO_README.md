# Sistema de Opções de Pagamento 💳

## Visão Geral

O sistema de opções de pagamento foi implementado para oferecer ao cliente diferentes formas de pagar pelo imóvel, com cálculos automáticos, comparações e recomendações personalizadas.

## 📋 Formas de Pagamento Disponíveis

### 1. 💰 À Vista
- **Descrição**: Pagamento integral em dinheiro
- **Vantagens**:
  - Desconto significativo (5% a 15%)
  - Sem juros
  - Negociação mais forte
  - Processo mais rápido
- **Requisitos**: Ter o valor total disponível

### 2. 🏦 Financiamento Bancário
- **Descrição**: Financiamento com bancos (Caixa, BB, Itaú, Santander)
- **Vantagens**:
  - Não precisa ter valor total
  - Prazo longo (até 35 anos)
  - Usa FGTS para abater entrada ou parcelas
  - Taxas competitivas
- **Requisitos**:
  - Renda comprovada
  - Entrada de 20% a 30%
  - Aprovação de crédito

### 3. 📅 Parcelado Direto
- **Descrição**: Parcelamento direto com construtora/proprietário
- **Vantagens**:
  - Sem análise bancária
  - Mais flexível
  - Sem juros ou juros menores
  - Ideal para imóveis na planta
- **Requisitos**:
  - Entrada substancial (30%+)
  - Acordo direto com vendedor

### 4. 🎲 Consórcio
- **Descrição**: Grupo de consórcio imobiliário
- **Vantagens**:
  - Sem juros (apenas taxa administrativa)
  - Flexível
  - Pode usar lance para antecipar
- **Desvantagens**:
  - Depende de sorteio ou lance
  - Pode demorar anos

### 5. 📝 FGTS
- **Descrição**: Uso do FGTS para entrada e/ou amortização
- **Vantagens**:
  - Usa recurso já disponível
  - Reduz entrada necessária
  - Pode abater parcelas mensais
- **Requisitos**:
  - Ter FGTS disponível
  - Imóvel residencial
  - Não ter outro financiamento ativo

### 6. 🔄 Permuta
- **Descrição**: Troca de imóvel como parte/totalidade do pagamento
- **Vantagens**:
  - Não precisa vender antes
  - Negociação direta
  - Facilita upgrade
- **Requisitos**:
  - Ter imóvel para trocar
  - Acordo sobre valores

### 7. 🔀 Misto
- **Descrição**: Combinação de entrada + FGTS + financiamento
- **Vantagens**:
  - Mais flexível
  - Reduz valor financiado
  - Parcelas menores
  - Aproveita melhor recursos disponíveis

## 🔧 Arquivos Criados/Modificados

### 1. `app/Services/OpcoesPagamentoService.php` ✨ NOVO
Serviço principal que gerencia todas as opções de pagamento.

**Métodos principais:**
- `obterOpcoes()`: Retorna todas as formas de pagamento com descrições completas
- `descreverFormaPagamento($forma)`: Descrição detalhada de uma forma específica
- `calcularDescontoAVista($valor, $percentual)`: Calcula desconto à vista
- `calcularParceladoDireto($valor, $entrada, $parcelas, $juros)`: Simula parcelamento direto
- `compararFormasPagamento($valor, $entrada, $renda, $prazo)`: Compara todas as formas lado a lado
- `formatarComparacao($comparacao)`: Formata comparação para exibir ao usuário

### 2. `app/Services/SlotsSchema.php` 🔄 MODIFICADO
Adicionados novos slots para capturar informações de pagamento:

```php
'opcao_pagamento_escolhida' => null,      // a_vista / financiamento / parcelado_direto / etc
'interesse_desconto_a_vista' => null,     // sim/não
'percentual_desconto_negociado' => null,  // 5-15%
'interesse_parcelado_direto' => null,     // sim/não
'num_parcelas_diretas' => null,           // 12/24/36/48
'interesse_consorcio' => null,            // sim/não
'possui_carta_credito_contemplada' => null, // sim/não
'interesse_usar_fgts' => null,            // sim/não
'saldo_fgts_disponivel' => null,          // valor
'tem_imovel_permuta' => null,             // sim/não
'valor_imovel_permuta' => null,           // valor estimado
'localizacao_imovel_permuta' => null,     // cidade/bairro
'complementar_com_dinheiro' => null,      // sim/não
'ja_calculou_comparacao' => null,         // sim/não
```

### 3. `app/Services/StateMachine.php` 🔄 MODIFICADO
Atualizado o prompt do `STATE_PROPOSTA` para:
- Apresentar menu completo de opções de pagamento
- Coletar informações específicas para cada forma escolhida
- Oferecer comparação entre formas
- Guiar o cliente na melhor escolha

### 4. `test_pagamento.php` ✨ NOVO
Script de teste completo com 7 cenários:
1. Listar todas as opções
2. Descrição detalhada
3. Cálculo de desconto à vista
4. Parcelamento direto
5. Comparação de formas
6. Formatação para usuário
7. Validação de entrada insuficiente

## 📊 Exemplo de Uso

### Código PHP:
```php
use App\Services\OpcoesPagamentoService;

// Comparar formas de pagamento
$comparacao = OpcoesPagamentoService::compararFormasPagamento(
    valorImovel: 350000,
    entradaDisponivel: 70000,
    rendaFaixa: "5000-8000",
    prazoAnos: 30
);

// Exibir comparação formatada ao usuário
$mensagem = OpcoesPagamentoService::formatarComparacao($comparacao);
echo $mensagem;
```

### Resultado:
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
```

## 🧪 Como Testar

Execute o script de teste:
```bash
php test_pagamento.php
```

Testes incluídos:
- ✅ Listagem de todas as opções
- ✅ Descrição detalhada de cada forma
- ✅ Cálculo de desconto à vista (10% e 15%)
- ✅ Simulação de parcelamento direto
- ✅ Comparação entre formas de pagamento
- ✅ Formatação de mensagem para WhatsApp
- ✅ Validação de entrada insuficiente

## 🤖 Fluxo no Chatbot

### Estado: STATE_PROPOSTA

1. **Identificar Imóvel**: Cliente informa código do imóvel
2. **Valor Proposto**: Cliente informa quanto quer oferecer
3. **Menu de Opções**: Bot apresenta 7 formas de pagamento
4. **Detalhes da Forma**: Bot coleta informações específicas:
   - À vista: interesse em desconto?
   - Financiamento: tem aprovação? quanto de entrada?
   - Parcelado: quantas parcelas?
   - Consórcio: já contemplado?
   - FGTS: quanto tem disponível?
   - Permuta: valor e localização do imóvel?
   - Misto: combinação desejada?
5. **Comparação (opcional)**: Bot oferece comparar opções
6. **Confirmação**: Cliente confirma escolha
7. **Encaminhamento**: Proposta enviada ao corretor

## 💡 Recursos Inteligentes

### Validação Automática
- Entrada mínima para financiamento: 20%
- Entrada mínima para parcelado direto: 30%
- Parcela máxima: 30% da renda (financiamento)

### Cálculos Precisos
- Desconto à vista: 5% a 15% (configurável)
- Financiamento: Fórmula Price com taxa de 7.5% a.a.
- Parcelado direto: Com ou sem juros

### Recomendações Personalizadas
- "Parcela cabe no orçamento" vs "Considere aumentar entrada"
- "Sem juros! Bom se conseguir entrada de 30%+"
- "Combine FGTS com entrada e financie o restante"

## 🎯 Benefícios

### Para o Cliente
- Visualiza todas as opções de pagamento
- Compara formas lado a lado
- Entende vantagens e desvantagens
- Recebe recomendações personalizadas
- Toma decisão mais informada

### Para a Imobiliária
- Qualifica melhor o lead
- Reduz propostas inviáveis
- Aumenta taxa de conversão
- Melhora experiência do cliente
- Automatiza processo de qualificação financeira

## 🔄 Integração com Outros Serviços

- **SimuladorFinanciamento**: Usado para calcular financiamento bancário
- **StateMachine**: Gerencia fluxo conversacional
- **SlotsSchema**: Armazena dados coletados
- **ProcessWhatsappMessage**: Processa mensagens com opções de pagamento

## 📈 Próximas Melhorias (Opcional)

- [ ] Integração com APIs de bancos para taxas reais
- [ ] Calculadora de consórcio com sorteios
- [ ] Simulação de permuta automática
- [ ] Comparação de diferentes prazos de financiamento
- [ ] Análise de custo-benefício entre opções
- [ ] Dashboard de propostas por forma de pagamento

## ✅ Status

**Implementação Completa**: Todos os testes passaram com sucesso! ✨

O sistema está pronto para uso em produção.
