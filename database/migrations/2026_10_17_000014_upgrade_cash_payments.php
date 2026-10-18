<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('odo_pago_receptores')) {
            Schema::create('odo_pago_receptores', function (Blueprint $table) {
                $table->id('id_receptor');
                $table->string('tipo_documento', 30)->nullable();
                $table->string('numero_documento', 30)->nullable();
                $table->string('nombre_razon_social', 180);
                $table->string('direccion', 250)->nullable();
                $table->timestamps();
                $table->index(['tipo_documento', 'numero_documento']);
            });
        }

        $columns = [];
        if (! Schema::hasColumn('odo_pagos', 'id_receptor')) $columns['id_receptor'] = fn (Blueprint $table) => $table->unsignedBigInteger('id_receptor')->nullable()->after('id_paciente');
        if (! Schema::hasColumn('odo_pagos', 'referencia_operacion')) $columns['referencia_operacion'] = fn (Blueprint $table) => $table->string('referencia_operacion', 120)->nullable()->after('id_metodo_pago');
        if (! Schema::hasColumn('odo_pagos', 'numero_constancia')) $columns['numero_constancia'] = fn (Blueprint $table) => $table->string('numero_constancia', 30)->nullable()->after('id_pago');
        if (! Schema::hasColumn('odo_pagos', 'idempotency_key')) $columns['idempotency_key'] = fn (Blueprint $table) => $table->uuid('idempotency_key')->nullable()->after('numero_constancia');
        if (! Schema::hasColumn('odo_pagos', 'fecha_anulacion')) $columns['fecha_anulacion'] = fn (Blueprint $table) => $table->dateTime('fecha_anulacion')->nullable()->after('estado');
        if (! Schema::hasColumn('odo_pagos', 'id_usuario_anulacion')) $columns['id_usuario_anulacion'] = fn (Blueprint $table) => $table->unsignedBigInteger('id_usuario_anulacion')->nullable()->after('id_usuario_creacion');
        if (! Schema::hasColumn('odo_pagos', 'motivo_anulacion')) $columns['motivo_anulacion'] = fn (Blueprint $table) => $table->text('motivo_anulacion')->nullable()->after('fecha_anulacion');
        if ($columns) Schema::table('odo_pagos', function (Blueprint $table) use ($columns) { foreach ($columns as $column) $column($table); });

        foreach (['uk_odo_pagos_numero_constancia' => 'numero_constancia', 'uk_odo_pagos_idempotency_key' => 'idempotency_key'] as $index => $column) {
            if (Schema::hasColumn('odo_pagos', $column)) {
                try { Schema::table('odo_pagos', fn (Blueprint $table) => $table->unique($column, $index)); } catch (Throwable $e) { /* Existing deployments may already contain the index. */ }
            }
        }

        try { Schema::table('odo_pagos', fn (Blueprint $table) => $table->foreign('id_receptor', 'fk_odo_pagos_receptor')->references('id_receptor')->on('odo_pago_receptores')); } catch (Throwable $e) { /* Existing foreign key is preserved. */ }
        try { Schema::table('odo_pagos', fn (Blueprint $table) => $table->foreign('id_usuario_anulacion', 'fk_odo_pagos_usuario_anulacion')->references('id_usuario')->on('odo_usuarios')); } catch (Throwable $e) { /* Existing foreign key is preserved. */ }
    }

    public function down(): void
    {
        // Intentionally non-destructive: payment history is retained.
    }
};
