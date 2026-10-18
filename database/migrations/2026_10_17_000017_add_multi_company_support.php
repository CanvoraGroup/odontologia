<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tenantTables = [
        'odo_pacientes', 'odo_odontologos', 'odo_consultorios', 'odo_citas', 'odo_atenciones',
        'odo_historias_clinicas', 'odo_odontogramas', 'odo_planes_tratamiento',
        'odo_presupuesto_versiones', 'odo_presupuesto_detalles', 'odo_plan_eventos',
        'odo_plan_procedimientos', 'odo_tratamiento_sesiones', 'odo_tratamiento_sesion_detalles',
        'odo_pagos', 'odo_pago_receptores', 'odo_comprobantes', 'odo_servicios',
        'odo_categorias_servicios',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('odo_usuarios', 'es_admin_plataforma')) {
            Schema::table('odo_usuarios', fn (Blueprint $table) => $table->boolean('es_admin_plataforma')->default(false)->after('id_clinica'));
        }

        foreach ([
            'logo_path' => fn (Blueprint $table) => $table->string('logo_path', 255)->nullable(),
            'ticket_footer' => fn (Blueprint $table) => $table->string('ticket_footer', 250)->nullable(),
            'ticket_width' => fn (Blueprint $table) => $table->unsignedSmallInteger('ticket_width')->default(80),
        ] as $column => $definition) {
            if (! Schema::hasColumn('odo_clinicas', $column)) {
                Schema::table('odo_clinicas', $definition);
            }
        }

        if ((int) DB::table('odo_clinicas')->count() === 0) {
            $config = Schema::hasTable('odo_configuracion_empresa')
                ? DB::table('odo_configuracion_empresa')->first()
                : null;

            DB::table('odo_clinicas')->insert([
                'nombre_comercial' => $config?->nombre_comercial ?: 'Clínica principal',
                'razon_social' => $config?->razon_social,
                'ruc' => $config?->ruc,
                'telefono' => $config?->telefono,
                'correo' => $config?->correo,
                'direccion' => $config?->direccion,
                'logo_path' => $config?->logo_path,
                'ticket_footer' => $config?->ticket_footer,
                'ticket_width' => $config?->ticket_width ?: 80,
                'estado' => 'ACTIVO',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $initialClinic = (int) DB::table('odo_clinicas')->orderBy('id_clinica')->value('id_clinica');

        foreach ($this->tenantTables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'id_clinica')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('id_clinica')->nullable()->index();
            });
        }

        // La auditoría previa mostró un único conjunto de datos actual. Se conserva y se adscribe a la clínica inicial.
        foreach (array_merge(['odo_usuarios'], $this->tenantTables) as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'id_clinica')) {
                DB::table($tableName)->whereNull('id_clinica')->update(['id_clinica' => $initialClinic]);
            }
        }

        if (Schema::hasTable('odo_configuracion_empresa') && ! Schema::hasColumn('odo_configuracion_empresa', 'id_clinica')) {
            Schema::table('odo_configuracion_empresa', function (Blueprint $table): void {
                $table->unsignedBigInteger('id_clinica')->nullable()->index()->after('id_configuracion');
            });
            DB::table('odo_configuracion_empresa')->whereNull('id_clinica')->update(['id_clinica' => $initialClinic]);
        }

        if (Schema::hasTable('odo_configuracion_empresa')) {
            $duplicateConfigs = DB::table('odo_configuracion_empresa')->select('id_clinica')->whereNotNull('id_clinica')->groupBy('id_clinica')->havingRaw('COUNT(*) > 1')->exists();
            $hasClinicUnique = collect(Schema::getIndexes('odo_configuracion_empresa'))
                ->contains(fn (array $index): bool => $index['name'] === 'uk_odo_configuracion_empresa_clinica');
            if (! $duplicateConfigs && ! $hasClinicUnique) {
                Schema::table('odo_configuracion_empresa', function (Blueprint $table): void {
                    $table->unique('id_clinica', 'uk_odo_configuracion_empresa_clinica');
                });
            }
        }
    }

    public function down(): void
    {
        // No se revierte automáticamente: la migración adscribió datos existentes a una empresa y su reversión podría exponerlos.
    }
};
