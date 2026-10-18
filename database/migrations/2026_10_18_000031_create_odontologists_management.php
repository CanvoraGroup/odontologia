<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('odo_odontologos')) {
            Schema::table('odo_odontologos', function (Blueprint $table): void {
                if (! Schema::hasColumn('odo_odontologos', 'observaciones')) $table->text('observaciones')->nullable();
                if (! Schema::hasColumn('odo_odontologos', 'direccion')) $table->string('direccion', 250)->nullable();
                if (! Schema::hasColumn('odo_odontologos', 'id_usuario_desactivacion')) $table->unsignedBigInteger('id_usuario_desactivacion')->nullable();
                if (! Schema::hasColumn('odo_odontologos', 'fecha_desactivacion')) $table->dateTime('fecha_desactivacion')->nullable();
                if (! Schema::hasColumn('odo_odontologos', 'motivo_desactivacion')) $table->text('motivo_desactivacion')->nullable();
                if (! Schema::hasColumn('odo_odontologos', 'id_usuario_reactivacion')) $table->unsignedBigInteger('id_usuario_reactivacion')->nullable();
                if (! Schema::hasColumn('odo_odontologos', 'fecha_reactivacion')) $table->dateTime('fecha_reactivacion')->nullable();
            });

            $indexes = collect(Schema::getIndexes('odo_odontologos'));
            if ($indexes->contains(fn (array $index): bool => $index['name'] === 'uk_odo_odontologos_colegiatura')) {
                $duplicates = DB::table('odo_odontologos')->select('id_clinica', 'colegiatura')->whereNotNull('colegiatura')->groupBy('id_clinica', 'colegiatura')->havingRaw('COUNT(*) > 1')->exists();
                if ($duplicates) throw new RuntimeException('No se puede ajustar el índice de colegiatura: existen duplicados dentro de una clínica.');
                Schema::table('odo_odontologos', function (Blueprint $table): void { $table->dropUnique('uk_odo_odontologos_colegiatura'); });
            }
            if (! collect(Schema::getIndexes('odo_odontologos'))->contains(fn (array $index): bool => $index['name'] === 'uk_odo_odontologos_clinica_colegiatura')) {
                Schema::table('odo_odontologos', fn (Blueprint $table) => $table->unique(['id_clinica', 'colegiatura'], 'uk_odo_odontologos_clinica_colegiatura'));
            }
            if (! collect(Schema::getIndexes('odo_odontologos'))->contains(fn (array $index): bool => $index['name'] === 'uk_odo_odontologos_clinica_documento')) {
                $duplicates = DB::table('odo_odontologos')->select('id_clinica', 'tipo_documento', 'numero_documento')->whereNotNull('numero_documento')->groupBy('id_clinica', 'tipo_documento', 'numero_documento')->havingRaw('COUNT(*) > 1')->exists();
                if ($duplicates) throw new RuntimeException('No se puede crear el índice de documento profesional: existen duplicados dentro de una clínica.');
                Schema::table('odo_odontologos', fn (Blueprint $table) => $table->unique(['id_clinica', 'tipo_documento', 'numero_documento'], 'uk_odo_odontologos_clinica_documento'));
            }
        }

        if (! Schema::hasTable('odo_odontologo_documentos')) {
            Schema::create('odo_odontologo_documentos', function (Blueprint $table): void {
                $table->id('id_documento_odontologo');
                $table->unsignedBigInteger('id_clinica');
                $table->unsignedBigInteger('id_odontologo');
                $table->unsignedBigInteger('id_especialidad')->nullable();
                $table->string('tipo_documento', 60);
                $table->string('nombre', 180);
                $table->string('institucion_emisora', 180)->nullable();
                $table->string('referencia', 120)->nullable();
                $table->date('fecha_emision')->nullable();
                $table->date('fecha_vencimiento')->nullable();
                $table->string('archivo_path', 500);
                $table->string('archivo_nombre_original', 255);
                $table->string('archivo_mime', 100);
                $table->unsignedBigInteger('archivo_tamano');
                $table->string('archivo_hash', 64);
                $table->string('estado_revision', 30)->default('PENDIENTE');
                $table->text('observaciones_revision')->nullable();
                $table->unsignedBigInteger('id_usuario_revision')->nullable();
                $table->dateTime('fecha_revision')->nullable();
                $table->unsignedBigInteger('id_documento_anterior')->nullable();
                $table->boolean('vigente')->default(true);
                $table->text('observaciones')->nullable();
                $table->unsignedBigInteger('id_usuario_creacion')->nullable();
                $table->unsignedBigInteger('id_usuario_modificacion')->nullable();
                $table->timestamps();
                $table->index(['id_clinica', 'id_odontologo']);
                $table->foreign('id_clinica')->references('id_clinica')->on('odo_clinicas');
                $table->foreign('id_odontologo')->references('id_odontologo')->on('odo_odontologos');
                $table->foreign('id_especialidad')->references('id_especialidad')->on('odo_especialidades')->nullOnDelete();
                $table->foreign('id_documento_anterior')->references('id_documento_odontologo')->on('odo_odontologo_documentos')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // No elimina historiales ni archivos en rollback.
    }
};
