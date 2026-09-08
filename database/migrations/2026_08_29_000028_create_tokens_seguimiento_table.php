<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tokens_seguimiento', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_id');
            $table->string('token_hash', 64)->unique();
            $table->string('proposito', 40)->default('CONSULTA');
            $table->dateTime('expira_en')->nullable()->index();
            $table->dateTime('ultimo_uso_en')->nullable();
            $table->unsignedInteger('usos')->default(0);
            $table->boolean('revocado')->default(false)->index();
            $table->timestamps();

            $table->foreign('documento_id', 'ts_doc_fk')->references('id')->on('documentos')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tokens_seguimiento');
    }
};
