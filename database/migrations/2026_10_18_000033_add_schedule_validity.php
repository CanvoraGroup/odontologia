<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        if (!Schema::hasTable('odo_horarios_odontologos')) return;

        Schema::table('odo_horarios_odontologos', function (Blueprint $table): void {
            if (!Schema::hasColumn('odo_horarios_odontologos', 'fecha_desde')) $table->date('fecha_desde')->nullable();
            if (!Schema::hasColumn('odo_horarios_odontologos', 'fecha_hasta')) $table->date('fecha_hasta')->nullable();
            if (!Schema::hasColumn('odo_horarios_odontologos', 'repetir_semanal')) $table->boolean('repetir_semanal')->default(true);
            if (!Schema::hasColumn('odo_horarios_odontologos', 'grupo_repeticion')) $table->string('grupo_repeticion', 36)->nullable();
        });

        if (!$this->hasIndex('odo_horarios_odontologos', 'idx_hor_odo_vigencia')) {
            Schema::table('odo_horarios_odontologos', function (Blueprint $table): void {
                $table->index(['id_clinica', 'id_odontologo', 'fecha_desde', 'fecha_hasta'], 'idx_hor_odo_vigencia');
            });
        }

        if (!Schema::hasTable('odo_horarios_excepciones')) {
            Schema::create('odo_horarios_excepciones', function (Blueprint $table): void {
                $table->id('id_excepcion');
                $table->unsignedBigInteger('id_clinica');
                $table->unsignedBigInteger('id_horario');
                $table->unsignedBigInteger('id_odontologo');
                $table->date('fecha');
                $table->string('tipo', 20); // DISPONIBLE o BLOQUEADO
                $table->time('hora_inicio')->nullable();
                $table->time('hora_fin')->nullable();
                $table->unsignedBigInteger('id_consultorio')->nullable();
                $table->string('motivo', 250)->nullable();
                $table->string('estado', 20)->default('ACTIVO');
                $table->unsignedBigInteger('id_usuario_creacion')->nullable();
                $table->unsignedBigInteger('id_usuario_modificacion')->nullable();
                $table->unsignedBigInteger('id_usuario_cancelacion')->nullable();
                $table->dateTime('fecha_cancelacion')->nullable();
                $table->string('motivo_cancelacion', 250)->nullable();
                $table->timestamps();
            });
        }
        if (!$this->hasIndex('odo_horarios_excepciones', 'idx_hor_exc_fecha')) {
            Schema::table('odo_horarios_excepciones', function (Blueprint $table): void {
                $table->index(['id_clinica', 'id_odontologo', 'fecha'], 'idx_hor_exc_fecha');
            });
        }
    }

    public function down(): void
    {
        // No se eliminan columnas ni historial de disponibilidad automáticamente.
    }
};
