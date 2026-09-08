<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expedientes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->unsignedSmallInteger('anio')->index();
            $table->string('asunto', 500);
            $table->unsignedBigInteger('estado_id');
            $table->unsignedBigInteger('area_actual_id')->nullable();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->dateTime('fecha_apertura')->useCurrent();
            $table->dateTime('fecha_cierre')->nullable();
            $table->timestamps();

            $table->foreign('estado_id', 'exp_estado_fk')->references('id')->on('estados')->restrictOnDelete();
            $table->foreign('area_actual_id', 'exp_area_fk')->references('id')->on('areas')->nullOnDelete();
            $table->foreign('creado_por', 'exp_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expedientes');
    }
};
