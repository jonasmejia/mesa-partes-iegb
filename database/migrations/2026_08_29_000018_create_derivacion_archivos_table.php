<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('derivacion_archivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('derivacion_id');
            $table->string('nombre_original', 255);
            $table->string('nombre_guardado', 255);
            $table->string('ruta', 500);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('tamano_bytes');
            $table->string('hash_sha256', 64)->nullable();
            $table->unsignedBigInteger('subido_por')->nullable();
            $table->timestamps();

            $table->foreign('derivacion_id', 'dera_der_fk')->references('id')->on('derivaciones')->cascadeOnDelete();
            $table->foreign('subido_por', 'dera_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('derivacion_archivos');
    }
};
