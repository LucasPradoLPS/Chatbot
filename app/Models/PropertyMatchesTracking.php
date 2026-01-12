<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyMatchesTracking extends Model
{
    protected $table = 'property_matches_tracking';

    protected $fillable = [
        'thread_id',
        'numero_cliente',
        'property_id',
        'property_titulo',
        'property_valor',
        'property_bairro',
        'score',
        'categoria',
        'score_detalhes',
        'posicao_exatos',
        'posicao_quase_la',
        'foi_clicado',
        'viu_fotos',
        'agendou_visita',
        'salvou_favorito',
        'cliques_total',
        'user_slots',
        'objetivo',
        'data_match',
    ];

    protected $casts = [
        'foi_clicado' => 'boolean',
        'viu_fotos' => 'boolean',
        'agendou_visita' => 'boolean',
        'salvou_favorito' => 'boolean',
        'score_detalhes' => 'array',
        'user_slots' => 'array',
        'data_match' => 'datetime',
    ];

    /**
     * Relação com Thread
     */
    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * Obter matches por categoria
     */
    public static function porCategoria(string $categoria)
    {
        return self::where('categoria', $categoria);
    }

    /**
     * Obter matches mais clicados
     */
    public static function maisClicados($limite = 10)
    {
        return self::where('foi_clicado', true)
            ->orderByDesc('cliques_total')
            ->limit($limite)
            ->get();
    }

    /**
     * Obter taxa de conversão (agendou_visita / foi_clicado)
     */
    public static function taxaConversao($dataInicio = null, $dataFim = null)
    {
        $query = self::where('foi_clicado', true);

        if ($dataInicio) {
            $query->where('data_match', '>=', $dataInicio);
        }

        if ($dataFim) {
            $query->where('data_match', '<=', $dataFim);
        }

        $totalClicados = $query->count();
        if ($totalClicados === 0) {
            return 0;
        }

        $agendados = $query->where('agendou_visita', true)->count();

        return ($agendados / $totalClicados) * 100;
    }

    /**
     * Score médio por categoria
     */
    public static function scoreMediaPorCategoria()
    {
        return self::groupBy('categoria')
            ->selectRaw('categoria, AVG(score) as score_medio, COUNT(*) as total')
            ->get();
    }

    /**
     * Imóveis mais relevantes (maior score médio)
     */
    public static function imoveisRelevantes($limite = 20)
    {
        return self::selectRaw('property_id, property_titulo, property_bairro, AVG(score) as score_medio, COUNT(*) as vezes_apresentado')
            ->groupBy('property_id', 'property_titulo', 'property_bairro')
            ->orderByDesc('score_medio')
            ->limit($limite)
            ->get();
    }

    /**
     * Registrar clique do usuário
     */
    public function registrarClique()
    {
        $this->increment('cliques_total');
        $this->update(['foi_clicado' => true]);
    }

    /**
     * Registrar que usuário viu fotos
     */
    public function registrarVouFotos()
    {
        $this->update(['viu_fotos' => true]);
    }

    /**
     * Registrar agendamento de visita
     */
    public function registrarAgendamento()
    {
        $this->update(['agendou_visita' => true]);
    }

    /**
     * Registrar como favorito
     */
    public function registrarFavorito()
    {
        $this->update(['salvou_favorito' => true]);
    }
}
