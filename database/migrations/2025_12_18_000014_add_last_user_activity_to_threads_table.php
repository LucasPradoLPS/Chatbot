<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->timestamp('ultima_atividade_usuario')->nullable()->after('thread_id')->comment('Rastreia quando o usuário enviou a última mensagem');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ultima_atividade_usuario');
    }
};
