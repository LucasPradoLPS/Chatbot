<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('cliente_jid');
            $table->string('thread_id')->nullable();
            
            // Funil
            $table->timestamp('chegou_em_qualificacao_em')->nullable();
            $table->timestamp('recebeu_opcoes_em')->nullable();
            $table->timestamp('pediu_visita_em')->nullable();
            $table->timestamp('visitou_em')->nullable();
            $table->timestamp('recebeu_proposta_em')->nullable();
            $table->timestamp('converteu_em')->nullable();
            
            // NPS/CSAT (dentro do WhatsApp)
            $table->integer('nps')->nullable(); // 0-10
            $table->integer('csat')->nullable(); // 0-5
            $table->text('feedback_texto')->nullable();
            
            // Por que não converteu (análise automática)
            $table->enum('motivo_nao_conversao', [
                'preco',
                'bairro',
                'timing',
                'falta_opcao',
                'atendimento',
                'financiamento',
                'outro'
            ])->nullable();
            
            // Métricas de conversa
            $table->integer('num_mensagens')->default(0);
            $table->integer('num_imagens_recebidas')->default(0);
            $table->integer('num_opcoes_apresentadas')->default(0);
            $table->integer('num_imoveis_clicados')->default(0);
            $table->integer('tempo_medio_resposta_seg')->nullable();
            
            // Detecção de objeções
            $table->json('objecoes_detectadas')->nullable(); // ["muito_caro", "longe"]
            $table->json('playbooks_usados')->nullable(); // Que tratamentos foram aplicados
            
            $table->timestamps();
            
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->index(['cliente_jid', 'converteu_em']);
            $table->index(['motivo_nao_conversao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_analytics');
    }
};
