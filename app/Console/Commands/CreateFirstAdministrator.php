<?php

namespace App\Console\Commands;

use App\Models\Odontologia\Rol;
use App\Models\Odontologia\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class CreateFirstAdministrator extends Command
{
    protected $signature = 'odontologia:crear-primer-administrador';
    protected $description = 'Crea interactivamente el primer administrador activo de OdontoLaravel';

    public function handle(): int
    {
        $existing = Usuario::query()
            ->where('estado', 'ACTIVO')
            ->whereHas('roles', fn ($query) => $query->where('nombre', 'ADMINISTRADOR'))
            ->exists();

        if ($existing) {
            $this->error('Ya existe un administrador activo. El comando no puede reutilizarse.');
            return self::FAILURE;
        }

        $nombres = trim($this->ask('Nombres'));
        $apellidos = trim($this->ask('Apellidos'));
        $email = trim($this->ask('Correo electrónico'));
        $password = $this->secret('Contraseña');
        $confirmation = $this->secret('Confirmar contraseña');

        if ($nombres === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Nombres y correo válido son obligatorios.');
            return self::FAILURE;
        }
        if ($password === '' || strlen($password) < 8 || $password !== $confirmation) {
            $this->error('La contraseña debe tener al menos 8 caracteres y coincidir con la confirmación.');
            return self::FAILURE;
        }

        $lock = Cache::lock('odontologia:crear-primer-administrador', 60);
        if (! $lock->get()) {
            $this->error('Ya hay otro proceso intentando crear el primer administrador.');
            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($nombres, $apellidos, $email, $password) {
                $activeAdmins = Usuario::query()
                    ->where('estado', 'ACTIVO')
                    ->whereHas('roles', fn ($query) => $query->where('nombre', 'ADMINISTRADOR'))
                    ->lockForUpdate()
                    ->count();

                if ($activeAdmins > 0) {
                    throw new \RuntimeException('Ya existe un administrador activo.');
                }

                $role = Rol::where('nombre', 'ADMINISTRADOR')->where('estado', 'ACTIVO')->firstOrFail();
                $usuario = Usuario::create([
                    'nombres' => $nombres,
                    'apellidos' => $apellidos ?: null,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'estado' => 'ACTIVO',
                ]);
                $usuario->roles()->attach($role->id_rol);
            });
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        } finally {
            $lock->release();
        }

        $this->info('Administrador creado.');
        return self::SUCCESS;
    }
}
