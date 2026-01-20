<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('cliente_jid'); // 5511999785770@s.whatsapp.net
            $table->string('cliente_nome')->nullable();
            $table->unsignedBigInteger('imovel_id')->nullable();
            $table->string('imovel_titulo')->nullable();
            $table->dateTime('data_agendada');
            $table->enum('status', [
                'pendente_confirmacao',
                'confirmada',
                'realizada',
                'cancelada',
                'reagendada'
            ])->default('pendente_confirmacao');
            $table->string('confirmation_token')->unique();
            $table->dateTime('confirmada_em')->nullable();
            $table->text('observacoes')->nullable();
            $table->string('corretor_atribuido')->nullable();
            $table->dateTime('lembrete_enviado_em')->nullable();
            $table->timestamps();
            
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->index(['cliente_jid', 'empresa_id']);
            $table->index(['status', 'data_agendada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
