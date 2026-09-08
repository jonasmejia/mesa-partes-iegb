<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expediente_documentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expediente_id');
            $table->unsignedBigInteger('documento_id');
            $table->string('relacion', 30)->default('PRINCIPAL');
            $table->unsignedInteger('orden')->default(1);
            $table->timestamps();

            $table->foreign('expediente_id', 'ed_exp_fk')->references('id')->on('expedientes')->cascadeOnDelete();
            $table->foreign('documento_id', 'ed_doc_fk')->references('id')->on('documentos')->cascadeOnDelete();
            $table->unique(['expediente_id', 'documento_id'], 'ed_exp_doc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expediente_documentos');
    }
};
