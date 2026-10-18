<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('odo_odontologos')
            ->select('id_usuario')
            ->whereNotNull('id_usuario')
            ->groupBy('id_usuario')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('No se puede crear el índice único: existen vínculos duplicados en odo_odontologos.id_usuario.');
        }

        $hasIndex = collect(DB::select('SHOW INDEX FROM odo_odontologos'))
            ->contains(fn ($index) => $index->Key_name === 'uk_odo_odontologos_usuario');

        if (! $hasIndex) {
            Schema::table('odo_odontologos', function (Blueprint $table) {
                $table->unique('id_usuario', 'uk_odo_odontologos_usuario');
            });
        }
    }

    public function down(): void
    {
        $hasIndex = collect(DB::select('SHOW INDEX FROM odo_odontologos'))
            ->contains(fn ($index) => $index->Key_name === 'uk_odo_odontologos_usuario');

        if ($hasIndex) {
            Schema::table('odo_odontologos', function (Blueprint $table) {
                $table->dropUnique('uk_odo_odontologos_usuario');
            });
        }
    }
};
