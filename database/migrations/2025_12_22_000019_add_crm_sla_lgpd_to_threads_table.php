<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('threads', function (Blueprint $table) {
            // CRM Pipeline
            $table->string('crm_status')->default('novo_lead')->after('refino_ciclos');
            // novo_lead → qualificado → em_visita → proposta_enviada → fechado / perdido

            // SLA & Follow-up
            $table->timestamp('ultimo_contato')->nullable()->after('crm_status');
            $table->timestamp('proximo_followup')->nullable()->after('ultimo_contato');
            $table->integer('followup_tentativas')->default(0)->after('proximo_followup');

            // Loss tracking
            $table->string('motivo_perda')->nullable()->after('followup_tentativas');
            // preço / localização / estado / documentacao / outro

            // LGPD
            $table->timestamp('lgpd_consentimento_data')->nullable()->after('motivo_perda');
            $table->boolean('lgpd_opt_out')->default(false)->after('lgpd_consentimento_data');
            $table->string('lgpd_politica_versao')->nullable()->after('lgpd_opt_out');

            $table->index(['crm_status', 'updated_at']);
            $table->index(['proximo_followup']);
        });
    }

    public function down(): void
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->dropIndex(['crm_status', 'updated_at']);
            $table->dropIndex(['proximo_followup']);
            $table->dropColumn([
                'crm_status',
                'ultimo_contato',
                'proximo_followup',
                'followup_tentativas',
                'motivo_perda',
                'lgpd_consentimento_data',
                'lgpd_opt_out',
                'lgpd_politica_versao',
            ]);
        });
    }
};
