<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('odo_auditoria')) return;
        if (! Schema::hasColumn('odo_auditoria', 'id_clinica')) {
            Schema::table('odo_auditoria', function (Blueprint $table): void {
                $table->unsignedBigInteger('id_clinica')->nullable()->after('id_auditoria');
                $table->index(['id_clinica', 'tabla', 'registro_id'], 'idx_odo_auditoria_clinica_registro');
            });
        }
        DB::statement('UPDATE odo_auditoria a INNER JOIN odo_usuarios u ON a.id_usuario = u.id_usuario SET a.id_clinica = u.id_clinica WHERE a.id_clinica IS NULL AND u.id_clinica IS NOT NULL');
        $keys = collect(Schema::getForeignKeys('odo_auditoria'));
        if (! $keys->contains(fn (array $key): bool => $key['name'] === 'fk_odo_auditoria_clinica')) {
            Schema::table('odo_auditoria', fn (Blueprint $table) => $table->foreign('id_clinica', 'fk_odo_auditoria_clinica')->references('id_clinica')->on('odo_clinicas')->nullOnDelete());
        }
    }

    public function down(): void
    {
        // No se eliminan trazas históricas automáticamente.
    }
};
