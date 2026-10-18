<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('odo_tratamiento_sesiones') && ! Schema::hasColumn('odo_tratamiento_sesiones', 'idempotency_key')) {
            Schema::table('odo_tratamiento_sesiones', function (Blueprint $table) {
                $table->string('idempotency_key', 36)->nullable()->after('id_sesion');
                $table->unique('idempotency_key', 'uk_odo_sesion_idempotency');
            });
        }
    }

    public function down(): void
    {
        // Se conserva la trazabilidad de sesiones; no se revierte automáticamente.
    }
};
