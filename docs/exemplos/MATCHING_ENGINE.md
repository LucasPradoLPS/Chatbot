# MatchingEngine - Lógica de Recomendação com Scoring

## Visão Geral

O `MatchingEngine` é um serviço que calcula um score de compatibilidade entre o perfil de busca do usuário (slots) e imóveis disponíveis no catálogo. Ele implementa uma estratégia de apresentação em dois níveis:

1. **EXATOS**: Imóveis que atendem 100% dos critérios (score ≥ 70)
2. **QUASE LÁ**: Imóveis ligeiramente acima do orçamento mas viáveis (score 40-69)

---

## Fórmula de Scoring

### Pontos Positivos

| Critério | Pontos | Condição |
|----------|--------|----------|
| Bairro/Região | +40 | O imóvel está em um dos bairros desejados |
| Valor no Máximo | +20 | Preço ≤ faixa_valor_max do usuário |
| Quartos Exatos | +10 | quartos = quartos_desejados |
| Quarto Extra | +5 | quartos = quartos_desejados + 1 |
| Vagas Suficientes | +10 | vagas ≥ vagas_desejadas |
| Prioridade (cada) | +5 | Cada tag (pet_friendly, varanda, suíte) atendida |

### Penalidades

| Situação | Penalidade | Condição |
|----------|-----------|----------|
| Acima do Orçamento (Leve) | -30 | Preço 1-20% acima do máximo → "Estica um pouco" |
| Acima do Orçamento (Severa) | -50 | Preço >20% acima do máximo → Descartado ou muito penalizado |

### Exemplo de Cálculo

**Usuário:**
- Bairro desejado: "Vila Mariana"
- Valor máximo: R$ 500.000
- Quartos: 2
- Vagas: 1
- Prioridades: pet_friendly, varanda

**Imóvel A:**
- Bairro: Vila Mariana ✅ (+40)
- Valor: R$ 480.000 ✅ (+20)
- Quartos: 2 ✅ (+10)
- Vagas: 1 ✅ (+10)
- Tags: [pet_friendly, varanda] ✅ (+5 +5 = +10)
- **Score Total: 90** → Exato ✅

**Imóvel B:**
- Bairro: Vila Mariana ✅ (+40)
- Valor: R$ 560.000 ⚠️ (-30, pois está 12% acima)
- Quartos: 3 (+5)
- Vagas: 2 ✅ (+10)
- Tags: [varanda] ✅ (+5)
- **Score Total: 30** → Descartado ❌

**Imóvel C:**
- Bairro: Pinheiros ❌ (0)
- Valor: R$ 450.000 ✅ (+20)
- Quartos: 2 ✅ (+10)
- Vagas: 1 ✅ (+10)
- Tags: [pet_friendly] ✅ (+5)
- **Score Total: 45** → Quase Lá (sem bairro exato) ⚠️

---

## Uso Básico

### 1. Calcular Score de um Imóvel

```php
use App\Services\MatchingEngine;

$imovel = [
    'id' => 1,
    'titulo' => 'Apt. 2 quartos em Vila Mariana',
    'bairro' => 'Vila Mariana',
    'valor' => 480000,
    'quartos' => 2,
    'vagas' => 1,
    'tags' => ['pet_friendly', 'varanda', 'suíte'],
];

$slots = [
    'bairro_regiao' => ['Vila Mariana', 'Vila Madalena'],
    'faixa_valor_max' => 500000,
    'quartos' => 2,
    'vagas' => 1,
    'tags_prioridades' => ['pet_friendly', 'varanda'],
];

$scoreDetalhes = MatchingEngine::calculateScore($imovel, $slots);

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
```

### 2. Categorizar Resultados

```php
// Array de imóveis com scores já calculados
$imoveis = [
    ['id' => 1, 'titulo' => '...', 'score_detalhes' => ['score' => 90]],
    ['id' => 2, 'titulo' => '...', 'score_detalhes' => ['score' => 45]],
    // ... mais imóveis
];

$categorizado = MatchingEngine::categorizeResults($imoveis, maxExatos: 5, maxQuaseLa: 2);

// Resultado:
// [
//     'exatos' => [...],        // score >= 70
//     'quase_la' => [...],      // score 40-69
//     'descartados' => [...]    // score < 40
// ]
```

### 3. Formatar Card de Imóvel

```php
$card = MatchingEngine::formatPropertyCard($imovel, categoria: 'exato');

// Resultado:
// 🏠 *Apt. 2 quartos em Vila Mariana*
// 📍 Vila Mariana
// 💰 R$ 480.000
// 🛏️ 2 quartos | 🚗 1 vaga
// 
// → Ver fotos | → Ver no mapa | → Agendar visita | → Mais info
```

### 4. Gerar Recomendações Completas

```php
$imovelDisponiveis = [ /* ... */ ];

$resultado = MatchingEngine::generateRecommendations(
    imoveis: $imovelDisponiveis,
    slots: $slotsUsuario,
    maxResultados: 8
);

// Resultado:
// [
//     'mensagem' => "🎯 *Encontrei as melhores opções para você!*\n\n✅ *OPÇÕES PERFEITAS...",
//     'imoveis_exatos' => [...],
//     'imoveis_quase_la' => [...],
//     'total_apresentados' => 7
// ]
```

