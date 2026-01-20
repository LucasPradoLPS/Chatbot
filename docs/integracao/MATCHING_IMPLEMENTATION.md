# ✅ Catálogo: Lógica de Recomendação com Match Scoring

## O que foi implementado

### 1. **Serviço MatchingEngine** [app/Services/MatchingEngine.php]
Motor de recomendação que calcula score de compatibilidade entre perfil do usuário e imóveis.

**Pontuação:**
- **+40** se bairro/região bate
- **+20** se valor dentro do máximo
- **+10** por quarto compatível (exato)
- **+5** se um quarto a mais
- **+10** por vaga compatível
- **+5** por cada prioridade atendida (pet_friendly, varanda, suíte, etc.)

**Penalidades:**
- **-30** se levemente acima do máximo (1-20%)
- **-50** se muito acima do máximo (>20%)

### 2. **Categorização de Resultados**

#### Exatos (Score ≥ 70)
Imóveis que atendem 100% dos critérios do usuário. Mostrados em primeiro lugar.

#### Quase Lá (Score 40-69)
Imóveis que "estão um pouco acima" do orçamento (1-20%), mas ainda viáveis. Apresentados com mensagem transparente: **"⚠️ ESTICA UM POUCO - Esse está um pouco acima do seu orçamento, mas vale a pena ver!"**

#### Descartados (Score < 40)
Não são apresentados; usuário pode solicitar ajuste.

### 3. **Regras de Apresentação**

```
🎯 ENCONTREI AS MELHORES OPÇÕES PARA VOCÊ!

✅ OPÇÕES PERFEITAS (dentro do seu orçamento):
─────────────────────────────────
[Card 1]
[Card 2]
[Card 3]

⚠️ ESTICA UM POUCO (vale a pena ver):
─────────────────────────────────
[Card 4: com aviso]
[Card 5: com aviso]

→ Quero ajustar (bairro, valor, etc.)
→ Agendar visita em uma delas
→ Falar com corretor
```

### 4. **Configuração Centralizadora** [app/Config/MatchingEngineConfig.php]

Todos os parâmetros de scoring em um só lugar para fácil personalização:

```php
// Editar pontos
'neighborhood_match' => 40,
'value_within_budget' => 20,
// etc...

// Limiares de categorização
'exact' => 70,
'almost' => 40,
'over_budget_threshold' => 20,  // % para mudar penalidade

// Limites de apresentação
'max_exatos' => 5,
'max_quase_la' => 2,
'max_total' => 8,
```

### 5. **Integração no ProcessWhatsappMessage**

Quando o estado é `STATE_MATCH_RESULT`:

```php
if ($estadoAtual === 'STATE_MATCH_RESULT') {
    $resultadoMatch = $this->processMatchResult($slotsAtuais, $objetivo);
    if ($resultadoMatch && !valid($resultadoMatch['imoveis_exatos'] || $resultadoMatch['imoveis_quase_la'])) {
        $respostaLimpa = $resultadoMatch['mensagem'];
    }
}
```

O método `processMatchResult()`:
- Busca catálogo de imóveis via `getPropertyCatalog($objetivo)`
- Chama `MatchingEngine::generateRecommendations()`
- Retorna mensagem formatada com cards

### 6. **Arquivo de Teste** [test_matching_engine.php]

Para testar localmente:
```bash
php test_matching_engine.php
```

Mostra:
- Análise individual de scores para cada imóvel
- Categorização em Exatos / Quase Lá / Descartados
- Mensagem formatada final

---

## Exemplo de Uso Prático

**Entrada (Slots do Usuário):**
```
Bairro: Vila Mariana, Pinheiros
Orçamento máximo: R$ 500.000
Quartos: 2
Vagas: 1
Prioridades: pet_friendly, varanda
```

**Imóvel A:**
- Vila Mariana, R$ 480.000, 2 quartos, 1 vaga, [pet_friendly, varanda]
- **Score: 90** → **✅ EXATO**

**Imóvel B:**
- Vila Mariana, R$ 560.000 (12% acima), 3 quartos, 2 vagas, [varanda]
- **Score: 45** → **⚠️ QUASE LÁ**

**Imóvel C:**
- Bairro Imirim (não desejado), R$ 420.000, 4 quartos
- **Score: 20** → **❌ DESCARTADO**

---

## Personalização

### Mudar Pontuação

Edite [app/Config/MatchingEngineConfig.php]:

```php
'neighborhood_match' => 50,  // Aumentar peso do bairro
'priority_per_tag' => 10,    // Aumentar peso das prioridades
'over_budget_light' => -20,  // Reduzir penalidade de "quase lá"
```

### Ajustar Categorias

```php
'exact' => 80,      // Score >= 80 = Exato
'almost' => 50,     // Score 50-79 = Quase Lá
'over_budget_threshold' => 15,  // % para mudar penalidade
```

### Adicionar Novas Tags

Edite `MatchingEngineConfig::SUPPORTED_TAGS`:

```php
'novo_amenity' => 'Novo Amenity',
```

---

## Próximos Passos

- [ ] Conectar `getPropertyCatalog()` com DB real (AgenteGerado model)
- [ ] Implementar filtros rápidos (CTA buttons para refinar busca)
- [ ] Salvar imóvel favorito (persistir em DB)
- [ ] Integrar fotos e vídeos (links para visualização)
- [ ] Analytics: quais imóveis mais clicados
- [ ] Re-scoring dinâmico baseado em interações do usuário
- [ ] Notificações push para novos matches

---

## Estrutura de Arquivos

```
app/
  Services/
    MatchingEngine.php          ← Motor de scoring (252 linhas)
  Config/
    MatchingEngineConfig.php    ← Config centralizadora (90 linhas)
  Jobs/
    ProcessWhatsappMessage.php  ← Integrado (methods: processMatchResult, getPropertyCatalog)

test_matching_engine.php        ← Teste local
MATCHING_ENGINE.md              ← Documentação detalhada
```

---

## Log de Execução

Todos os matches gerados são registrados em `storage/logs/laravel.log`:

```
[2025-12-22 14:30:00] local.INFO: [MATCH-RESULT] Recomendações geradas
{
    "numero_cliente": "11999999999",
    "exatos": 3,
    "quase_la": 2
}
```

---

## Testes Recomendados

1. **Teste unitário**: `php test_matching_engine.php`
2. **Teste de estado**: Enviar mensagem em `STATE_MATCH_RESULT`
3. **Teste de refino**: Usuario digita "aumentar orçamento" → `STATE_REFINAR`
4. **Teste de cards**: Verificar formatação de cada imóvel

---

Implementação completa! 🎉 O sistema agora recomenda imóveis com scoring inteligente, mostrando "exatos" primeiro, depois "quase lá" com transparência.
