<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Odontologia\Concerns\BelongsToClinic;

class CategoriaServicio extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_categorias_servicios';
    protected $primaryKey = 'id_categoria';

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'estado'];

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_categoria', 'id_categoria');
    }
}
