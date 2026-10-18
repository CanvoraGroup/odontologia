<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('odo_planes_tratamiento') && ! Schema::hasColumn('odo_planes_tratamiento', 'tipo_plan')) {
            Schema::table('odo_planes_tratamiento', function (Blueprint $table) {
                $table->string('tipo_plan', 60)->nullable()->after('nombre');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('odo_planes_tratamiento') && Schema::hasColumn('odo_planes_tratamiento', 'tipo_plan')) {
            Schema::table('odo_planes_tratamiento', function (Blueprint $table) {
                $table->dropColumn('tipo_plan');
            });
        }
    }
};
