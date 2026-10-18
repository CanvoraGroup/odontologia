<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('odo_planes_tratamiento')) {
            Schema::table('odo_planes_tratamiento', function (Blueprint $table) {
                if (! Schema::hasColumn('odo_planes_tratamiento', 'numero')) $table->string('numero', 30)->nullable()->after('id_plan_tratamiento');
                if (! Schema::hasColumn('odo_planes_tratamiento', 'id_atencion')) $table->unsignedBigInteger('id_atencion')->nullable()->after('id_paciente');
                if (! Schema::hasColumn('odo_planes_tratamiento', 'fecha_plan')) $table->date('fecha_plan')->nullable()->after('nombre');
                if (! Schema::hasColumn('odo_planes_tratamiento', 'observaciones')) $table->text('observaciones')->nullable()->after('descripcion');
                if (! Schema::hasColumn('odo_planes_tratamiento', 'estado_clinico')) $table->enum('estado_clinico', ['PLANIFICADO', 'EN_PROCESO', 'FINALIZADO', 'ANULADO'])->default('PLANIFICADO')->after('estado');
                if (! Schema::hasColumn('odo_planes_tratamiento', 'id_usuario_creacion')) $table->unsignedBigInteger('id_usuario_creacion')->nullable();
                if (! Schema::hasColumn('odo_planes_tratamiento', 'lock_version')) $table->unsignedInteger('lock_version')->default(0);
            });
            $indexes = collect(DB::select('SHOW INDEX FROM odo_planes_tratamiento'));
            $constraints = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'odo_planes_tratamiento'"))->pluck('CONSTRAINT_NAME');
            Schema::table('odo_planes_tratamiento', function (Blueprint $table) use ($indexes, $constraints) {
                if (! $indexes->contains(fn ($index) => ($index->Key_name ?? null) === 'uk_odo_planes_numero')) $table->unique('numero', 'uk_odo_planes_numero');
                if (! $constraints->contains('fk_odo_planes_atencion')) $table->foreign('id_atencion', 'fk_odo_planes_atencion')->references('id_atencion')->on('odo_atenciones');
                if (! $constraints->contains('fk_odo_planes_usuario')) $table->foreign('id_usuario_creacion', 'fk_odo_planes_usuario')->references('id_usuario')->on('odo_usuarios');
            });
        } else {
            Schema::create('odo_planes_tratamiento', function (Blueprint $table) {
                $table->id('id_plan_tratamiento');
                $table->string('numero', 30)->unique();
                $table->unsignedBigInteger('id_paciente');
                $table->unsignedBigInteger('id_odontologo')->nullable();
                $table->unsignedBigInteger('id_atencion')->nullable();
                $table->string('nombre', 150);
                $table->date('fecha_plan')->nullable();
                $table->text('descripcion')->nullable();
                $table->text('observaciones')->nullable();
                $table->decimal('monto_total', 10, 2)->default(0);
                $table->enum('estado', ['PENDIENTE','APROBADO','EN_PROCESO','FINALIZADO','ANULADO'])->default('PENDIENTE');
                $table->enum('estado_clinico', ['PLANIFICADO','EN_PROCESO','FINALIZADO','ANULADO'])->default('PLANIFICADO');
                $table->unsignedBigInteger('id_usuario_creacion')->nullable();
                $table->unsignedInteger('lock_version')->default(0);
                $table->timestamps();
                $table->foreign('id_paciente')->references('id_paciente')->on('odo_pacientes');
                $table->foreign('id_odontologo')->references('id_odontologo')->on('odo_odontologos');
                $table->foreign('id_atencion')->references('id_atencion')->on('odo_atenciones');
                $table->foreign('id_usuario_creacion')->references('id_usuario')->on('odo_usuarios');
            });
        }

        if (Schema::hasTable('odo_pagos') && Schema::hasColumn('odo_pagos', 'id_plan_tratamiento')) {
            // La FK existente seguirá apuntando al mismo encabezado del plan.
        }

        if (! Schema::hasTable('odo_presupuesto_versiones')) {
            Schema::create('odo_presupuesto_versiones', function (Blueprint $table) {
                $table->id('id_version');
                $table->unsignedBigInteger('id_plan_tratamiento');
                $table->unsignedInteger('numero_version');
                $table->enum('estado', ['BORRADOR','ENVIADO','ACEPTADO','RECHAZADO'])->default('BORRADOR');
                $table->date('vigencia_hasta')->nullable();
                $table->text('condiciones')->nullable();
                $table->decimal('total', 10, 2)->default(0);
                $table->unsignedBigInteger('id_usuario_creacion')->nullable();
                $table->unsignedBigInteger('id_usuario_envio')->nullable();
                $table->dateTime('enviado_at')->nullable();
                $table->unsignedBigInteger('id_usuario_respuesta')->nullable();
                $table->dateTime('respondido_at')->nullable();
                $table->text('observacion_respuesta')->nullable();
                $table->unsignedInteger('lock_version')->default(0);
                $table->timestamps();
                $table->unique(['id_plan_tratamiento', 'numero_version'], 'uk_odo_presupuesto_version');
                $table->foreign('id_plan_tratamiento')->references('id_plan_tratamiento')->on('odo_planes_tratamiento');
                $table->foreign('id_usuario_creacion')->references('id_usuario')->on('odo_usuarios');
                $table->foreign('id_usuario_envio')->references('id_usuario')->on('odo_usuarios');
                $table->foreign('id_usuario_respuesta')->references('id_usuario')->on('odo_usuarios');
            });
        }

        if (! Schema::hasTable('odo_presupuesto_detalles')) {
            Schema::create('odo_presupuesto_detalles', function (Blueprint $table) {
                $table->id('id_detalle');
                $table->unsignedBigInteger('id_version');
                $table->unsignedBigInteger('id_servicio')->nullable();
                $table->string('codigo_servicio', 30)->nullable();
                $table->string('nombre_servicio', 150);
                $table->string('forma_cobro', 20);
                $table->boolean('requiere_pieza')->default(false);
                $table->boolean('requiere_caras')->default(false);
                $table->decimal('precio_catalogo', 10, 2)->default(0);
                $table->decimal('precio_unitario', 10, 2)->default(0);
                $table->decimal('descuento', 10, 2)->default(0);
                $table->unsignedInteger('cantidad')->default(1);
                $table->string('pieza_fdi', 3)->nullable();
                $table->string('caras', 120)->nullable();
                $table->unsignedInteger('prioridad')->nullable();
                $table->unsignedInteger('sesiones_estimadas')->nullable();
                $table->text('observaciones')->nullable();
                $table->text('motivo_precio')->nullable();
                $table->text('motivo_descuento')->nullable();
                $table->decimal('subtotal', 10, 2)->default(0);
                $table->timestamps();
                $table->foreign('id_version')->references('id_version')->on('odo_presupuesto_versiones')->cascadeOnDelete();
                $table->foreign('id_servicio')->references('id_servicio')->on('odo_servicios')->nullOnDelete();
                $table->index(['id_version', 'id_servicio', 'pieza_fdi', 'caras'], 'idx_odo_presupuesto_detalles_busqueda');
            });
        }

        if (Schema::hasTable('odo_presupuesto_detalles')) {
            $hasIndex = collect(DB::select('SHOW INDEX FROM odo_presupuesto_detalles'))
                ->contains(fn ($index) => (($index->Key_name ?? null) === 'idx_odo_presupuesto_detalles_busqueda'));
            if (! $hasIndex) {
                Schema::table('odo_presupuesto_detalles', function (Blueprint $table) {
                    $table->index(['id_version', 'id_servicio', 'pieza_fdi', 'caras'], 'idx_odo_presupuesto_detalles_busqueda');
                });
            }
        }

        if (! Schema::hasTable('odo_plan_eventos')) {
            Schema::create('odo_plan_eventos', function (Blueprint $table) {
                $table->id('id_evento');
                $table->unsignedBigInteger('id_plan_tratamiento');
                $table->unsignedBigInteger('id_version')->nullable();
                $table->string('tipo', 40);
                $table->string('estado_anterior', 30)->nullable();
                $table->string('estado_nuevo', 30)->nullable();
                $table->unsignedBigInteger('id_usuario')->nullable();
                $table->text('observacion')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->foreign('id_plan_tratamiento')->references('id_plan_tratamiento')->on('odo_planes_tratamiento');
                $table->foreign('id_version')->references('id_version')->on('odo_presupuesto_versiones')->nullOnDelete();
                $table->foreign('id_usuario')->references('id_usuario')->on('odo_usuarios')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // No se eliminan tablas ni datos del módulo en reversión automática.
    }
};
