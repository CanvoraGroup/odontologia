<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permiso extends Model
{
    protected $table = 'odo_permisos';
    protected $primaryKey = 'id_permiso';
    protected $fillable = ['modulo', 'accion', 'descripcion'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'odo_rol_permisos', 'id_permiso', 'id_rol')
            ->withTimestamps();
    }
}
