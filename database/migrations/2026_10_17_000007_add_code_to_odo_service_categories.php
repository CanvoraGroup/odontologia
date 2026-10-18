<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('odo_categorias_servicios', 'codigo')) {
            Schema::table('odo_categorias_servicios', function (Blueprint $table) {
                $table->string('codigo', 20)->nullable()->after('id_categoria');
                $table->unique('codigo', 'uk_odo_categorias_servicios_codigo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('odo_categorias_servicios', 'codigo')) {
            Schema::table('odo_categorias_servicios', function (Blueprint $table) {
                $table->dropUnique('uk_odo_categorias_servicios_codigo');
                $table->dropColumn('codigo');
            });
        }
    }
};
