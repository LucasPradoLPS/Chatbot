<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->string('estado_atual')->default('STATE_START')->after('intent')->comment('Estado atual da máquina de estados');
            $table->json('estado_historico')->nullable()->after('estado_atual')->comment('Histórico de transições de estado');
        });
    }

    public function down()
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->dropColumn(['estado_atual', 'estado_historico']);
        });
    }
};
