# 💡 Exemplos Práticos: Usando o MatchingEngine

## 📖 Índice
1. [Uso Básico](#1-uso-básico)
2. [Customização](#2-customização)
3. [Analytics](#3-analytics)
4. [Edge Cases](#4-edge-cases)
5. [Integração Avançada](#5-integração-avançada)

---

## 1. Uso Básico

### Exemplo 1a: Calcular score de um imóvel

```php
<?php

use App\Services\MatchingEngine;

// Dados do imóvel
$imovel = [
    'id' => 101,
    'titulo' => 'Apartamento 2 quartos em Vila Mariana',
    'bairro' => 'Vila Mariana',
    'valor' => 480000,
    'quartos' => 2,
    'vagas' => 1,
    'tags' => ['pet_friendly', 'varanda', 'ar_condicionado'],
];

// Preferências do usuário (slots)
$slots = [
    'bairro_regiao' => ['Vila Mariana', 'Pinheiros'],
    'faixa_valor_max' => 500000,
    'quartos' => 2,
    'vagas' => 1,
    'tags_prioridades' => ['pet_friendly', 'varanda'],
];

// Calcular score
$resultado = MatchingEngine::calculateScore($imovel, $slots);

// Resultado:
// [
//     'score' => 90,
//     'penalidades' => 0,
//     'detalhes' => [
//         '+40 (Bairro corresponde)',
//         '+20 (Valor dentro do orçamento)',
//         '+10 (Quartos exatos)',
//         '+10 (Vagas atendem)',
//         '+10 (2 prioridades atendidas)',
//     ]
// ]

echo "Score: " . $resultado['score'];  // Output: Score: 90
```

### Exemplo 1b: Gerar recomendações completas

```php
<?php

use App\Services\MatchingEngine;

// Lista de imóveis disponíveis
$imoveis = [
    [
        'id' => 1,
        'titulo' => 'Apt 2 qtos Vila Mariana',
        'bairro' => 'Vila Mariana',
        'valor' => 480000,
        'quartos' => 2,
        'vagas' => 1,
        'tags' => ['pet_friendly', 'varanda'],
    ],
    [
        'id' => 2,
        'titulo' => 'Apt 3 qtos Vila Mariana',
        'bairro' => 'Vila Mariana',
        'valor' => 560000,  // 12% acima
        'quartos' => 3,
        'vagas' => 2,
        'tags' => ['varanda', 'suíte'],
    ],
    // ... mais imóveis
];

// Slots do usuário
$slots = [
    'bairro_regiao' => ['Vila Mariana', 'Pinheiros'],
    'faixa_valor_max' => 500000,
    'quartos' => 2,
    'vagas' => 1,
    'tags_prioridades' => ['pet_friendly', 'varanda'],
];

// Gerar recomendações
$resultado = MatchingEngine::generateRecommendations(
    imoveis: $imoveis,
    slots: $slots,
    maxResultados: 8
);

// Enviar mensagem ao usuário
$mensagem = $resultado['mensagem'];
$this->enviarWhatsapp($clienteId, $mensagem);

// Dados para análise
$exatos = count($resultado['imoveis_exatos']);      // 2 imóveis
$quaseLa = count($resultado['imoveis_quase_la']);   // 1 imóvel
```

---

## 2. Customização

### Exemplo 2a: Alterar pesos de scoring

```php
<?php

// Arquivo: app/Config/MatchingEngineConfig.php

// ANTES (padrão)
public const POINTS = [
    'neighborhood_match' => 40,
    'value_within_budget' => 20,
    'bedrooms_exact' => 10,
    'priority_per_tag' => 5,
];

// DEPOIS (customizado - bairro mais importante)
public const POINTS = [
    'neighborhood_match' => 60,      // ← Aumentado
    'value_within_budget' => 15,     // ← Reduzido
    'bedrooms_exact' => 10,
    'priority_per_tag' => 8,         // ← Aumentado
];

// Resultado: Bairro é 1.5x mais importante que preço
```

### Exemplo 2b: Mudar limiares de categorização

```php
<?php

// Arquivo: app/Config/MatchingEngineConfig.php

// ANTES (padrão)
public const THRESHOLDS = [
    'exact' => 70,           // Score >= 70 = Exato
    'almost' => 40,          // Score >= 40 = Quase Lá
    'over_budget_threshold' => 20,  // 20% acima
];

// DEPOIS (mais permissivo)
public const THRESHOLDS = [
    'exact' => 75,           // ← Mais rigoroso
    'almost' => 50,          // ← Mais rigoroso
    'over_budget_threshold' => 25,  // ← Mais permissivo
];

// Resultado: Menos imóveis "exatos", mais "quase lá"
```

### Exemplo 2c: Adicionar nova tag de amenity

```php
<?php

// Arquivo: app/Config/MatchingEngineConfig.php

public const SUPPORTED_TAGS = [
    'pet_friendly' => 'Pet Friendly',
    'varanda' => 'Varanda',
    'suíte' => 'Suíte',
    'piscina' => 'Piscina',
    'novo_amenity' => 'Novo Amenity',  // ← Adicionado
];

// Uso no MatchingEngine:
// Agora prioridades podem incluir 'novo_amenity'
// E ganha +5 pontos se o imóvel tem essa tag
```

---

## 3. Analytics

### Exemplo 3a: Rastrear match quando enviado

```php
<?php

use App\Models\PropertyMatchesTracking;

// No ProcessWhatsappMessage, quando STATE_MATCH_RESULT:

foreach ($resultado['imoveis_exatos'] as $index => $imovel) {
    PropertyMatchesTracking::create([
        'thread_id' => $thread->id,
        'numero_cliente' => $clienteId,
        'property_id' => $imovel['id'],
        'property_titulo' => $imovel['titulo'],
        'property_valor' => $imovel['valor'],
        'property_bairro' => $imovel['bairro'],
        'score' => $imovel['score_detalhes']['score'],
        'categoria' => 'exato',
        'score_detalhes' => $imovel['score_detalhes'],
        'posicao_exatos' => $index + 1,
        'user_slots' => $slotsAtuais,
        'objetivo' => $objetivo,
    ]);
}

foreach ($resultado['imoveis_quase_la'] as $index => $imovel) {
    PropertyMatchesTracking::create([
        // ... mesma estrutura
        'categoria' => 'quase_la',
        'posicao_quase_la' => $index + 1,
    ]);
}
```

### Exemplo 3b: Registrar clique do usuário

```php
<?php

use App\Models\PropertyMatchesTracking;

// Quando usuário clica em "Ver fotos" de um imóvel:

$match = PropertyMatchesTracking::find($matchId);
$match->registrarClique();  // Incrementa cliques_total, seta foi_clicado = true

// Ou manualmente:
$match->increment('cliques_total');
$match->update(['foi_clicado' => true]);
```

### Exemplo 3c: Registrar agendamento de visita

```php
<?php

use App\Models\PropertyMatchesTracking;

// Quando usuário confirma agendamento de visita:

$match = PropertyMatchesTracking::find($matchId);
$match->registrarAgendamento();  // Seta agendou_visita = true

// Verificar taxa de conversão
$taxaConversao = PropertyMatchesTracking::taxaConversao(
    dataInicio: now()->subDays(7),
    dataFim: now()
);
echo "Taxa conversão (semana): {$taxaConversao}%";
```

### Exemplo 3d: Relatório de imóveis mais clicados

```php
<?php

use App\Models\PropertyMatchesTracking;

// Top 10 imóveis mais clicados
$topImoveis = PropertyMatchesTracking::selectRaw(
    'property_id, property_titulo, property_bairro, AVG(score) as score_medio, COUNT(*) as apresentacoes, SUM(CASE WHEN foi_clicado THEN 1 ELSE 0 END) as cliques'
)
    ->groupBy('property_id', 'property_titulo', 'property_bairro')
    ->orderByDesc('cliques')
    ->limit(10)
    ->get();

foreach ($topImoveis as $imovel) {
    echo $imovel->property_titulo . ": ";
    echo "{$imovel->cliques} cliques de {$imovel->apresentacoes} apresentações";
    echo " (Score médio: {$imovel->score_medio})";
}

// Output:
// Apt 2 quartos Vila Mariana: 15 cliques de 20 apresentações (Score médio: 88)
// Apt 3 quartos Vila Mariana: 8 cliques de 18 apresentações (Score médio: 65)
```

---

## 4. Edge Cases

### Exemplo 4a: Sem imóveis no catálogo

```php
<?php

use App\Services\MatchingEngine;

$imoveis = [];  // Catálogo vazio
$slots = [...];

$resultado = MatchingEngine::generateRecommendations($imoveis, $slots);

// Resultado: Mensagem de fallback
// "Desculpe, não encontrei imóveis no catálogo que correspondam ao seu perfil..."
```

### Exemplo 4b: Slots incompletos

```php
<?php

use App\Services\MatchingEngine;

$imovel = [
    'bairro' => 'Vila Mariana',
    'valor' => 500000,
    // quartos ausente
    // vagas ausente
    // tags ausente
];

$slots = [
    'bairro_regiao' => ['Vila Mariana'],
    // Outros slots ausentes
];

$resultado = MatchingEngine::calculateScore($imovel, $slots);

// Resultado: Funciona normalmente, ignora campos ausentes
// Score: 60 (40 bairro + 20 valor)
// Não crasheia
```

### Exemplo 4c: Tag não reconhecida

```php
<?php

use App\Services\MatchingEngine;

$imovel = [
    'tags' => ['amenity_inexistente'],  // Tag não está em SUPPORTED_TAGS
];

$slots = [
    'tags_prioridades' => ['amenity_inexistente'],
];

$resultado = MatchingEngine::calculateScore($imovel, $slots);

// Resultado: Não crasheia, simplesmente não conta pontos
// Score: XX (sem os +5 por tag)
```

### Exemplo 4d: Valor muito alto

```php
<?php

use App\Services\MatchingEngine;

$imovel = [
    'valor' => 1000000000,  // 1 bilhão
];

$slots = [
    'faixa_valor_max' => 500000,
];

$resultado = MatchingEngine::calculateScore($imovel, $slots);

// Percentual acima: (1000000000 - 500000) / 500000 * 100 = 199999%
// Penalidade: -50 (muito acima)
// Score: XX - 50 (provavelmente baixo/negativo, mas limitado a 0)
```

---

## 5. Integração Avançada

### Exemplo 5a: Refino dinâmico (STATE_REFINAR)

```php
<?php

// Usuário: "Podem ser um pouco mais caros?"
// Sistema detecta intenção e vai para STATE_REFINAR

// 1. Atualizar slots
$thread->slots['faixa_valor_max'] = 600000;  // De 500k para 600k
$thread->save();

// 2. Buscar novo catálogo
$imoveis = $this->getPropertyCatalog($objetivo);

// 3. Gerar novas recomendações
$resultado = MatchingEngine::generateRecommendations(
    $imoveis,
    $thread->slots,
    maxResultados: 8
);

// 4. Ir para STATE_MATCH_RESULT
$thread->update([
    'estado_atual' => 'STATE_MATCH_RESULT',
    'estado_historico' => StateMachine::registerTransition(
        $thread->estado_historico,
        'STATE_REFINAR',
        'STATE_MATCH_RESULT'
    ),
]);

// 5. Enviar novas recomendações
$this->enviarWhatsapp($clienteId, $resultado['mensagem']);
```

### Exemplo 5b: Filtro rápido por preço

```php
<?php

// Usuário clica em "Ver mais baratos" 

// 1. Detectar intenção: 'filtrar' com keyword 'barato'
// 2. Atualizar slots
$faixaAtual = $thread->slots['faixa_valor_max'];
$thread->slots['faixa_valor_max'] = $faixaAtual - 50000;  // Reduz 50k
$thread->save();

// 3. Re-gerar recomendações
$imoveis = $this->getPropertyCatalog($objetivo);
$resultado = MatchingEngine::generateRecommendations(
    $imoveis,
    $thread->slots,
);

// Resultado: Imóveis mais baratos aparecem em primeiro lugar
```

### Exemplo 5c: Salvar como favorito

```php
<?php

use App\Models\PropertyMatchesTracking;

// Usuário clica em "Salvar favorito"

$match = PropertyMatchesTracking::find($matchId);
$match->registrarFavorito();  // salvou_favorito = true

// Listar favoritos do usuário
$favoritos = PropertyMatchesTracking::where('numero_cliente', $clienteId)
    ->where('salvou_favorito', true)
    ->get();

foreach ($favoritos as $fav) {
    echo "{$fav->property_titulo} - R$ " . number_format($fav->property_valor);
}
```

### Exemplo 5d: Machine Learning - Ajustar pesos dinamicamente

```php
<?php

use App\Config\MatchingEngineConfig;
use App\Models\PropertyMatchesTracking;

// Analisar comportamento do usuário
$userMatches = PropertyMatchesTracking::where('numero_cliente', $clienteId)->get();

$clicadosComScore = $userMatches->where('foi_clicado', true)->avg('score');
$clicadosComBairro = $userMatches
    ->where('foi_clicado', true)
    ->where('property_bairro', 'Vila Mariana')
    ->count();

// Se usuário clica muito em imóveis caros, aumentar peso de preço
if ($clicadosComScore > 75) {
    // Próximas recomendações podem priorizar preço
    // (Implementar lógica de peso dinâmico)
}

// Se usuário clica muito em um bairro, aumentar peso desse bairro
if ($clicadosComBairro > 5) {
    // Próximas recomendações podem priorizar Vila Mariana
}
```

---

## 📝 Exemplo Completo: Fluxo do Usuário

```php
<?php

// 1️⃣ USUÁRIO INICIA
Input: "Olá, quero comprar um apartamento"
Bot: "Bem-vindo! Vou ajudar a encontrar o imóvel ideal."

// 2️⃣ LGPD
Input: "Sim, concordo com LGPD"
Bot: [Vai para objetivo]

// 3️⃣ OBJETIVO
Input: "Quero comprar"
Bot: [Vai para qualificação]

// 4️⃣ QUALIFICAÇÃO (preenchimento de slots)
Input: "2 quartos, Vila Mariana, até R$ 500 mil"
Slots: {
    "quartos": 2,
    "bairro_regiao": ["Vila Mariana"],
    "faixa_valor_max": 500000,
    "tags_prioridades": ["pet_friendly"]
}
Bot: [Vai para STATE_MATCH_RESULT]

// 5️⃣ MATCH RESULT (MatchingEngine em ação!)
Estado: STATE_MATCH_RESULT

$imoveis = getPropertyCatalog('comprar');  // Busca DB/API
$resultado = MatchingEngine::generateRecommendations($imoveis, $slots);

// Resultado:
// ✅ EXATOS (Score >= 70): 5 imóveis
// ⚠️ QUASE LÁ (Score 40-69): 2 imóveis
// ❌ DESCARTADOS: 43 imóveis (não mostrados)

Bot: "🎯 Encontrei as melhores opções para você!
✅ OPÇÕES PERFEITAS:
1. Apt 2 quartos em Vila Mariana - R$ 480k
2. Apt 2 quartos em Vila Mariana - R$ 470k
...

⚠️ ESTICA UM POUCO:
1. Apt 3 quartos em Vila Mariana - R$ 560k

→ Quero ajustar
→ Agendar visita
→ Falar com corretor"

// 6️⃣ REFINO (opcional)
Input: "Podem ser um pouco mais caros?"

Estado: STATE_REFINAR
Slots: { "faixa_valor_max": 600000 }

$resultado = MatchingEngine::generateRecommendations($imoveis, $slots);

Bot: "Melhor assim?
✅ OPÇÕES PERFEITAS:
1. Apt 2 quartos Vila Mariana - R$ 480k
2. Apt 2 quartos Vila Mariana - R$ 520k (novo!)
...

→ Agendar visita
→ Falar com corretor"

// 7️⃣ AGENDAMENTO
Input: "Quero agendar uma visita"

Estado: STATE_AGENDAMENTO
Bot: "Qual imóvel gostaria de visitar?
Qual dia e horário te convém?"

// Analytics registram:
PropertyMatchesTracking {
    "property_id": 1,
    "score": 85,
    "categoria": "exato",
    "foi_clicado": true,
    "agendou_visita": true,
}
```

---

## 🎯 Conclusão

Estes exemplos cobrem:
- ✅ Casos básicos de scoring
- ✅ Customização de pesos
- ✅ Analytics e rastreamento
- ✅ Edge cases
- ✅ Integração com fluxo conversacional
- ✅ Machine learning futuro

Para mais exemplos, consulte:
- `MATCHING_ENGINE.md` - Métodos
- `SCORING_FORMULA.md` - Fórmulas
- `test_matching_engine.php` - Teste completo

---

Happy coding! 🚀
