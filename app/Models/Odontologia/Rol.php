<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rol extends Model
{
    protected $table = 'odo_roles';
    protected $primaryKey = 'id_rol';
    protected $fillable = ['id_clinica', 'nombre', 'descripcion', 'estado'];

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'odo_rol_permisos', 'id_rol', 'id_permiso')
            ->withTimestamps();
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'odo_usuario_roles', 'id_rol', 'id_usuario')
            ->withTimestamps();
    }
}
