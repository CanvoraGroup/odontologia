<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private function hasIndex(string $table, string $index): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }

    public function up(): void
    {
        if (Schema::hasTable('odo_horarios_odontologos')) {
            Schema::table('odo_horarios_odontologos', function (Blueprint $table): void {
                if (!Schema::hasColumn('odo_horarios_odontologos', 'id_clinica')) $table->unsignedBigInteger('id_clinica')->nullable()->index();
                if (!Schema::hasColumn('odo_horarios_odontologos', 'id_usuario_creacion')) $table->unsignedBigInteger('id_usuario_creacion')->nullable();
                if (!Schema::hasColumn('odo_horarios_odontologos', 'id_usuario_modificacion')) $table->unsignedBigInteger('id_usuario_modificacion')->nullable();
            });
            if (Schema::hasColumn('odo_horarios_odontologos', 'id_clinica') && Schema::hasTable('odo_odontologos')) {
                DB::statement('UPDATE odo_horarios_odontologos h INNER JOIN odo_odontologos o ON o.id_odontologo = h.id_odontologo SET h.id_clinica = o.id_clinica WHERE h.id_clinica IS NULL');
            }
        }
        if (!Schema::hasTable('odo_bloqueos_odontologos')) {
            Schema::create('odo_bloqueos_odontologos', function (Blueprint $table): void {
                $table->id('id_bloqueo');
                $table->unsignedBigInteger('id_clinica');
                $table->unsignedBigInteger('id_odontologo');
                $table->date('fecha_desde');
                $table->date('fecha_hasta');
                $table->time('hora_desde')->nullable();
                $table->time('hora_hasta')->nullable();
                $table->boolean('dia_completo')->default(false);
                $table->string('motivo', 250);
                $table->string('estado', 20)->default('ACTIVO');
                $table->unsignedBigInteger('id_usuario_creacion')->nullable();
                $table->unsignedBigInteger('id_usuario_modificacion')->nullable();
                $table->unsignedBigInteger('id_usuario_cancelacion')->nullable();
                $table->dateTime('fecha_cancelacion')->nullable();
                $table->string('motivo_cancelacion', 250)->nullable();
                $table->timestamps();
                $table->index('id_clinica', 'idx_bloq_clinica');
                $table->index(
                    ['id_clinica', 'id_odontologo', 'fecha_desde', 'fecha_hasta'],
                    'idx_bloq_clin_odo_fechas'
                );
            });
        }

        // La ejecución puede haberse interrumpido después de crear la tabla
        // pero antes de crear sus índices. Completar solo lo que falte.
        if (Schema::hasTable('odo_bloqueos_odontologos')) {
            Schema::table('odo_bloqueos_odontologos', function (Blueprint $table): void {
                if (!Schema::hasColumn('odo_bloqueos_odontologos', 'id_clinica')) $table->unsignedBigInteger('id_clinica')->nullable();
                if (!Schema::hasColumn('odo_bloqueos_odontologos', 'id_odontologo')) $table->unsignedBigInteger('id_odontologo')->nullable();
                if (!Schema::hasColumn('odo_bloqueos_odontologos', 'fecha_desde')) $table->date('fecha_desde')->nullable();
                if (!Schema::hasColumn('odo_bloqueos_odontologos', 'fecha_hasta')) $table->date('fecha_hasta')->nullable();
                if (!Schema::hasColumn('odo_bloqueos_odontologos', 'hora_desde')) $table->time('hora_desde')->nullable();
                if (!Schema::hasColumn('odo_bloqueos_odontologos', 'hora_hasta')) $table->time('hora_hasta')->nullable();
                if (!Schema::hasColumn('odo_bloqueos_odontologos', 'dia_completo')) $table->boolean('dia_completo')->default(false);
                if (!Schema::hasColumn('odo_bloqueos_odontologos', 'motivo')) $table->string('motivo', 250)->nullable();
                if (!Schema::hasColumn('odo_bloqueos_odontologos', 'estado')) $table->string('estado', 20)->default('ACTIVO');
                if (!Schema::hasColumn('odo_bloqueos_odontologos', 'id_usuario_creacion')) $table->unsignedBigInteger('id_usuario_creacion')->nullable();
                if (!Schema::hasColumn('odo_bloqueos_odontologos', 'id_usuario_modificacion')) $table->unsignedBigInteger('id_usuario_modificacion')->nullable();
                if (!Schema::hasColumn('odo_bloqueos_odontologos', 'id_usuario_cancelacion')) $table->unsignedBigInteger('id_usuario_cancelacion')->nullable();
                if (!Schema::hasColumn('odo_bloqueos_odontologos', 'fecha_cancelacion')) $table->dateTime('fecha_cancelacion')->nullable();
                if (!Schema::hasColumn('odo_bloqueos_odontologos', 'motivo_cancelacion')) $table->string('motivo_cancelacion', 250)->nullable();
                if (!Schema::hasColumn('odo_bloqueos_odontologos', 'created_at')) $table->timestamp('created_at')->nullable();
                if (!Schema::hasColumn('odo_bloqueos_odontologos', 'updated_at')) $table->timestamp('updated_at')->nullable();
            });
            if (!$this->hasIndex('odo_bloqueos_odontologos', 'idx_bloq_clinica')) {
                Schema::table('odo_bloqueos_odontologos', function (Blueprint $table): void {
                    $table->index('id_clinica', 'idx_bloq_clinica');
                });
            }
            if (!$this->hasIndex('odo_bloqueos_odontologos', 'idx_bloq_clin_odo_fechas')) {
                Schema::table('odo_bloqueos_odontologos', function (Blueprint $table): void {
                    $table->index(
                        ['id_clinica', 'id_odontologo', 'fecha_desde', 'fecha_hasta'],
                        'idx_bloq_clin_odo_fechas'
                    );
                });
            }
        }
    }
    public function down(): void { }
};
