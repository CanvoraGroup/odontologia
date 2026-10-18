<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('odo_configuracion_empresa')) {
            Schema::create('odo_configuracion_empresa', function (Blueprint $table) {
                $table->id('id_configuracion');
                $table->string('razon_social', 180)->nullable();
                $table->string('nombre_comercial', 180)->nullable();
                $table->string('ruc', 11)->nullable();
                $table->string('direccion', 250)->nullable();
                $table->string('telefono', 40)->nullable();
                $table->string('correo', 150)->nullable();
                $table->string('logo_path', 255)->nullable();
                $table->string('ticket_footer', 250)->nullable();
                $table->unsignedSmallInteger('ticket_width')->default(80);
                $table->timestamps();
            });
        }

        $columns = [
            'snapshot_razon_social' => fn (Blueprint $t) => $t->string('snapshot_razon_social', 180)->nullable(),
            'snapshot_nombre_comercial' => fn (Blueprint $t) => $t->string('snapshot_nombre_comercial', 180)->nullable(),
            'snapshot_ruc' => fn (Blueprint $t) => $t->string('snapshot_ruc', 11)->nullable(),
            'snapshot_direccion' => fn (Blueprint $t) => $t->string('snapshot_direccion', 250)->nullable(),
            'snapshot_telefono' => fn (Blueprint $t) => $t->string('snapshot_telefono', 40)->nullable(),
            'snapshot_correo' => fn (Blueprint $t) => $t->string('snapshot_correo', 150)->nullable(),
            'snapshot_logo_path' => fn (Blueprint $t) => $t->string('snapshot_logo_path', 255)->nullable(),
            'snapshot_ticket_footer' => fn (Blueprint $t) => $t->string('snapshot_ticket_footer', 250)->nullable(),
            'snapshot_ticket_width' => fn (Blueprint $t) => $t->unsignedSmallInteger('snapshot_ticket_width')->nullable(),
            'snapshot_receptor_tipo_documento' => fn (Blueprint $t) => $t->string('snapshot_receptor_tipo_documento', 30)->nullable(),
            'snapshot_receptor_numero_documento' => fn (Blueprint $t) => $t->string('snapshot_receptor_numero_documento', 30)->nullable(),
            'snapshot_receptor_nombre' => fn (Blueprint $t) => $t->string('snapshot_receptor_nombre', 180)->nullable(),
            'snapshot_receptor_direccion' => fn (Blueprint $t) => $t->string('snapshot_receptor_direccion', 250)->nullable(),
            'snapshot_total_presupuesto' => fn (Blueprint $t) => $t->decimal('snapshot_total_presupuesto', 10, 2)->nullable(),
            'snapshot_abonado_acumulado' => fn (Blueprint $t) => $t->decimal('snapshot_abonado_acumulado', 10, 2)->nullable(),
            'snapshot_saldo_pendiente' => fn (Blueprint $t) => $t->decimal('snapshot_saldo_pendiente', 10, 2)->nullable(),
        ];
        $pending = array_filter($columns, fn ($column, $name) => ! Schema::hasColumn('odo_pagos', $name), ARRAY_FILTER_USE_BOTH);
        if ($pending) Schema::table('odo_pagos', function (Blueprint $table) use ($pending) { foreach ($pending as $column) $column($table); });
    }

    public function down(): void
    {
        // Se conserva configuración y evidencia histórica de pagos.
    }
};
