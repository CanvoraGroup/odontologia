<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('odo_planes_tratamiento') || Schema::hasColumn('odo_planes_tratamiento', 'idempotency_key')) return;

        Schema::table('odo_planes_tratamiento', function (Blueprint $table) {
            $table->string('idempotency_key', 36)->nullable()->after('id_plan_tratamiento');
        });

        $indexes = collect(DB::select('SHOW INDEX FROM odo_planes_tratamiento'));
        if (! $indexes->contains(fn ($index) => ($index->Key_name ?? null) === 'uk_odo_planes_idempotency')) {
            Schema::table('odo_planes_tratamiento', function (Blueprint $table) {
                $table->unique('idempotency_key', 'uk_odo_planes_idempotency');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('odo_planes_tratamiento') || ! Schema::hasColumn('odo_planes_tratamiento', 'idempotency_key')) return;
        Schema::table('odo_planes_tratamiento', function (Blueprint $table) {
            $table->dropUnique('uk_odo_planes_idempotency');
            $table->dropColumn('idempotency_key');
        });
    }
};
