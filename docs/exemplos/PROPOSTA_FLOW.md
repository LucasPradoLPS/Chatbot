# Fluxo de Proposta (STATE_PROPOSTA)

## Resumo
O usuário pode fazer uma oferta/proposta para um imóvel identificado, coletando:
- Código do imóvel (#123)
- Valor proposto
- Forma de pagamento (financiamento / à vista / FGTS)
- Prazo para resposta (3/5/7/10 dias)

**Regra especial:** Se escolher **financiamento** e não tem aprovação de crédito confirmada, oferecer **simulação grátis** antes de enviar.

---

## Estados Envolvidos

### 1. STATE_PROPOSTA
**Descrição:** Proposta - Fazer oferta para imóvel

**Prompt:**
```
"Você quer fazer proposta para qual imóvel?"
(Pergunte código, ex.: #123)

Dados mínimos:
1. Qual é seu valor proposto?
2. Como prefere pagar? (Financiamento / À vista / FGTS / Combinado)
3. Quantos dias o vendedor tem para responder? (3 / 5 / 7 / 10 dias)

Se FINANCIAMENTO:
- Pergunta: "Você já tem aprovação de crédito?"
  - SIM → "Ótimo! Posso guardar sua aprovação para acelerar."
  - NÃO → "Posso fazer uma SIMULAÇÃO grátis com você."
          Ofereça: "Quer fazer a simulação agora?"

Ao final:
"Vou encaminhar sua proposta ao corretor responsável e você recebe 
a resposta em [prazo_dias] dias. Você será avisado por WhatsApp."
```

**Transições:**
- De: `STATE_MATCH_RESULT`, `STATE_REFINAR`, `STATE_VISITA_POS`
- Para: `STATE_HANDOFF` (após dados completos ou confirmação)
- Alternativa: volta para `STATE_MATCH_RESULT` (se mudar de ideia)

---

## Slots Coletados

| Slot | Tipo | Obrigatório | Descrição |
|------|------|-------------|-----------|
| `imovel_proposta_codigo` | string | ✅ | Código do imóvel (ex.: "123" para #123) |
| `valor_proposto` | number | ✅ | Valor da oferta em reais |
| `forma_pagamento` | string | ✅ | "financiamento", "à vista", "FGTS" ou "combinado" |
| `prazo_resposta_dias` | number | ✅ | Dias para resposta (3, 5, 7, 10, etc.) |
| `capacidade_financeira_confirmada` | string | ❌ | "sim" se usuário confirmou capacidade/aprovação |

---

## Lógica de Avanço Automático

**Quando STATE_PROPOSTA:**

1. **Extrai código do imóvel**
   - Busca `#123` ou `123` na mensagem
   - Salva em `slots[imovel_proposta_codigo]`

2. **Verifica dados mínimos**
   - Se TEM: `imovel_proposta_codigo`, `valor_proposto`, `forma_pagamento`, `prazo_resposta_dias`
   
3. **Se forma = "financiamento"**
   - E `capacidade_financeira_confirmada` ≠ "sim"
   - → Insere sugestão: "Quer fazer uma simulação grátis?"
   - Aguarda resposta do usuário
   
4. **Se forma ≠ "financiamento"** (à vista/FGTS/combinado)
   - → Vai direto para `STATE_HANDOFF`
   
5. **Se financiamento + capacidade confirmada**
   - → Vai direto para `STATE_HANDOFF`

---

## Fluxo de Exemplo

### Cenário 1: À Vista
```
User: "Quero fazer proposta para o imóvel #1"
Bot:  "Qual é seu valor proposto?" 
User: "R$ 480 mil"
Bot:  "Como prefere pagar?"
User: "À vista"
Bot:  "Quantos dias para resposta?"
User: "5 dias"
Bot:  "Vou encaminhar sua proposta ao corretor e você recebe resposta em 5 dias. 
      Você será avisado por WhatsApp."
     [Transição: STATE_HANDOFF]
```

### Cenário 2: Financiamento (sem aprovação)
```
User: "Proposta para #2, R$ 550 mil, financiamento, 7 dias"
Bot:  "Você já tem aprovação de crédito?"
User: "Não"
Bot:  "💡 Posso fazer uma SIMULAÇÃO GRÁTIS com você para saber a capacidade.
      Quer fazer a simulação agora?"
User: "Sim"
Bot:  [Oferece simulação - dados de renda, entrada, etc.]
     [Após simulação, confirma proposta]
     "Vou encaminhar sua proposta ao corretor..."
     [Transição: STATE_HANDOFF]
```

### Cenário 3: Financiamento (com aprovação)
```
User: "Proposta para #3, R$ 620 mil, financiamento, 3 dias"
Bot:  "Você já tem aprovação de crédito?"
User: "Sim, tenho"
Bot:  "Ótimo! Vou guardar sua aprovação para acelerar.
      Vou encaminhar sua proposta ao corretor e você recebe resposta em 3 dias."
     [Transição: STATE_HANDOFF]
```

---

## Integração com outros Estados

### De STATE_MATCH_RESULT
```
User: "Gostei desse, quero fazer proposta"
Bot:  [Detecta intent: "fazer_proposta"]
     [Transição: STATE_PROPOSTA]
     "Qual é o código do imóvel? (ex.: #123)"
```

### De STATE_VISITA_POS
```
User: "Gostei da visita, quero fazer proposta"
Bot:  [Transição: STATE_PROPOSTA]
     "Você quer fazer proposta para qual imóvel?"
```

---

## Logging e Rastreamento

```
[PROPOSTA] Sugestão de simulação inserida
[PROPOSTA] Proposta completa com capacidade confirmada, indo para HANDOFF
[PROPOSTA] Proposta à vista/FGTS completa, indo para HANDOFF
[PROPOSTA] Erro ao processar avanço do fluxo de proposta
```

---

## Próximos Passos Implementáveis

1. **Simulador de Financiamento (STATE_SIMULACAO_FINANCIAMENTO)**
   - Perguntar: renda mensal, entrada disponível, prazo desejado
   - Calcular prestação e viabilidade
   - Integrar com banco de dados de taxas de juros

2. **Armazenar Proposta em DB**
   - Criar modelo `PropostasSubmissao` com audit trail
   - Registrar timestamp, usuário, imóvel, valores, método pagamento

3. **Notificação ao Corretor**
   - Webhook ou email para aviso imediato
   - Dashboard de propostas pendentes

4. **Follow-up Automático**
   - Lembrete se resposta não chegar no prazo
   - Oferecer refinamento ou outras opções

---

## Atalhos Disponíveis

Em qualquer momento durante STATE_PROPOSTA:
- **"Ver imóveis"** → volta para `STATE_MATCH_RESULT`
- **"Falar com corretor"** → vai para `STATE_HANDOFF` sem finalizar proposta
- **"Cancelar proposta"** → volta para estado anterior
