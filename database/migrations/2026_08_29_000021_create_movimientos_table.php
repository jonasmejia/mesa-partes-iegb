<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('area_id')->nullable();
            $table->string('tipo_movimiento', 50)->index();
            $table->unsignedBigInteger('estado_anterior_id')->nullable();
            $table->unsignedBigInteger('estado_nuevo_id')->nullable();
            $table->string('entidad_tipo', 80)->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->text('detalle')->nullable();
            $table->dateTime('fecha_movimiento')->useCurrent()->index();
            $table->timestamps();

            $table->foreign('documento_id', 'mov_doc_fk')->references('id')->on('documentos')->cascadeOnDelete();
            $table->foreign('user_id', 'mov_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('area_id', 'mov_area_fk')->references('id')->on('areas')->nullOnDelete();
            $table->foreign('estado_anterior_id', 'mov_est_ant_fk')->references('id')->on('estados')->nullOnDelete();
            $table->foreign('estado_nuevo_id', 'mov_est_nuevo_fk')->references('id')->on('estados')->nullOnDelete();

            $table->index(['documento_id', 'fecha_movimiento'], 'mov_doc_fecha_idx');
            $table->index(['entidad_tipo', 'entidad_id'], 'mov_entidad_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
