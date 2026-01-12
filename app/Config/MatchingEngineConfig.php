<?php

namespace App\Config;

/**
 * Configurações de Scoring do MatchingEngine
 * Personalize aqui os pontos atribuídos a cada critério
 */
class MatchingEngineConfig
{
    /**
     * Pontos positivos por critério
     */
    public const POINTS = [
        'neighborhood_match' => 40,      // +40 se bairro/região bate
        'value_within_budget' => 20,     // +20 se valor dentro do máximo
        'bedrooms_exact' => 10,          // +10 se quartos exatos
        'bedrooms_plus_one' => 5,        // +5 se um quarto a mais
        'parking_sufficient' => 10,      // +10 se vagas atendem
        'priority_per_tag' => 5,         // +5 por cada prioridade atendida
    ];

    /**
     * Penalidades por situações
     */
    public const PENALTIES = [
        'over_budget_light' => -30,      // -30 se 1-20% acima do máximo
        'over_budget_heavy' => -50,      // -50 se >20% acima do máximo
    ];

    /**
     * Limiares de categorização
     */
    public const THRESHOLDS = [
        'exact' => 70,                   // Score >= 70 = Exato
        'almost' => 40,                  // Score 40-69 = Quase Lá
        'discard' => 40,                 // Score < 40 = Descartado
        'over_budget_threshold' => 20,   // % acima do máximo para mudar penalidade
    ];

    /**
     * Limites de apresentação
     */
    public const PRESENTATION_LIMITS = [
        'max_exatos' => 5,               // Máximo de imóveis "exatos" a mostrar
        'max_quase_la' => 2,             // Máximo de imóveis "quase lá" a mostrar
        'max_total' => 8,                // Total máximo de imóveis por recomendação
    ];

    /**
     * Configurações de formatação
     */
    public const FORMAT = [
        'show_score' => false,           // Mostrar score no card? (debug)
        'show_breakdown' => false,       // Mostrar detalhes do cálculo? (debug)
    ];

    /**
     * Tags suportadas para prioridades
     */
    public const SUPPORTED_TAGS = [
        'pet_friendly' => 'Pet Friendly',
        'varanda' => 'Varanda',
        'suíte' => 'Suíte',
        'piscina' => 'Piscina',
        'quintal' => 'Quintal',
        'garagem_coberta' => 'Garagem Coberta',
        'elevador' => 'Elevador',
        'mobiliado' => 'Mobiliado',
        'ar_condicionado' => 'Ar Condicionado',
        'garden' => 'Garden',
        'duplex' => 'Duplex',
        'cobertura' => 'Cobertura',
        'playground' => 'Playground',
        'academia' => 'Academia',
    ];

    /**
     * Obter ponto para um critério
     */
    public static function getPoint(string $criterion): int
    {
        return self::POINTS[$criterion] ?? 0;
    }

    /**
     * Obter penalidade para uma situação
     */
    public static function getPenalty(string $situation): int
    {
        return self::PENALTIES[$situation] ?? 0;
    }

    /**
     * Obter limiar de categorização
     */
    public static function getThreshold(string $type): int
    {
        return self::THRESHOLDS[$type] ?? 0;
    }

    /**
     * Validar se tag é suportada
     */
    public static function isValidTag(string $tag): bool
    {
        return isset(self::SUPPORTED_TAGS[$tag]);
    }

    /**
     * Obter label de tag
     */
    public static function getTagLabel(string $tag): string
    {
        return self::SUPPORTED_TAGS[$tag] ?? $tag;
    }
}
