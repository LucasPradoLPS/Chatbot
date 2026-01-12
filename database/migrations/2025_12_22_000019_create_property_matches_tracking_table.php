-- Migration: Criar tabela de tracking para analytics de matches
-- Arquivo: database/migrations/2025_12_22_000019_create_property_matches_tracking_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertyMatchesTrackingTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('property_matches_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('threads')->onDelete('cascade');
            $table->string('numero_cliente')->index();
            
            // Detalhes do match
            $table->unsignedInteger('property_id')->comment('ID do imóvel');
            $table->string('property_titulo')->comment('Título do imóvel');
            $table->decimal('property_valor', 15, 2)->comment('Valor do imóvel');
            $table->string('property_bairro')->comment('Bairro do imóvel');
            
            // Score e categorização
            $table->unsignedInteger('score')->comment('Score de match calculado');
            $table->enum('categoria', ['exato', 'quase_la', 'descartado'])->comment('Categoria do match');
            $table->json('score_detalhes')->nullable()->comment('Detalhes do cálculo de score');
            
            // Posição na apresentação
            $table->unsignedTinyInteger('posicao_exatos')->nullable()->comment('Posição entre exatos (1-5)');
            $table->unsignedTinyInteger('posicao_quase_la')->nullable()->comment('Posição entre quase lá (1-2)');
            
            // Interações do usuário
            $table->boolean('foi_clicado')->default(false)->comment('Usuário clicou no imóvel?');
            $table->boolean('viu_fotos')->default(false)->comment('Usuário viu fotos?');
            $table->boolean('agendou_visita')->default(false)->comment('Usuário agendou visita?');
            $table->boolean('salvou_favorito')->default(false)->comment('Usuário salvou como favorito?');
            $table->integer('cliques_total')->default(0)->comment('Total de cliques');
            
            // Contexto do match
            $table->json('user_slots')->nullable()->comment('Slots do usuário no momento do match');
            $table->string('objetivo')->comment('Objetivo: comprar, alugar, vender, etc.');
            $table->timestamp('data_match')->useCurrent()->comment('Quando o match foi gerado');
            
            // Timestamps
            $table->timestamps();
            
            // Índices para queries rápidas
            $table->index(['numero_cliente', 'data_match']);
            $table->index(['categoria', 'score']);
            $table->index(['foi_clicado', 'data_match']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_matches_tracking');
    }
}
