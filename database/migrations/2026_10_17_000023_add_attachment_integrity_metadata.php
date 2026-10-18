<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('odo_historia_adjuntos')) return;

        Schema::table('odo_historia_adjuntos', function (Blueprint $table): void {
            if (! Schema::hasColumn('odo_historia_adjuntos', 'archivo_hash_sha256')) {
                $table->char('archivo_hash_sha256', 64)->nullable()->after('tamano_bytes');
            }
            if (! Schema::hasColumn('odo_historia_adjuntos', 'ruta_publica_anterior')) {
                $table->string('ruta_publica_anterior', 500)->nullable()->after('archivo_path');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('odo_historia_adjuntos')) return;

        Schema::table('odo_historia_adjuntos', function (Blueprint $table): void {
            foreach (['archivo_hash_sha256', 'ruta_publica_anterior'] as $column) {
                if (Schema::hasColumn('odo_historia_adjuntos', $column)) $table->dropColumn($column);
            }
        });
    }
};
