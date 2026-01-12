<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->string('intent')->nullable()->after('lgpd_consentimento')->comment('Intenção detectada: saudacao, comprar_imovel, alugar_imovel, vender_imovel, etc.');
        });
    }

    public function down()
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->dropColumn('intent');
        });
    }
};
