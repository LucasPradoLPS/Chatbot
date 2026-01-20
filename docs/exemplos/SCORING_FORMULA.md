# Score Match Scoring - Fórmula Visual

## 📊 Tabela de Pontuação

```
┌─────────────────────────────────────────────────────────────┐
│                    CRITÉRIOS POSITIVOS                      │
├─────────────────────────────────────────────────────────────┤
│ Bairro/Região                          │     +40 pontos     │
│ Valor dentro do orçamento máximo       │     +20 pontos     │
│ Quartos exatos                         │     +10 pontos     │
│ Quartos: um a mais que desejado        │     +5 pontos      │
│ Vagas suficientes ou superiores        │     +10 pontos     │
│ Cada prioridade atendida*              │     +5 pontos      │
├─────────────────────────────────────────────────────────────┤
│                    PENALIDADES                              │
├─────────────────────────────────────────────────────────────┤
│ Levemente acima (1-20% do máximo)      │     -30 pontos     │
│ Muito acima (>20% do máximo)           │     -50 pontos     │
├─────────────────────────────────────────────────────────────┤
│                    CATEGORIZAÇÃO                            │
├─────────────────────────────────────────────────────────────┤
│ Score ≥ 70       │  ✅ EXATO      │  Mostrar primeiro       │
│ Score 40-69      │  ⚠️  QUASE LÁ  │  Mostrar com aviso      │
│ Score < 40       │  ❌ DESCARTADO │  Não mostrar            │
└─────────────────────────────────────────────────────────────┘

*Prioridades: pet_friendly, varanda, suíte, piscina, quintal, 
            garagem_coberta, elevador, mobiliado, etc.
```

---

## 🧮 Exemplos de Cálculo

### Exemplo 1: EXATO (Score 90)
```
Usuário quer:
  • Bairro: Vila Mariana
  • Orçamento máximo: R$ 500.000
  • Quartos: 2
  • Vagas: 1
  • Prioridades: pet_friendly, varanda

Imóvel: "Apt. 2 quartos em Vila Mariana"
  ✓ Bairro: Vila Mariana              +40 pontos
  ✓ Valor: R$ 480.000 (dentro)        +20 pontos
  ✓ Quartos: 2 (exato)                +10 pontos
  ✓ Vagas: 1 (suficiente)             +10 pontos
  ✓ Tags: [pet_friendly, varanda]     +10 pontos
  ───────────────────────────────────
  SCORE: 90  ✅ EXATO
```

### Exemplo 2: QUASE LÁ (Score 45)
```
Usuário quer:
  • Bairro: Vila Mariana, Pinheiros
  • Orçamento máximo: R$ 500.000
  • Quartos: 2
  • Vagas: 1

Imóvel: "Apt. 3 quartos em Vila Mariana"
  ✓ Bairro: Vila Mariana              +40 pontos
  ✗ Valor: R$ 560.000 (12% acima)     -30 pontos ⚠️
  ✓ Quartos: 3 (um a mais)            +5 pontos
  ✓ Vagas: 2 (suficiente)             +10 pontos
  ✗ Sem prioridades                   0 pontos
  ───────────────────────────────────
  SCORE: 45  ⚠️  QUASE LÁ
  Mensagem: "Esse está 12% acima do seu orçamento, mas vale a pena!"
```

### Exemplo 3: DESCARTADO (Score 20)
```
Usuário quer:
  • Bairro: Vila Mariana, Pinheiros
  • Orçamento máximo: R$ 500.000
  • Quartos: 2
  • Vagas: 1

Imóvel: "Apt. 2 quartos em Imirim"
  ✗ Bairro: Imirim (não desejado)     0 pontos
  ✓ Valor: R$ 420.000 (dentro)        +20 pontos
  ✓ Quartos: 2 (exato)                +10 pontos
  ✗ Vagas: 0 (insuficiente)           0 pontos
  ✗ Sem prioridades                   0 pontos
  ───────────────────────────────────
  SCORE: 30  ❌ DESCARTADO
  (Não é mostrado ao usuário)
```

