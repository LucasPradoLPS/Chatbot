<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('numero_cliente');
            $table->string('event_type'); // lead_created, property_viewed, visit_scheduled, proposal_sent, fechado, perdido, etc.
            $table->unsignedBigInteger('property_id')->nullable();
            $table->json('metadata')->nullable(); // contexto adicional (valores, motivos, etc.)
            $table->timestamps();

            $table->index(['empresa_id', 'numero_cliente']);
            $table->index(['event_type', 'created_at']);
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_logs');
    }
};
