<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('odo_roles') || Schema::hasColumn('odo_roles', 'id_clinica')) return;

        Schema::table('odo_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_clinica')->nullable()->after('id_rol')->index();
        });
    }

    public function down(): void
    {
        // No se elimina el alcance de roles automáticamente para no romper asignaciones existentes.
    }
};
