<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicados = DB::table('odo_atenciones')
            ->select('id_cita', DB::raw('COUNT(*) AS cantidad'))
            ->whereNotNull('id_cita')
            ->groupBy('id_cita')
            ->having('cantidad', '>', 1)
            ->get();

        if ($duplicados->isNotEmpty()) {
            throw new RuntimeException(
                'No se puede crear el índice único de odo_atenciones.id_cita: existen citas duplicadas.'
            );
        }

        $indiceExiste = collect(Schema::getIndexes('odo_atenciones'))
            ->contains(fn (array $indice) => ($indice['name'] ?? '') === 'odo_atenciones_id_cita_unique');

        if (! $indiceExiste) {
            Schema::table('odo_atenciones', function (Blueprint $table): void {
                $table->unique('id_cita', 'odo_atenciones_id_cita_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('odo_atenciones')) {
            Schema::table('odo_atenciones', function (Blueprint $table): void {
                $table->dropUnique('odo_atenciones_id_cita_unique');
            });
        }
    }
};
