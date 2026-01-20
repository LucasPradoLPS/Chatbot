# 🧪 Guia de Testes: Sistema de Match Scoring

## 1. Teste Local (Sem WhatsApp)

### Executar teste automatizado
```bash
cd c:\Users\lucas\Downloads\Chatbot-laravel
php test_matching_engine.php
```

**Output esperado:**
```
═══════════════════════════════════════════════════════
TESTE: MatchingEngine - Lógica de Recomendação
═══════════════════════════════════════════════════════

👤 PERFIL DO USUÁRIO:
   Nome: João Silva
   Bairros: Vila Mariana, Pinheiros, Vila Madalena
   Orçamento: R$ 500.000
   Quartos: 2
   Vagas: 1
   Prioridades: pet_friendly, varanda

═══════════════════════════════════════════════════════
ANÁLISE INDIVIDUAL DE SCORES
═══════════════════════════════════════════════════════

🏠 Apt. 2 quartos em Vila Mariana
   Bairro: Vila Mariana
   Valor: R$ 480.000
   Quartos: 2 | Vagas: 1
   Tags: pet_friendly, varanda
   SCORE: 90 pontos
      +40 (Bairro corresponde)
      +20 (Valor dentro do orçamento)
      +10 (Quartos exatos)
      +10 (Vagas atendem)
      +10 (2 prioridades atendidas)

... [mais imóveis] ...

═══════════════════════════════════════════════════════
CATEGORIZAÇÃO E FILTROS
═══════════════════════════════════════════════════════

✅ EXATOS (Score >= 70):
   1. Apt. 2 quartos em Vila Mariana (Score: 90)
   2. Apt. 2 quartos em Vila Madalena (Score: 85)
   3. Apt. 2 quartos em Pinheiros (Score: 75)

⚠️ QUASE LÁ (Score 40-69):
   1. Apt. 3 quartos em Vila Mariana (Score: 45)

❌ DESCARTADOS (Score < 40):
   1. Apt. 4 quartos em Imirim (Score: 20)
   2. Apt. 2 quartos em Morumbi (Score: 15)

═══════════════════════════════════════════════════════
MENSAGEM FORMATADA PARA O USUÁRIO
═══════════════════════════════════════════════════════

🎯 *Encontrei as melhores opções para você!*

✅ *OPÇÕES PERFEITAS (dentro do seu orçamento):*
━━━━━━━━━━━━━━━━━━━━━━━━
🏠 *Apt. 2 quartos em Vila Mariana*
📍 Vila Mariana
💰 R$ 480.000
🛏️ 2 quartos | 🚗 1 vaga

→ Ver fotos | → Ver no mapa | → Agendar visita | → Mais info

... [mais cards] ...

═══════════════════════════════════════════════════════
RESUMO
═══════════════════════════════════════════════════════
Total de imóveis analisados: 6
Exatos apresentados: 3
Quase lá apresentados: 1
Total apresentado: 4
═══════════════════════════════════════════════════════
```

---

## 2. Testes Unitários (PHPUnit)

### Criar arquivo de teste
```php
// tests/Unit/MatchingEngineTest.php

<?php

namespace Tests\Unit;

use App\Services\MatchingEngine;
use PHPUnit\Framework\TestCase;

class MatchingEngineTest extends TestCase
{
    public function test_calculate_score_exact_match()
    {
        $imovel = [
            'id' => 1,
            'bairro' => 'Vila Mariana',
            'valor' => 480000,
            'quartos' => 2,
            'vagas' => 1,
            'tags' => ['pet_friendly', 'varanda'],
        ];

        $slots = [
            'bairro_regiao' => ['Vila Mariana'],
            'faixa_valor_max' => 500000,
            'quartos' => 2,
            'vagas' => 1,
            'tags_prioridades' => ['pet_friendly', 'varanda'],
        ];

        $result = MatchingEngine::calculateScore($imovel, $slots);

        $this->assertEqual($result['score'], 90);
        $this->assertEqual($result['penalidades'], 0);
    }

    public function test_calculate_score_almost_match()
    {
        $imovel = [
            'id' => 2,
            'bairro' => 'Vila Mariana',
            'valor' => 560000,  // 12% acima
            'quartos' => 3,
            'vagas' => 2,
            'tags' => ['varanda'],
        ];

        $slots = [
            'bairro_regiao' => ['Vila Mariana'],
            'faixa_valor_max' => 500000,
            'quartos' => 2,
            'vagas' => 1,
            'tags_prioridades' => ['pet_friendly'],
        ];

        $result = MatchingEngine::calculateScore($imovel, $slots);

        $this->assertEqual($result['score'], 25);  // 40 + 5 + 10 - 30
        $this->assertEqual($result['penalidades'], -30);
    }

    public function test_categorize_results()
    {
        $imoveis = [
            ['id' => 1, 'score_detalhes' => ['score' => 90]],
            ['id' => 2, 'score_detalhes' => ['score' => 45]],
            ['id' => 3, 'score_detalhes' => ['score' => 25]],
        ];

        $result = MatchingEngine::categorizeResults($imoveis);

        $this->assertCount(1, $result['exatos']);
        $this->assertCount(1, $result['quase_la']);
        $this->assertCount(1, $result['descartados']);
    }
}
```

