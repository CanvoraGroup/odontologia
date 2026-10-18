<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('odo_plan_procedimientos')) {
            Schema::create('odo_plan_procedimientos', function (Blueprint $table) {
                $table->id('id_plan_procedimiento');
                $table->unsignedBigInteger('id_plan_tratamiento');
                $table->unsignedBigInteger('id_detalle_origen');
                $table->unsignedBigInteger('id_version_origen');
                $table->unsignedBigInteger('id_servicio')->nullable();
                $table->string('codigo_servicio', 30)->nullable();
                $table->string('nombre_servicio', 150);
                $table->string('pieza_fdi', 3)->nullable();
                $table->string('caras', 120)->nullable();
                $table->string('estado', 20)->default('PENDIENTE');
                $table->unsignedInteger('orden')->default(0);
                $table->timestamps();
                $table->unique(['id_plan_tratamiento', 'id_detalle_origen'], 'uk_odo_plan_proc_origen');
                $table->foreign('id_plan_tratamiento')->references('id_plan_tratamiento')->on('odo_planes_tratamiento');
                $table->foreign('id_detalle_origen')->references('id_detalle')->on('odo_presupuesto_detalles');
                $table->foreign('id_version_origen')->references('id_version')->on('odo_presupuesto_versiones');
                $table->foreign('id_servicio')->references('id_servicio')->on('odo_servicios')->nullOnDelete();
                $table->index(['id_plan_tratamiento', 'estado']);
            });
        }

        if (! Schema::hasTable('odo_tratamiento_sesiones')) {
            Schema::create('odo_tratamiento_sesiones', function (Blueprint $table) {
                $table->id('id_sesion');
                $table->unsignedBigInteger('id_plan_tratamiento');
                $table->unsignedInteger('numero_sesion');
                $table->dateTime('fecha_sesion')->nullable();
                $table->unsignedBigInteger('id_odontologo')->nullable();
                $table->unsignedBigInteger('id_atencion')->nullable();
                $table->string('estado', 20)->default('BORRADOR');
                $table->text('observaciones')->nullable();
                $table->unsignedBigInteger('id_usuario_creacion')->nullable();
                $table->unsignedBigInteger('id_usuario_finalizacion')->nullable();
                $table->dateTime('finalizada_at')->nullable();
                $table->unsignedInteger('lock_version')->default(0);
                $table->timestamps();
                $table->unique(['id_plan_tratamiento', 'numero_sesion'], 'uk_odo_plan_sesion_numero');
                $table->foreign('id_plan_tratamiento')->references('id_plan_tratamiento')->on('odo_planes_tratamiento');
                $table->foreign('id_odontologo')->references('id_odontologo')->on('odo_odontologos')->nullOnDelete();
                $table->foreign('id_atencion')->references('id_atencion')->on('odo_atenciones')->nullOnDelete();
                $table->foreign('id_usuario_creacion')->references('id_usuario')->on('odo_usuarios')->nullOnDelete();
                $table->foreign('id_usuario_finalizacion')->references('id_usuario')->on('odo_usuarios')->nullOnDelete();
                $table->index(['id_plan_tratamiento', 'estado']);
            });
        }

        if (! Schema::hasTable('odo_tratamiento_sesion_detalles')) {
            Schema::create('odo_tratamiento_sesion_detalles', function (Blueprint $table) {
                $table->id('id_sesion_detalle');
                $table->unsignedBigInteger('id_sesion');
                $table->unsignedBigInteger('id_plan_procedimiento');
                $table->string('estado_resultante', 20);
                $table->text('evolucion')->nullable();
                $table->timestamps();
                $table->unique(['id_sesion', 'id_plan_procedimiento'], 'uk_odo_sesion_proc');
                $table->foreign('id_sesion')->references('id_sesion')->on('odo_tratamiento_sesiones')->cascadeOnDelete();
                $table->foreign('id_plan_procedimiento')->references('id_plan_procedimiento')->on('odo_plan_procedimientos');
            });
        }
    }

    public function down(): void
    {
        // Las estructuras clínicas no se eliminan automáticamente para proteger el historial.
    }
};