---

## Integração com ProcessWhatsappMessage

Quando o usuário atinge o estado `STATE_MATCH_RESULT`, o job automaticamente:

1. Captura os `slots` do usuário
2. Chama `$this->processMatchResult($slots, $objetivo)`
3. Busca imóveis do catálogo via `$this->getPropertyCatalog($objetivo)`
4. Gera recomendações com MatchingEngine
5. Envia a mensagem formatada com cards de imóveis

```php
// No ProcessWhatsappMessage::handle()

if ($estadoAtual === 'STATE_MATCH_RESULT') {
    $resultadoMatch = $this->processMatchResult($slotsAtuais, $objetivo);
    if ($resultadoMatch && !empty($resultadoMatch['imoveis_exatos'] || $resultadoMatch['imoveis_quase_la'])) {
        $respostaLimpa = $resultadoMatch['mensagem'];
    }
}
```

---

## Integração com Banco de Dados Real

Atualmente, `getPropertyCatalog()` retorna dados fictícios. Para produção:

### Opção 1: Query AgenteGerado (Imóveis do Sistema)

```php
private function getPropertyCatalog(string $objetivo): array
{
    $imoveis = AgenteGerado::where('objetivo', $objetivo)
        ->where('ativo', true)
        ->select([
            'id',
            'titulo',
            'bairro',
            'valor_aluguel as valor',  // ou 'valor_venda'
            'quartos',
            'vagas',
            'tags',  // JSON
        ])
        ->limit(50)
        ->get()
        ->map(fn($item) => $item->toArray())
        ->toArray();

    return $imoveis;
}
```

### Opção 2: API Externa (Imobiliária)

```php
private function getPropertyCatalog(string $objetivo): array
{
    $response = Http::get('https://api.imovel.com/properties', [
        'type' => $objetivo === 'comprar' ? 'sale' : 'rent',
        'limit' => 50,
    ]);

    if (!$response->successful()) {
        return [];
    }

    return array_map(fn($prop) => [
        'id' => $prop['id'],
        'titulo' => $prop['title'],
        'bairro' => $prop['location']['neighborhood'],
        'valor' => $prop['price'],
        'quartos' => $prop['bedrooms'],
        'vagas' => $prop['parking_spaces'],
        'tags' => $prop['amenities'] ?? [],
    ], $response->json('results'));
}
```

---

## Estrutura de Slots Esperada

Para o MatchingEngine funcionar corretamente, os `slots` devem conter:

```php
$slots = [
    // Lead (obrigatório)
    'nome' => 'João Silva',
    'telefone_whatsapp' => '11999999999',
    'email' => 'joao@email.com',
    
    // Busca (compra/aluguel)
    'bairro_regiao' => ['Vila Mariana', 'Vila Madalena'],  // array
    'faixa_valor_max' => 500000,  // int
    'quartos' => 2,  // int
    'vagas' => 1,  // int
    'tags_prioridades' => ['pet_friendly', 'varanda'],  // array
    
    // Outros
    'tipo_imovel' => 'apartamento',
    'prazo_mudanca' => '3 meses',
];
```

---

## Tags Suportadas

Use as tags abaixo ao definir imóveis:

- `pet_friendly` - Aceita animais de estimação
- `varanda` - Tem varanda
- `suíte` - Tem suíte
- `piscina` - Tem piscina
- `quintal` - Tem quintal
- `garagem_coberta` - Garagem coberta
- `elevador` - Tem elevador
- `mobiliado` - Imóvel mobiliado
- `ar_condicionado` - Ar condicionado
- `garden` - Garden/semi-basement
- `duplex` - Duplex
- `cobertura` - Cobertura
- `playground` - Playground (condomínio)
- `academia` - Academia (condomínio)

---

## Personalização do Scoring

Para alterar os pontos de cada critério, edite `app/Services/MatchingEngine.php`:

```php
// +40 se bairro/região bata → mudar para +50
$score += 50;

// -30 se levemente acima → mudar para -20
$penalidades -= 20;

// +5 por prioridade → mudar para +3
$scoreTagsCount = $prioridadesAtendidas * 3;
```

---

## Debugging

Todos os cálculos de score são registrados em log:

```
Log::info('[MATCH-RESULT] Recomendações geradas', [
    'numero_cliente' => $clienteId,
    'exatos' => 3,
    'quase_la' => 2,
]);
```

Verifique em `storage/logs/laravel.log` para ver:
- Quantos imóveis foram avaliados
- Quantos ficaram em "exatos" vs. "quase lá"
- Detalhes do cálculo de cada imóvel

---

## Próximos Passos

- [ ] Conectar com AgenteGerado para catálogo de imóveis real
- [ ] Implementar filtros rápidos (CTA buttons para refinar)
- [ ] Adicionar favoritos (salvar imóvel)
- [ ] Integrar fotos e vídeos (link para visualização)
- [ ] Agendamento automático de visitas
- [ ] Notificações push para novos matches
- [ ] Analytics: quais imóveis são mais clicados/visitados