### Executar testes
```bash
php artisan test --filter MatchingEngineTest
```

---

## 3. Teste de Integração (Com WhatsApp)

### Cenário 1: Usuário atinge STATE_MATCH_RESULT

**Fluxo:**
```
1. Usuário inicia chat: "Olá"
2. Bot responde: "Bem-vindo!"
3. Usuário confirma LGPD: "Sim"
4. Usuário escolhe objetivo: "Comprar imóvel"
5. Usuário preenche slots: "2 quartos, R$ 500mil, Vila Mariana"
6. Bot executa MatchingEngine
7. Bot envia recomendações com scores
```

**Verificar em logs:**
```bash
tail -f storage/logs/laravel.log | grep -i "match-result\|score\|estado_atual"
```

**Output esperado:**
```
[2025-12-22 14:30:00] local.INFO: [MATCH-RESULT] Recomendações geradas
{
    "numero_cliente": "11999999999",
    "exatos": 3,
    "quase_la": 1,
    "objetivo": "comprar"
}
```

---

### Cenário 2: Refino da busca (STATE_REFINAR)

**Fluxo:**
```
1. Usuário vê resultados
2. Usuário diz: "Podem ser um pouco mais caros?"
3. Bot vai para STATE_REFINAR
4. Bot atualiza faixa_valor_max nos slots
5. Bot volta para STATE_MATCH_RESULT com novos matches
```

**Teste:**
- Verificar que slots foram atualizados
- Verificar que novos imóveis aparecem
- Verificar que estado transitou: STATE_MATCH_RESULT → STATE_REFINAR → STATE_MATCH_RESULT

---

### Cenário 3: Clique em imóvel

**Fluxo:**
```
1. Usuário clica em "Ver fotos" de um imóvel
2. Bot registra interação em PropertyMatchesTracking
3. Bot incrementa foi_clicado e cliques_total
```

**Query para verificar:**
```bash
php artisan tinker
```

```php
use App\Models\PropertyMatchesTracking;

# Ver últimos matches registrados
PropertyMatchesTracking::latest('data_match')->take(5)->get();

# Ver matches que foram clicados
PropertyMatchesTracking::where('foi_clicado', true)->get();

# Ver taxa de conversão
PropertyMatchesTracking::taxaConversao();
```

---

## 4. Testes de Performance

### Teste com 100+ imóveis

```php
// tests/Feature/MatchingPerformanceTest.php

$imoveis = [];
for ($i = 1; $i <= 100; $i++) {
    $imoveis[] = [
        'id' => $i,
        'titulo' => "Imóvel $i",
        'bairro' => ['Vila Mariana', 'Pinheiros', 'Vila Madalena'][rand(0, 2)],
        'valor' => rand(300000, 800000),
        'quartos' => rand(1, 4),
        'vagas' => rand(1, 3),
        'tags' => [...],
    ];
}

$start = microtime(true);
$resultado = MatchingEngine::generateRecommendations($imoveis, $slots);
$duration = microtime(true) - $start;

echo "Tempo processamento: {$duration}ms";
// Esperado: < 100ms
```

---

## 5. Testes de Edge Cases

### Caso 1: Sem imóveis no catálogo
```php
$imoveis = [];  // Vazio
$resultado = MatchingEngine::generateRecommendations($imoveis, $slots);

// Esperado: Mensagem "Desculpe, não encontrei opções"
$this->assertStringContainsString('não encontrei', $resultado['mensagem']);
```

### Caso 2: Slots incompletos
```php
$slots = [
    'nome' => 'João',
    // Outros slots vazios
];

$resultado = MatchingEngine::generateRecommendations($imoveis, $slots);

// Esperado: Sem crashear, lidar com null valores gracefully
$this->assertNotNull($resultado['mensagem']);
```

### Caso 3: Tag não suportada
```php
$imovel = [
    'tags' => ['amenity_inexistente'],
];

$slots = [
    'tags_prioridades' => ['amenity_inexistente'],
];

$result = MatchingEngine::calculateScore($imovel, $slots);

// Esperado: Não crashear, contabilizar como 0 pontos
$this->assertIsInt($result['score']);
```

### Caso 4: Valor muito alto (>1 bilhão)
```php
$imovel = [
    'valor' => 1500000000,  // 1.5 bilhão
];

$slots = [
    'faixa_valor_max' => 500000,
];

$result = MatchingEngine::calculateScore($imovel, $slots);

// Esperado: Penalidade severa, score baixo
$this->assertLessThan(0, $result['score'] + $result['penalidades']);
```

---

## 6. Testes de Banco de Dados

