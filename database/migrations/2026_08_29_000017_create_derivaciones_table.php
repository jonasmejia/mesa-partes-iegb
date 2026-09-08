<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('derivaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_id');
            $table->unsignedBigInteger('area_origen_id');
            $table->unsignedBigInteger('area_destino_id');
            $table->unsignedBigInteger('derivado_por');
            $table->unsignedBigInteger('responsable_destino_id')->nullable();
            $table->unsignedBigInteger('estado_id');
            $table->text('indicacion')->nullable();
            $table->dateTime('fecha_derivacion')->useCurrent();
            $table->dateTime('fecha_limite')->nullable()->index();
            $table->dateTime('fecha_recepcion')->nullable();
            $table->dateTime('fecha_atencion')->nullable();
            $table->timestamps();

            $table->foreign('documento_id', 'der_doc_fk')->references('id')->on('documentos')->cascadeOnDelete();
            $table->foreign('area_origen_id', 'der_area_ori_fk')->references('id')->on('areas')->restrictOnDelete();
            $table->foreign('area_destino_id', 'der_area_des_fk')->references('id')->on('areas')->restrictOnDelete();
            $table->foreign('derivado_por', 'der_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('responsable_destino_id', 'der_resp_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('estado_id', 'der_estado_fk')->references('id')->on('estados')->restrictOnDelete();

            $table->index(['documento_id', 'fecha_derivacion'], 'der_doc_fecha_idx');
            $table->index(['area_destino_id', 'estado_id'], 'der_destino_estado_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('derivaciones');
    }
};
