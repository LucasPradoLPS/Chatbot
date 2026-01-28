<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('threads', function (Blueprint $table) {
            // Adiciona coluna para rastrear última atividade do usuário
            // Usada para timeout de inatividade em handoff
            if (!Schema::hasColumn('threads', 'ultima_atividade_usuario')) {
                $table->timestamp('ultima_atividade_usuario')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('threads', function (Blueprint $table) {
            if (Schema::hasColumn('threads', 'ultima_atividade_usuario')) {
                $table->dropColumn('ultima_atividade_usuario');
            }
        });
    }
};
