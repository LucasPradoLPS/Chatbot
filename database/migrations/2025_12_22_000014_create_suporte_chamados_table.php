<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('suporte_chamados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('numero_cliente');
            $table->string('nome_cliente')->nullable();
            $table->string('telefone_whatsapp')->nullable();
            $table->string('unidade_endereco');
            $table->string('tipo_problema');
            $table->string('urgencia'); // alta, media, baixa
            $table->string('midia_link')->nullable();
            $table->string('status')->default('aberto'); // aberto, em_andamento, concluido, cancelado
            $table->string('prioridade')->default('normal'); // alta, normal, baixa
            $table->integer('sla_estimativa_horas')->default(48);
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'numero_cliente']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suporte_chamados');
    }
};
