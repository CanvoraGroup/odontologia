<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $duplicates = [
        'odo_atenciones' => ['fk_atenciones_clinica'],
        'odo_categorias_servicios' => ['fk_categorias_servicios_clinica'],
        'odo_citas' => ['fk_citas_clinica'],
        'odo_comprobantes' => ['fk_comprobantes_clinica'],
        'odo_consultorios' => ['fk_consultorios_clinica'],
        'odo_historias_clinicas' => ['fk_historias_clinicas_clinica'],
        'odo_odontogramas' => ['fk_odontogramas_clinica'],
        'odo_odontologos' => ['fk_odontologos_clinica'],
        'odo_pacientes' => ['fk_pacientes_clinica'],
        'odo_pago_receptores' => ['fk_pago_receptores_clinica'],
        'odo_pagos' => ['fk_pagos_clinica'],
        'odo_plan_eventos' => ['fk_plan_eventos_clinica'],
        'odo_plan_procedimientos' => ['fk_plan_procedimientos_clinica'],
        'odo_planes_tratamiento' => ['fk_planes_tratamiento_clinica'],
        'odo_presupuesto_detalles' => ['fk_presupuesto_detalles_clinica'],
        'odo_presupuesto_versiones' => ['fk_presupuesto_versiones_clinica'],
        'odo_servicios' => ['fk_servicios_clinica'],
        'odo_tratamiento_sesion_detalles' => ['fk_tratamiento_sesion_detalles_clinica'],
        'odo_tratamiento_sesiones' => ['fk_tratamiento_sesiones_clinica'],
    ];

    public function up(): void
    {
        foreach ($this->duplicates as $tableName => $names) {
            if (! Schema::hasTable($tableName)) continue;
            $existing = collect(Schema::getForeignKeys($tableName))->pluck('name')->all();
            foreach ($names as $name) {
                if (in_array($name, $existing, true)) {
                    Schema::table($tableName, fn (Blueprint $table) => $table->dropForeign($name));
                }
            }
        }
    }

    public function down(): void
    {
        // No se reintroducen restricciones redundantes automáticamente.
    }
};