### Exemplo 4: Múltiplas Prioridades (Score 85)
```
Usuário quer:
  • Bairro: Morumbi
  • Orçamento: R$ 600.000
  • Quartos: 2
  • Vagas: 2
  • Prioridades: [pet_friendly, varanda, suíte, piscina]

Imóvel: "Apt. 2 quartos em Morumbi"
  Tags: [pet_friendly, varanda, suíte, piscina]
  
  ✓ Bairro: Morumbi                   +40 pontos
  ✓ Valor: R$ 580.000 (dentro)        +20 pontos
  ✓ Quartos: 2 (exato)                +10 pontos
  ✓ Vagas: 2 (suficiente)             +10 pontos
  ✓ Prioridades: 4 atendidas × 5      +20 pontos
  ───────────────────────────────────
  SCORE: 100  ✅ EXATO (Máximo!)
```

---

## 📈 Curva de Score por Orçamento

```
Score
  │
100│                    ◆ (Dentro + Muitas prioridades)
 80│            ◆ (Dentro + Poucos acertos)
 60│        ◆ (Levemente acima)
 40│    ◆ (Muito acima) ◆ (Poucos acertos)
 20│◆ (Sem bairro + Fora do orçamento)
  │
  └────────────────────────────────────────→ Percentual acima do máximo
    0%    5%    10%    15%    20%    25%
    
│  0-20%       │ Pode aplicar -30 (quase lá)
│  >20%        │ Aplica -50 (muito fora)
```

---

## 🎯 Recomendação ao Usuário

### Se Score ≥ 70 (Exato)
```
✅ OPÇÃO PERFEITA

🏠 [Título]
📍 [Bairro]
💰 [Preço]
🛏️ [Quartos] | 🚗 [Vagas]

→ Ver fotos | → Ver no mapa | → Agendar visita | → Mais info
```

### Se Score 40-69 (Quase Lá)
```
⚠️  ESTICA UM POUCO

🏠 [Título]
📍 [Bairro]
💰 [Preço]
🛏️ [Quartos] | 🚗 [Vagas]

⚠️  Esse está um pouco acima do seu orçamento, mas vale a pena ver!

→ Ver fotos | → Ver no mapa | → Agendar visita | → Mais info
```

### Se Score < 40 (Descartado)
```
❌ Não é mostrado

[Se usuário solicitar ajuste]
"Desculpe, não encontrei opções exatas. Posso:
1. Aumentar seu orçamento?
2. Mudar de bairro?
3. Falar com um corretor para opções customizadas?"
```

---

## 🔧 Como Ajustar Pontuação

Edite [app/Config/MatchingEngineConfig.php]:

```php
public const POINTS = [
    'neighborhood_match' => 40,      // Aumentar para 50 se bairro é crítico
    'value_within_budget' => 20,     // Aumentar para 30 se orçamento é crítico
    'bedrooms_exact' => 10,          // Manter em 10
    'bedrooms_plus_one' => 5,        // Reduzir para 3 se quartos é menos importante
    'parking_sufficient' => 10,      // Aumentar para 15 se vagas é importante
    'priority_per_tag' => 5,         // Aumentar para 8 se amenities são críticas
];

public const PENALTIES = [
    'over_budget_light' => -30,      // Reduzir para -20 se "quase lá" deve ser mais permissivo
    'over_budget_heavy' => -50,      // Aumentar para -70 se muito acima é não-viável
];
```

---

## 📊 Distribuição Típica

Com 50 imóveis analisados:

```
Distribuição de Scores:
  ✅ 10-15 imóveis "exatos" (Score 70+)     → Mostrar 5
  ⚠️  5-8 imóveis "quase lá" (Score 40-69)  → Mostrar 2
  ❌ 25-35 imóveis "descartados" (Score <40) → Não mostrar
```

Ao usuário são apresentados: **5-7 imóveis no total** (5 exatos + até 2 quase lá)

---

## 🚀 Otimizações Futuras

1. **Scoring dinâmico**: Ajustar pesos baseado em comportamento do usuário
2. **Weighted categories**: Diferentes pesos para bairro vs. preço vs. amenities
3. **Machine learning**: Aprender preferências do usuário ao longo do tempo
4. **Feedback loop**: "Não gostei" → ajusta futuros scores
5. **Fuzzy matching**: Permitir aproximações (ex: "próximo a Vila Mariana")

---

Fórmula pronta para produção! 🎯
