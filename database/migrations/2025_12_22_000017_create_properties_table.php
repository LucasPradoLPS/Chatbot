<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('codigo_propriedade')->unique();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('tipo_imovel'); // apto/casa/terreno/comercial/kitnet
            $table->string('bairro');
            $table->string('endereco');
            $table->string('numero')->nullable();
            $table->string('complemento')->nullable();
            $table->string('cep')->nullable();
            $table->string('cidade');
            $table->string('estado');
            $table->decimal('preco', 14, 2)->nullable();
            $table->decimal('preco_aluguel', 10, 2)->nullable();
            $table->integer('quartos')->default(0);
            $table->integer('banheiros')->default(0);
            $table->integer('vagas')->default(0);
            $table->decimal('area_total', 10, 2)->nullable();
            $table->decimal('area_util', 10, 2)->nullable();
            $table->decimal('condominio', 10, 2)->nullable();
            $table->decimal('iptu', 10, 2)->nullable();
            $table->json('tags')->nullable();
            $table->json('fotos')->nullable(); // array de URLs/paths
            $table->string('maps_url')->nullable();
            $table->decimal('maps_lat', 10, 8)->nullable();
            $table->decimal('maps_lng', 11, 8)->nullable();
            $table->string('status')->default('ativo'); // ativo/inativo/vendido/alugado
            $table->date('disponivel_desde')->nullable();
            $table->timestamp('publicado_em')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['empresa_id', 'bairro']);
            $table->index(['empresa_id', 'preco']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
