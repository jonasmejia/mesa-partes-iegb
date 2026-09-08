<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_cargos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('cargo_id');
            $table->unsignedBigInteger('area_id');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('es_responsable')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('user_id', 'uc_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('cargo_id', 'uc_cargo_fk')->references('id')->on('cargos')->restrictOnDelete();
            $table->foreign('area_id', 'uc_area_fk')->references('id')->on('areas')->restrictOnDelete();

            $table->index(['user_id', 'activo'], 'uc_user_activo_idx');
            $table->index(['area_id', 'activo'], 'uc_area_activo_idx');
            $table->index(['cargo_id', 'activo'], 'uc_cargo_activo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_cargos');
    }
};
