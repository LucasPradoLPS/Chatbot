<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Índices para threads - queries frequentes por numero_cliente
        Schema::table('threads', function (Blueprint $table) {
            if (Schema::hasColumn('threads', 'numero_cliente')) {
                $table->index('numero_cliente', 'idx_threads_numero_cliente');
                $table->index(['numero_cliente', 'empresa_id'], 'idx_threads_numero_cliente_empresa');
            }
            if (Schema::hasColumn('threads', 'empresa_id')) {
                $table->index('empresa_id', 'idx_threads_empresa_id');
            }
            if (Schema::hasColumn('threads', 'agente_id')) {
                $table->index('agente_id', 'idx_threads_agente_id');
            }
            if (Schema::hasColumn('threads', 'thread_id')) {
                $table->index('thread_id', 'idx_threads_thread_id');
            }
            if (Schema::hasColumn('threads', 'created_at')) {
                $table->index('created_at', 'idx_threads_created_at');
            }
        });

        // Índices para mensagens - filtragens frequentes
        if (Schema::hasTable('mensagens')) {
            Schema::table('mensagens', function (Blueprint $table) {
                if (Schema::hasColumn('mensagens', 'thread_id')) {
                    $table->index('thread_id', 'idx_mensagens_thread_id');
                    $table->index(['thread_id', 'created_at'], 'idx_mensagens_thread_created');
                }
                if (Schema::hasColumn('mensagens', 'numero_cliente')) {
                    $table->index('numero_cliente', 'idx_mensagens_numero_cliente');
                }
                if (Schema::hasColumn('mensagens', 'created_at')) {
                    $table->index('created_at', 'idx_mensagens_created_at');
                }
            });
        }

        // Índices para instancias - lookups rápidos
        if (Schema::hasTable('instancia_whatsapps')) {
            Schema::table('instancia_whatsapps', function (Blueprint $table) {
                if (Schema::hasColumn('instancia_whatsapps', 'instance_name')) {
                    $table->index('instance_name', 'idx_instancia_name');
                }
                if (Schema::hasColumn('instancia_whatsapps', 'empresa_id')) {
                    $table->index('empresa_id', 'idx_instancia_empresa_id');
                }
            });
        }

        // Índices para agentes
        if (Schema::hasTable('agentes')) {
            Schema::table('agentes', function (Blueprint $table) {
                if (Schema::hasColumn('agentes', 'empresa_id')) {
                    $table->index('empresa_id', 'idx_agentes_empresa_id');
                }
                if (Schema::hasColumn('agentes', 'ia_ativa')) {
                    $table->index('ia_ativa', 'idx_agentes_ia_ativa');
                }
            });
        }

        // Índices para agentes gerados
        if (Schema::hasTable('agente_gerados')) {
            Schema::table('agente_gerados', function (Blueprint $table) {
                if (Schema::hasColumn('agente_gerados', 'empresa_id')) {
                    $table->index('empresa_id', 'idx_agente_gerados_empresa_id');
                }
                if (Schema::hasColumn('agente_gerados', 'agente_base_id')) {
                    $table->index('agente_base_id', 'idx_agente_gerados_base_id');
                }
            });
        }

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
            // Seguro: drop somente índices que podem existir
            try { $table->dropIndex('idx_threads_numero_cliente'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_threads_numero_cliente_empresa'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_threads_empresa_id'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_threads_agente_id'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_threads_thread_id'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_threads_created_at'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_threads_cliente_id'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_threads_cliente_empresa'); } catch (\Exception $e) {}
        });

        if (Schema::hasTable('mensagens')) {
            Schema::table('mensagens', function (Blueprint $table) {
                try { $table->dropIndex('idx_mensagens_thread_id'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_mensagens_numero_cliente'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_mensagens_created_at'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_mensagens_thread_created'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_mensagens_cliente_id'); } catch (\Exception $e) {}
            });
        }

        if (Schema::hasTable('instancia_whatsapps')) {
            Schema::table('instancia_whatsapps', function (Blueprint $table) {
                try { $table->dropIndex('idx_instancia_name'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_instancia_empresa_id'); } catch (\Exception $e) {}
            });
        }

        if (Schema::hasTable('agentes')) {
            Schema::table('agentes', function (Blueprint $table) {
                try { $table->dropIndex('idx_agentes_empresa_id'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_agentes_ia_ativa'); } catch (\Exception $e) {}
            });
        }

        if (Schema::hasTable('agente_gerados')) {
            Schema::table('agente_gerados', function (Blueprint $table) {
                try { $table->dropIndex('idx_agente_gerados_empresa_id'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_agente_gerados_base_id'); } catch (\Exception $e) {}
            });
        }

        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                try { $table->dropIndex('idx_jobs_queue'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_jobs_created_at'); } catch (\Exception $e) {}
            });
        }
    }
};
