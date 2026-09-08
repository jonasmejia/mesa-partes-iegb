<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('origen', 20)->index(); // EXTERNO | INTERNO
            $table->unsignedBigInteger('tipo_documento_id');
            $table->unsignedBigInteger('estado_id');
            $table->unsignedBigInteger('prioridad_id');
            $table->unsignedBigInteger('area_actual_id')->nullable();
            $table->unsignedBigInteger('registrado_por')->nullable();
            $table->string('numero_documento', 100)->nullable();
            $table->unsignedSmallInteger('anio');
            $table->string('sigla', 100)->nullable();
            $table->string('asunto', 500);
            $table->text('descripcion')->nullable();
            $table->unsignedSmallInteger('folios')->default(1);
            $table->dateTime('fecha_documento')->nullable();
            $table->dateTime('fecha_registro')->useCurrent();
            $table->dateTime('fecha_limite')->nullable()->index();
            $table->boolean('confidencial')->default(false);
            $table->boolean('requiere_respuesta')->default(false);
            $table->timestamps();

            $table->foreign('tipo_documento_id', 'doc_tipo_fk')->references('id')->on('tipos_documento')->restrictOnDelete();
            $table->foreign('estado_id', 'doc_estado_fk')->references('id')->on('estados')->restrictOnDelete();
            $table->foreign('prioridad_id', 'doc_prioridad_fk')->references('id')->on('prioridades')->restrictOnDelete();
            $table->foreign('area_actual_id', 'doc_area_actual_fk')->references('id')->on('areas')->nullOnDelete();
            $table->foreign('registrado_por', 'doc_registrado_por_fk')->references('id')->on('users')->nullOnDelete();

            $table->index(['origen', 'anio'], 'doc_origen_anio_idx');
            $table->index(['estado_id', 'area_actual_id'], 'doc_estado_area_idx');
            $table->index(['tipo_documento_id', 'numero_documento', 'anio'], 'doc_tipo_num_anio_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
