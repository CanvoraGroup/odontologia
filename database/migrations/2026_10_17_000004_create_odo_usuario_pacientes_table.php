<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('odo_usuario_pacientes')) {
            return;
        }

        Schema::create('odo_usuario_pacientes', function (Blueprint $table) {
            $table->id('id_usuario_paciente');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_paciente');
            $table->timestamps();

            $table->unique('id_usuario', 'uk_odo_usuario_paciente_usuario');
            $table->unique('id_paciente', 'uk_odo_usuario_paciente_paciente');
            $table->foreign('id_usuario', 'fk_odo_usuario_pacientes_usuario')
                ->references('id_usuario')->on('odo_usuarios');
            $table->foreign('id_paciente', 'fk_odo_usuario_pacientes_paciente')
                ->references('id_paciente')->on('odo_pacientes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odo_usuario_pacientes');
    }
};