### Verificar migration
```bash
php artisan migrate:status

# Output esperado:
# 2025_12_22_000019_create_property_matches_tracking_table   PENDING  (ou MIGRATED)
```

### Executar migration
```bash
php artisan migrate --step=1
# Ou somente essa:
php artisan migrate --path=database/migrations/2025_12_22_000019_create_property_matches_tracking_table.php
```

### Verificar estrutura da tabela
```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

# Ver colunas
$columns = Schema::getColumns('property_matches_tracking');
dd($columns);

# Esperado:
# id, thread_id, numero_cliente, property_id, property_titulo, 
# property_valor, property_bairro, score, categoria, score_detalhes,
# posicao_exatos, posicao_quase_la, foi_clicado, viu_fotos, agendou_visita,
# salvou_favorito, cliques_total, user_slots, objetivo, data_match, created_at, updated_at
```

---

## 7. Teste de Carga (Load Test)

### Com Apache Bench
```bash
# Simular 100 requisições, 10 concorrentes
ab -n 100 -c 10 http://localhost/chatbot/webhook

# Esperado: Tempo resposta < 1s por requisição
```

### Com Wrk (mais realista)
```bash
wrk -t4 -c100 -d30s http://localhost/chatbot/webhook

# Esperado: Requests/sec > 50 rps
```

---

## 8. Checklist de Testes

### Antes de Deploy
- [ ] `php test_matching_engine.php` executa sem erros
- [ ] `php artisan test --filter MatchingEngineTest` passa
- [ ] `php artisan migrate` executa sem erros
- [ ] `php artisan queue:restart` reinicia workers
- [ ] Logs não mostram erros de import ou sintaxe
- [ ] Database tem tabela `property_matches_tracking`

### Teste Manual WhatsApp
- [ ] Usuário chega em STATE_MATCH_RESULT
- [ ] Recomendações são enviadas com cards
- [ ] Mínimo 1 "exato" é mostrado
- [ ] "Quase lá" mostra com ⚠️ aviso
- [ ] Atalhos são mostrados no final

### Teste de Refino
- [ ] Usuário diz "aumentar orçamento"
- [ ] Bot vai para STATE_REFINAR
- [ ] Volta para STATE_MATCH_RESULT com novos imóveis
- [ ] Slots foram atualizados corretamente

### Teste de Analytics
- [ ] Clique em "Ver fotos" registra em DB
- [ ] Agendamento de visita registra em DB
- [ ] Query `PropertyMatchesTracking::maisClicados()` retorna dados
- [ ] Taxa de conversão é calculada corretamente

---

## 9. Debugging

### Ver logs em tempo real
```bash
tail -f storage/logs/laravel.log | grep -i "match\|score\|estado"
```

### Inspecionar slots de um usuário
```bash
php artisan tinker
```

```php
use App\Models\Thread;

$thread = Thread::where('numero_cliente', '11999999999')->latest()->first();
dd($thread->slots);
```

### Inspecionar estado de um thread
```php
$thread = Thread::where('numero_cliente', '11999999999')->latest()->first();
dd([
    'estado_atual' => $thread->estado_atual,
    'etapa_fluxo' => $thread->etapa_fluxo,
    'objetivo' => $thread->objetivo,
    'lgpd_consentimento' => $thread->lgpd_consentimento,
    'intent' => $thread->intent,
    'estado_historico' => $thread->estado_historico,
]);
```

### Testar scoring manual
```php
use App\Services\MatchingEngine;

$imovel = ['bairro' => 'Vila Mariana', 'valor' => 480000, ...];
$slots = ['bairro_regiao' => ['Vila Mariana'], ...];

$score = MatchingEngine::calculateScore($imovel, $slots);
dd($score);
```

---

## 10. Documentação de Resultados

Após testes, documentar em `TESTES_EXECUTADOS.md`:

```markdown
# Testes Executados - 2025-12-22

## Teste Local
- ✅ `php test_matching_engine.php` - PASSOU
  - 6 imóveis processados
  - 3 exatos, 1 quase lá, 2 descartados
  - Mensagem formatada corretamente

## Testes Unitários
- ✅ `MatchingEngineTest::test_calculate_score_exact_match` - PASSOU
- ✅ `MatchingEngineTest::test_calculate_score_almost_match` - PASSOU
- ✅ `MatchingEngineTest::test_categorize_results` - PASSOU

## Database
- ✅ Migration `property_matches_tracking` - EXECUTADA
- ✅ Tabela criada com todas as colunas

## WhatsApp (Manual)
- ✅ Usuário atingiu STATE_MATCH_RESULT
- ✅ 5 imóveis "exatos" foram apresentados
- ✅ 2 imóveis "quase lá" foram apresentados
- ✅ Cards formatados corretamente
- ✅ Atalhos apareceram no final

## Conclusão
PRONTO PARA PRODUÇÃO ✅
```

---

Tudo pronto para testar! 🧪🎯
