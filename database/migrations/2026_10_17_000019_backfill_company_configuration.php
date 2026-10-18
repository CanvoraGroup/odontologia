<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('odo_clinicas')->get() as $clinic) {
            if (DB::table('odo_configuracion_empresa')->where('id_clinica', $clinic->id_clinica)->exists()) continue;
            DB::table('odo_configuracion_empresa')->insert([
                'id_clinica' => $clinic->id_clinica,
                'razon_social' => $clinic->razon_social,
                'nombre_comercial' => $clinic->nombre_comercial,
                'ruc' => $clinic->ruc,
                'direccion' => $clinic->direccion,
                'telefono' => $clinic->telefono,
                'correo' => $clinic->correo,
                'logo_path' => $clinic->logo_path,
                'ticket_footer' => $clinic->ticket_footer,
                'ticket_width' => $clinic->ticket_width ?: 80,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // No elimina configuraciones de empresas existentes.
    }
};
