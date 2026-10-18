<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('odo_planes_tratamiento') || ! Schema::hasColumn('odo_planes_tratamiento', 'estado_clinico')) {
            return;
        }

        DB::statement("ALTER TABLE odo_planes_tratamiento MODIFY estado_clinico ENUM('PLANIFICADO','EN_PROCESO','COMPLETADO','FINALIZADO','ANULADO') NOT NULL DEFAULT 'PLANIFICADO'");
    }

    public function down(): void
    {
        // Se conserva el valor para no invalidar estados clínicos existentes.
    }
};
