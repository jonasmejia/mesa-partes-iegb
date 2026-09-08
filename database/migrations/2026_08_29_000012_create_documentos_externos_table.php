<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_externos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_id')->unique();
            $table->unsignedBigInteger('remitente_externo_id');
            $table->string('canal_ingreso', 30)->default('MESA_VIRTUAL')->index();
            $table->string('codigo_seguimiento', 80)->unique();
            $table->string('ip_registro', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->dateTime('fecha_recepcion')->nullable();
            $table->unsignedBigInteger('recepcionado_por')->nullable();
            $table->text('observacion_recepcion')->nullable();
            $table->timestamps();

            $table->foreign('documento_id', 'de_doc_fk')->references('id')->on('documentos')->cascadeOnDelete();
            $table->foreign('remitente_externo_id', 'de_remitente_fk')->references('id')->on('remitentes_externos')->restrictOnDelete();
            $table->foreign('recepcionado_por', 'de_recepcionista_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_externos');
    }
};
