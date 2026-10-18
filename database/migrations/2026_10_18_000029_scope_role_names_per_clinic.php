<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('odo_roles') || ! Schema::hasColumn('odo_roles', 'id_clinica')) {
            return;
        }

        $indexes = collect(DB::select('SHOW INDEX FROM `odo_roles`'))
            ->groupBy('Key_name')
            ->map(fn ($rows) => $rows->sortBy('Seq_in_index')->values());

        $clinicNameIndex = 'uk_odo_roles_clinica_nombre';
        if (! $indexes->has($clinicNameIndex)) {
            Schema::table('odo_roles', function (Blueprint $table) use ($clinicNameIndex): void {
                $table->unique(['id_clinica', 'nombre'], $clinicNameIndex);
            });
        }

        foreach ($indexes as $name => $columns) {
            $isSingleNameUnique = $columns->count() === 1
                && (int) $columns->first()->Non_unique === 0
                && $columns->first()->Column_name === 'nombre';

            if ($isSingleNameUnique && $name !== $clinicNameIndex) {
                Schema::table('odo_roles', function (Blueprint $table) use ($name): void {
                    $table->dropUnique($name);
                });
            }
        }
    }

    public function down(): void
    {
        // No reintroducir la clave global: rompería las copias por clínica ya creadas.
    }
};
