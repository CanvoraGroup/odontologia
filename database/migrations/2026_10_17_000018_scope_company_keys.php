<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'odo_pacientes', 'odo_odontologos', 'odo_consultorios', 'odo_citas', 'odo_atenciones',
            'odo_historias_clinicas', 'odo_odontogramas', 'odo_planes_tratamiento',
            'odo_presupuesto_versiones', 'odo_presupuesto_detalles', 'odo_plan_eventos',
            'odo_plan_procedimientos', 'odo_tratamiento_sesiones', 'odo_tratamiento_sesion_detalles',
            'odo_pagos', 'odo_pago_receptores', 'odo_comprobantes', 'odo_servicios', 'odo_categorias_servicios',
        ];

        foreach ($tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'id_clinica')) continue;
            $fk = 'fk_'.substr($tableName, 4).'_clinica';
            $hasFk = collect(Schema::getForeignKeys($tableName))->contains(fn (array $key): bool => $key['name'] === $fk);
            if (! $hasFk) {
                Schema::table($tableName, function (Blueprint $table) use ($fk): void {
                    $table->foreign('id_clinica', $fk)->references('id_clinica')->on('odo_clinicas');
                });
            }
        }

        $this->replaceUnique('odo_pacientes', 'uk_odo_pacientes_documento', 'uk_odo_pacientes_clinica_documento', ['id_clinica', 'tipo_documento', 'numero_documento']);
        $this->replaceUnique('odo_servicios', 'uk_odo_servicios_codigo', 'uk_odo_servicios_clinica_codigo', ['id_clinica', 'codigo']);
        $this->replaceUnique('odo_categorias_servicios', 'odo_categorias_servicios_nombre_unique', 'uk_odo_categorias_clinica_nombre', ['id_clinica', 'nombre']);
        $this->replaceUnique('odo_categorias_servicios', 'uk_odo_categorias_servicios_codigo', 'uk_odo_categorias_clinica_codigo', ['id_clinica', 'codigo']);
        $this->replaceUnique('odo_planes_tratamiento', 'uk_odo_planes_numero', 'uk_odo_planes_clinica_numero', ['id_clinica', 'numero']);
        $this->replaceUnique('odo_planes_tratamiento', 'uk_odo_planes_idempotency', 'uk_odo_planes_clinica_idempotency', ['id_clinica', 'idempotency_key']);
        $this->replaceUnique('odo_pagos', 'uk_odo_pagos_numero_constancia', 'uk_odo_pagos_clinica_constancia', ['id_clinica', 'numero_constancia']);
        $this->replaceUnique('odo_pagos', 'uk_odo_pagos_idempotency_key', 'uk_odo_pagos_clinica_idempotency', ['id_clinica', 'idempotency_key']);
    }

    private function replaceUnique(string $table, string $old, string $new, array $columns): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id_clinica')) return;
        $indexes = collect(Schema::getIndexes($table));
        if ($indexes->contains(fn (array $index): bool => $index['name'] === $new)) return;
        if ($indexes->contains(fn (array $index): bool => $index['name'] === $old)) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique($old));
        }
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique($columns, $new));
    }

    public function down(): void
    {
        // Las claves por empresa no se revierten automáticamente para no reintroducir colisiones entre clínicas.
    }
};
