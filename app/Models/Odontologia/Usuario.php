<?php

namespace App\Models\Odontologia;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Odontologia\Concerns\TracksActors;

class Usuario extends Authenticatable
{
    use Notifiable;
    use TracksActors;

    protected $table = 'odo_usuarios';

    protected $primaryKey = 'id_usuario';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_clinica',
        'es_admin_plataforma',
        'nombres',
        'apellidos',
        'email',
        'password',
        'telefono',
        'estado',
        'ultimo_acceso_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'ultimo_acceso_at' => 'datetime',
        'es_admin_plataforma' => 'boolean',
    ];

    public function clinica(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Clinica::class, 'id_clinica', 'id_clinica');
    }

    public function isPlatformAdministrator(): bool
    {
        return (bool) $this->es_admin_plataforma;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'odo_usuario_roles', 'id_usuario', 'id_rol')
            ->where('odo_roles.estado', 'ACTIVO')
            ->withTimestamps();
    }

    public function odontologo(): HasOne
    {
        return $this->hasOne(Odontologo::class, 'id_usuario', 'id_usuario');
    }

    public function pacienteCuenta(): HasOne
    {
        return $this->hasOne(UsuarioPaciente::class, 'id_usuario', 'id_usuario');
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('nombre', $role)->exists();
    }

    public function hasPermission(string $module, string $action): bool
    {
        if ($this->isPlatformAdministrator()) {
            return in_array($module, ['empresas', 'configuracion_plataforma'], true);
        }

        if ($this->hasRole('ADMINISTRADOR') && ! ($module === 'recetas' && in_array($action, ['crear', 'editar', 'anular'], true))) {
            return true;
        }

        return $this->roles()
            ->whereHas('permisos', fn ($query) => $query
                ->where('modulo', $module)
                ->where('accion', $action))
            ->exists();
    }

    public function hasInternalAccess(): bool
    {
        return $this->estado === 'ACTIVO'
            && $this->pacienteCuenta()->doesntExist()
            && $this->roles()->exists();
    }

    public function getAuthPasswordName()
    {
        return 'password';
    }

    public function getAuthPassword()
    {
        return (string) $this->getAttribute($this->getAuthPasswordName());
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres . ' ' . ($this->apellidos ?? ''));
    }
}
