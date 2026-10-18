<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('odo_correlativos')) {
            Schema::create('odo_correlativos', function (Blueprint $table): void {
                $table->id('id_correlativo');
                $table->unsignedBigInteger('id_clinica');
                $table->string('tipo', 40);
                $table->unsignedInteger('ultimo_numero')->default(0);
                $table->timestamps();
                $table->unique(['id_clinica', 'tipo'], 'uk_odo_correlativos_clinica_tipo');
                $table->foreign('id_clinica')->references('id_clinica')->on('odo_clinicas');
            });
        }

        foreach (DB::table('odo_clinicas')->pluck('id_clinica') as $clinicId) {
            if (DB::table('odo_correlativos')->where(['id_clinica' => $clinicId, 'tipo' => 'CONSTANCIA_PAGO'])->exists()) continue;
            $last = (int) DB::table('odo_pagos')->where('id_clinica', $clinicId)->whereNotNull('numero_constancia')->max(DB::raw("CAST(SUBSTRING_INDEX(numero_constancia, '-', -1) AS UNSIGNED)"));
            DB::table('odo_correlativos')->insert(['id_clinica' => $clinicId, 'tipo' => 'CONSTANCIA_PAGO', 'ultimo_numero' => $last, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('odo_correlativos');
    }
};
