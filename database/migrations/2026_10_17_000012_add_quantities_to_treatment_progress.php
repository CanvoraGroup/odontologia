<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('odo_plan_procedimientos')) {
            Schema::table('odo_plan_procedimientos', function (Blueprint $table) {
                if (! Schema::hasColumn('odo_plan_procedimientos', 'cantidad_requerida')) $table->unsignedInteger('cantidad_requerida')->default(1)->after('caras');
                if (! Schema::hasColumn('odo_plan_procedimientos', 'cantidad_realizada')) $table->unsignedInteger('cantidad_realizada')->default(0)->after('cantidad_requerida');
                if (! Schema::hasColumn('odo_plan_procedimientos', 'ultimo_avance_at')) $table->dateTime('ultimo_avance_at')->nullable()->after('cantidad_realizada');
            });
        }
        if (Schema::hasTable('odo_tratamiento_sesion_detalles')) {
            Schema::table('odo_tratamiento_sesion_detalles', function (Blueprint $table) {
                if (! Schema::hasColumn('odo_tratamiento_sesion_detalles', 'cantidad_realizada')) $table->unsignedInteger('cantidad_realizada')->default(1)->after('id_plan_procedimiento');
            });
        }
    }

    public function down(): void
    {
        // Se conserva el historial clínico; no se eliminan columnas de avance automáticamente.
    }
};
