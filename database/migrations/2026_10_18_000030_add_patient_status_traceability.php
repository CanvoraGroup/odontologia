<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('odo_pacientes')) {
            return;
        }

        Schema::table('odo_pacientes', function (Blueprint $table): void {
            if (! Schema::hasColumn('odo_pacientes', 'id_usuario_desactivacion')) {
                $table->unsignedBigInteger('id_usuario_desactivacion')->nullable()->after('id_usuario_modificacion');
            }
            if (! Schema::hasColumn('odo_pacientes', 'fecha_desactivacion')) {
                $table->dateTime('fecha_desactivacion')->nullable()->after('id_usuario_desactivacion');
            }
            if (! Schema::hasColumn('odo_pacientes', 'motivo_desactivacion')) {
                $table->text('motivo_desactivacion')->nullable()->after('fecha_desactivacion');
            }
            if (! Schema::hasColumn('odo_pacientes', 'id_usuario_reactivacion')) {
                $table->unsignedBigInteger('id_usuario_reactivacion')->nullable()->after('motivo_desactivacion');
            }
            if (! Schema::hasColumn('odo_pacientes', 'fecha_reactivacion')) {
                $table->dateTime('fecha_reactivacion')->nullable()->after('id_usuario_reactivacion');
            }
        });
    }

    public function down(): void
    {
        // Se conserva la trazabilidad histórica; no se eliminan columnas en rollback.
    }
};
