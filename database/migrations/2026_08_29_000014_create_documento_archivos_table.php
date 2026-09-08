<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_archivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_id');
            $table->string('tipo', 30)->default('PRINCIPAL')->index();
            $table->string('nombre_original', 255);
            $table->string('nombre_guardado', 255);
            $table->string('ruta', 500);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('tamano_bytes');
            $table->string('hash_sha256', 64)->nullable()->index();
            $table->unsignedBigInteger('subido_por')->nullable();
            $table->boolean('es_principal')->default(false);
            $table->timestamps();

            $table->foreign('documento_id', 'da_doc_fk')->references('id')->on('documentos')->cascadeOnDelete();
            $table->foreign('subido_por', 'da_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['documento_id', 'es_principal'], 'da_doc_principal_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_archivos');
    }
};
