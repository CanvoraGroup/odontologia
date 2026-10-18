<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('odo_configuracion_empresa') || ! Schema::hasColumn('odo_configuracion_empresa', 'id_clinica')) return;
        $exists = collect(Schema::getForeignKeys('odo_configuracion_empresa'))->contains(fn (array $key): bool => $key['name'] === 'fk_odo_configuracion_empresa_clinica');
        if (! $exists) {
            Schema::table('odo_configuracion_empresa', function (Blueprint $table): void {
                $table->foreign('id_clinica', 'fk_odo_configuracion_empresa_clinica')->references('id_clinica')->on('odo_clinicas');
            });
        }
    }

    public function down(): void
    {
        // No se quita la relación de configuración con su empresa.
    }
};
