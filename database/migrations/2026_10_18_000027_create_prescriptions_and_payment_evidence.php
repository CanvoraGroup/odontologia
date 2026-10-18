<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('odo_recetas')) Schema::create('odo_recetas', function (Blueprint $t) {
            $t->id('id_receta'); $t->unsignedBigInteger('id_clinica'); $t->unsignedBigInteger('id_atencion'); $t->unsignedBigInteger('id_paciente'); $t->unsignedBigInteger('id_odontologo'); $t->string('numero_receta',30); $t->unsignedInteger('version')->default(1); $t->date('fecha_emision'); $t->text('indicaciones_generales')->nullable(); $t->enum('estado',['EMITIDA','REEMPLAZADA','ANULADA'])->default('EMITIDA'); $t->unsignedBigInteger('id_receta_anterior')->nullable(); $t->unsignedBigInteger('id_usuario_creacion')->nullable(); $t->unsignedBigInteger('id_usuario_modificacion')->nullable(); $t->unsignedBigInteger('id_usuario_anulacion')->nullable(); $t->timestamp('fecha_anulacion')->nullable(); $t->text('motivo_anulacion')->nullable(); $t->timestamps(); $t->unique(['id_clinica','numero_receta']); $t->index(['id_clinica','id_atencion']);
            $t->foreign('id_clinica')->references('id_clinica')->on('odo_clinicas'); $t->foreign('id_atencion')->references('id_atencion')->on('odo_atenciones'); $t->foreign('id_paciente')->references('id_paciente')->on('odo_pacientes'); $t->foreign('id_odontologo')->references('id_odontologo')->on('odo_odontologos'); $t->foreign('id_receta_anterior')->references('id_receta')->on('odo_recetas');
        });
        if (!Schema::hasTable('odo_receta_detalles')) Schema::create('odo_receta_detalles', function (Blueprint $t) {
            $t->id('id_detalle'); $t->unsignedBigInteger('id_clinica'); $t->unsignedBigInteger('id_receta'); $t->unsignedInteger('orden')->default(1); $t->string('medicamento',180); $t->string('presentacion',180); $t->string('dosis',120); $t->string('via_administracion',120); $t->string('frecuencia',120); $t->string('duracion',120); $t->decimal('cantidad',10,2); $t->string('unidad',80); $t->text('indicaciones')->nullable(); $t->unsignedBigInteger('id_usuario_creacion')->nullable(); $t->unsignedBigInteger('id_usuario_modificacion')->nullable(); $t->timestamps(); $t->index(['id_clinica','id_receta']); $t->foreign('id_clinica')->references('id_clinica')->on('odo_clinicas'); $t->foreign('id_receta')->references('id_receta')->on('odo_recetas');
        });
        if (!Schema::hasTable('odo_pago_evidencias')) Schema::create('odo_pago_evidencias', function (Blueprint $t) {
            $t->id('id_evidencia'); $t->unsignedBigInteger('id_clinica'); $t->unsignedBigInteger('id_pago'); $t->string('ruta_privada',500); $t->string('nombre_original',255); $t->string('mime_type',100); $t->unsignedBigInteger('tamano_bytes'); $t->string('hash_sha256',64); $t->enum('estado',['ACTIVA','RETIRADA'])->default('ACTIVA'); $t->unsignedBigInteger('id_usuario_creacion')->nullable(); $t->unsignedBigInteger('id_usuario_modificacion')->nullable(); $t->unsignedBigInteger('id_usuario_retiro')->nullable(); $t->timestamp('fecha_retiro')->nullable(); $t->text('motivo_retiro')->nullable(); $t->timestamps(); $t->index(['id_clinica','id_pago','estado']); $t->foreign('id_clinica')->references('id_clinica')->on('odo_clinicas'); $t->foreign('id_pago')->references('id_pago')->on('odo_pagos');
        });
    }
    public function down(): void { Schema::dropIfExists('odo_pago_evidencias'); Schema::dropIfExists('odo_receta_detalles'); Schema::dropIfExists('odo_recetas'); }
};
