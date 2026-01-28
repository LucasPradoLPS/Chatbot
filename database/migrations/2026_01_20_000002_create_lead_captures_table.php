<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_captures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('cliente_jid')->unique();
            $table->string('cliente_nome')->nullable();
            $table->string('cliente_telefone')->nullable();
            
            // Captura completa
            $table->decimal('renda_aproximada', 12, 2)->nullable(); // em R$
            $table->string('tipo_financiamento')->nullable(); // a_vista / financiamento / parcelado / consorcio / misto
            $table->integer('prazo_desejado_anos')->nullable(); // 1-35
            $table->enum('urgencia', ['alta', 'media', 'baixa'])->nullable();
            $table->boolean('tem_pre_aprovacao')->default(false);
            $table->string('pre_aprovacao_valor')->nullable();
            $table->string('pre_aprovacao_banco')->nullable();
            
            // Localização não-negociável
            $table->string('cidade_principal')->nullable();
            $table->json('bairros_nao_negociaveis')->nullable(); // ["Vila Mariana", "Pinheiros"]
            
            // Top 3 prioridades
            $table->json('top_3_prioridades')->nullable(); // ["pet_friendly", "varanda", "2_quartos"]
            
            // Preferências aprendidas (likes/dislikes)
            $table->json('imoveis_gostou')->nullable(); // [1, 3, 5]
            $table->json('imoveis_descartou')->nullable(); // [2, 4]
            $table->json('preferencias_descartadas')->nullable(); // ["terreo", "fundos"]
            
            // Seguimento
            $table->dateTime('ultimo_contato_em')->nullable();
            $table->enum('status_lead', [
                'novo',
                'qualificado',
                'em_busca',
                'visitando',
                'em_proposta',
                'perdido',
                'convertido'
            ])->default('novo');
            
            $table->integer('dias_inativo')->default(0);
            $table->boolean('enviou_follow_up_1')->default(false);
            $table->boolean('enviou_follow_up_2')->default(false);
            $table->dateTime('proximo_follow_up_em')->nullable();
            
            // Consentimentos (LGPD)
            $table->boolean('consentimento_dados')->default(false);
            $table->dateTime('consentimento_dados_em')->nullable();
            $table->boolean('consentimento_marketing')->default(false);
            $table->dateTime('consentimento_marketing_em')->nullable();
            
            $table->timestamps();
            $table->softDeletes(); // Para LGPD (permite "deleção lógica")
            
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->index(['status_lead', 'dias_inativo']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_captures');
    }
};
