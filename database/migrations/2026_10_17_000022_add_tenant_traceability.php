<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tenantTables = [
        'odo_clinicas', 'odo_usuarios', 'odo_pacientes', 'odo_odontologos', 'odo_consultorios', 'odo_citas',
        'odo_atenciones', 'odo_historias_clinicas', 'odo_historia_adjuntos', 'odo_odontogramas', 'odo_odontograma_detalles',
        'odo_planes_tratamiento', 'odo_presupuesto_versiones', 'odo_presupuesto_detalles', 'odo_plan_eventos',
        'odo_plan_procedimientos', 'odo_tratamiento_sesiones', 'odo_tratamiento_sesion_detalles', 'odo_pagos',
        'odo_pago_receptores', 'odo_pago_detalles', 'odo_comprobantes', 'odo_servicios', 'odo_categorias_servicios',
        'odo_configuracion_empresa',
    ];

    private array $annulableTables = ['odo_citas', 'odo_atenciones', 'odo_historias_clinicas', 'odo_odontogramas', 'odo_planes_tratamiento', 'odo_tratamiento_sesiones'];

    public function up(): void
    {
        if (! Schema::hasTable('odo_auditoria')) {
            Schema::create('odo_auditoria', function (Blueprint $table): void {
                $table->id('id_auditoria');
                $table->unsignedBigInteger('id_clinica');
                $table->unsignedBigInteger('id_usuario')->nullable();
                $table->string('accion', 40);
                $table->string('tabla', 100);
                $table->string('id_registro', 80);
                $table->json('datos_anteriores')->nullable();
                $table->json('datos_nuevos')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index(['id_clinica', 'tabla', 'id_registro']);
                $table->foreign('id_clinica')->references('id_clinica')->on('odo_clinicas');
                $table->foreign('id_usuario')->references('id_usuario')->on('odo_usuarios')->nullOnDelete();
            });
        }

        foreach ($this->tenantTables as $tableName) {
            if (! Schema::hasTable($tableName)) continue;
            if (! Schema::hasColumn($tableName, 'id_usuario_creacion')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->unsignedBigInteger('id_usuario_creacion')->nullable());
            }
            if (! Schema::hasColumn($tableName, 'id_usuario_modificacion')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->unsignedBigInteger('id_usuario_modificacion')->nullable());
            }
        }

        foreach ($this->annulableTables as $tableName) {
            if (! Schema::hasTable($tableName)) continue;
            if (! Schema::hasColumn($tableName, 'fecha_anulacion')) Schema::table($tableName, fn (Blueprint $table) => $table->dateTime('fecha_anulacion')->nullable());
            if (! Schema::hasColumn($tableName, 'id_usuario_anulacion')) Schema::table($tableName, fn (Blueprint $table) => $table->unsignedBigInteger('id_usuario_anulacion')->nullable());
            if (! Schema::hasColumn($tableName, 'motivo_anulacion')) Schema::table($tableName, fn (Blueprint $table) => $table->text('motivo_anulacion')->nullable());
        }

        foreach (['odo_historia_adjuntos' => ['id_historia', 'odo_historias_clinicas', 'id_historia'], 'odo_odontograma_detalles' => ['id_odontograma', 'odo_odontogramas', 'id_odontograma'], 'odo_pago_detalles' => ['id_pago', 'odo_pagos', 'id_pago']] as $child => [$childKey, $parent, $parentKey]) {
            if (! Schema::hasTable($child) || ! Schema::hasTable($parent)) continue;
            if (! Schema::hasColumn($child, 'id_clinica')) Schema::table($child, fn (Blueprint $table) => $table->unsignedBigInteger('id_clinica')->nullable()->index());
            DB::statement("UPDATE {$child} c INNER JOIN {$parent} p ON c.{$childKey}=p.{$parentKey} SET c.id_clinica=p.id_clinica WHERE c.id_clinica IS NULL");
        }

        foreach ($this->tenantTables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'id_clinica')) continue;
            $indexes = collect(Schema::getIndexes($tableName));
            if (! $indexes->contains(fn (array $index): bool => $index['name'] === $tableName.'_id_clinica_index')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->index('id_clinica', $tableName.'_id_clinica_index'));
            }
        }

        $children = ['odo_historia_adjuntos', 'odo_odontograma_detalles', 'odo_pago_detalles'];
        foreach (array_merge(array_diff($this->tenantTables, ['odo_clinicas']), $children) as $tableName) {
            if (! Schema::hasTable($tableName)) continue;
            $foreignKeys = collect(Schema::getForeignKeys($tableName));
            if (Schema::hasColumn($tableName, 'id_clinica') && ! $foreignKeys->contains(fn (array $key): bool => $key['name'] === 'fk_'.$tableName.'_clinica')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->foreign('id_clinica', 'fk_'.$tableName.'_clinica')->references('id_clinica')->on('odo_clinicas'));
            }
        }
    }

    public function down(): void
    {
        // No se eliminan trazas ni columnas de actor automáticamente.
    }
};
