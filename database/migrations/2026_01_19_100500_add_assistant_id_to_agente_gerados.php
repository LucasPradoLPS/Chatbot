<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('agente_gerados', function (Blueprint $table) {
            if (!Schema::hasColumn('agente_gerados', 'assistant_id')) {
                $table->string('assistant_id')->nullable()->after('agente_base_id');
            }
        });
    }

    public function down()
    {
        Schema::table('agente_gerados', function (Blueprint $table) {
            if (Schema::hasColumn('agente_gerados', 'assistant_id')) {
                $table->dropColumn('assistant_id');
            }
        });
    }
};
