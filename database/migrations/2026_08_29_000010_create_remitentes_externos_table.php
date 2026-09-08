<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remitentes_externos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_persona', 20)->default('NATURAL');
            $table->string('tipo_documento_identidad', 20)->nullable();
            $table->string('numero_documento_identidad', 30)->nullable()->index();
            $table->string('nombres', 120)->nullable();
            $table->string('apellidos', 160)->nullable();
            $table->string('razon_social', 200)->nullable();
            $table->string('correo', 191)->nullable()->index();
            $table->string('telefono', 30)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('ubigeo', 6)->nullable()->index();
            $table->timestamps();

            $table->index(['tipo_documento_identidad', 'numero_documento_identidad'], 're_doc_identidad_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remitentes_externos');
    }
};
