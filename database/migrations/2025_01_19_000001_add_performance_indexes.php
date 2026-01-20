<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Índices para threads - queries frequentes por cliente_id
        Schema::table('threads', function (Blueprint $table) {
            $table->index('cliente_id', 'idx_threads_cliente_id');
            $table->index('empresa_id', 'idx_threads_empresa_id');
            $table->index('agente_id', 'idx_threads_agente_id');
            $table->index('thread_id', 'idx_threads_thread_id');
            $table->index(['cliente_id', 'empresa_id'], 'idx_threads_cliente_empresa');
            $table->index('created_at', 'idx_threads_created_at');
        });

        // Índices para mensagens - filtragens frequentes
        Schema::table('mensagens', function (Blueprint $table) {
            $table->index('thread_id', 'idx_mensagens_thread_id');
            $table->index('cliente_id', 'idx_mensagens_cliente_id');
            $table->index('created_at', 'idx_mensagens_created_at');
            $table->index(['thread_id', 'created_at'], 'idx_mensagens_thread_created');
        });

        // Índices para instancias - lookups rápidos
        Schema::table('instancia_whatsapps', function (Blueprint $table) {
            $table->index('instance_name', 'idx_instancia_name');
            $table->index('empresa_id', 'idx_instancia_empresa_id');
        });

        // Índices para agentes
        Schema::table('agentes', function (Blueprint $table) {
            $table->index('empresa_id', 'idx_agentes_empresa_id');
            $table->index('ia_ativa', 'idx_agentes_ia_ativa');
        });

        // Índices para agentes gerados
        Schema::table('agente_gerados', function (Blueprint $table) {
            $table->index('empresa_id', 'idx_agente_gerados_empresa_id');
            $table->index('agente_base_id', 'idx_agente_gerados_base_id');
        });

        // Índices para fila de jobs (performance)
        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->index('queue', 'idx_jobs_queue');
                $table->index('created_at', 'idx_jobs_created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->dropIndex('idx_threads_cliente_id');
            $table->dropIndex('idx_threads_empresa_id');
            $table->dropIndex('idx_threads_agente_id');
            $table->dropIndex('idx_threads_thread_id');
            $table->dropIndex('idx_threads_cliente_empresa');
            $table->dropIndex('idx_threads_created_at');
        });

        Schema::table('mensagens', function (Blueprint $table) {
            $table->dropIndex('idx_mensagens_thread_id');
            $table->dropIndex('idx_mensagens_cliente_id');
            $table->dropIndex('idx_mensagens_created_at');
            $table->dropIndex('idx_mensagens_thread_created');
        });

        Schema::table('instancia_whatsapps', function (Blueprint $table) {
            $table->dropIndex('idx_instancia_name');
            $table->dropIndex('idx_instancia_empresa_id');
        });

        Schema::table('agentes', function (Blueprint $table) {
            $table->dropIndex('idx_agentes_empresa_id');
            $table->dropIndex('idx_agentes_ia_ativa');
        });

        Schema::table('agente_gerados', function (Blueprint $table) {
            $table->dropIndex('idx_agente_gerados_empresa_id');
            $table->dropIndex('idx_agente_gerados_base_id');
        });

        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->dropIndex('idx_jobs_queue');
                $table->dropIndex('idx_jobs_created_at');
            });
        }
    }
};
