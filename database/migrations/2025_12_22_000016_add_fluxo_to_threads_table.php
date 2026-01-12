<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->string('etapa_fluxo')->default('boas_vindas')->after('slots')->comment('Etapa atual do macro fluxo: boas_vindas, lgpd, objetivo, coleta, etc.');
            $table->string('objetivo')->nullable()->after('etapa_fluxo')->comment('Objetivo selecionado: comprar, alugar, vender, anunciar_aluguel, investir, atendimento_humano');
            $table->boolean('lgpd_consentimento')->default(false)->after('objetivo')->comment('Consentimento LGPD do usuário');
        });
    }

    public function down()
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->dropColumn(['etapa_fluxo', 'objetivo', 'lgpd_consentimento']);
        });
    }
};
