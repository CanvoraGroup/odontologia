<?php

namespace App\Console\Commands;

use App\Models\Odontologia\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreatePlatformAdministrator extends Command
{
    protected $signature = 'odontologia:crear-administrador-plataforma';
    protected $description = 'Crea interactivamente el único administrador inicial de la plataforma';

    public function handle(): int
    {
        if (Usuario::where('es_admin_plataforma', true)->where('estado', 'ACTIVO')->exists()) {
            $this->error('Ya existe un administrador de plataforma activo. Este comando no se puede reutilizar.');
            return self::FAILURE;
        }

        $nombres = trim($this->ask('Nombres'));
        $apellidos = trim($this->ask('Apellidos'));
        $email = trim($this->ask('Correo electrónico'));
        $password = $this->secret('Contraseña');
        $confirmation = $this->secret('Confirmar contraseña');

        if ($nombres === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Nombres y correo válido son obligatorios.');
            return self::FAILURE;
        }
        if ($password === '' || strlen($password) < 8 || $password !== $confirmation) {
            $this->error('La contraseña debe tener al menos 8 caracteres y coincidir con la confirmación.');
            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($nombres, $apellidos, $email, $password): void {
                $active = Usuario::where('es_admin_plataforma', true)->where('estado', 'ACTIVO')->lockForUpdate()->exists();
                if ($active) throw new \RuntimeException('Ya existe un administrador de plataforma activo.');
                Usuario::create([
                    'nombres' => $nombres,
                    'apellidos' => $apellidos ?: null,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'estado' => 'ACTIVO',
                    'es_admin_plataforma' => true,
                    'id_clinica' => null,
                ]);
            });
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Administrador de plataforma creado.');
        return self::SUCCESS;
    }
}
