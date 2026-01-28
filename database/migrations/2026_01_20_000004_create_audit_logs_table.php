<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('cliente_jid')->nullable();
            
            // O que aconteceu
            $table->string('acao'); // 'recomendacao', 'objecao_detectada', 'escalacao', etc
            $table->json('dados_acao'); // dados JSON completos para auditoria
            
            // Contexto da recomendação (para investigar reclamações)
            $table->unsignedBigInteger('imovel_id')->nullable();
            $table->integer('score_calculado')->nullable();
            $table->json('criterios_score')->nullable(); // detalhe de cada critério
            
            // Rastreabilidade
            $table->string('decisao_motivo')->nullable();
            $table->boolean('foi_sobrescrita')->default(false);
            $table->string('sobrescrita_por')->nullable();
            
            $table->timestamps();
            
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->index(['cliente_jid', 'acao']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
