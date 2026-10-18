<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Especialidad extends Model
{
    protected $table = 'odo_especialidades';
    protected $primaryKey = 'id_especialidad';
    protected $fillable = ['nombre', 'descripcion', 'estado'];

    public function odontologos(): BelongsToMany
    {
        return $this->belongsToMany(Odontologo::class, 'odo_odontologo_especialidades', 'id_especialidad', 'id_odontologo');
    }
}
