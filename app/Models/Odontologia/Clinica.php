<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Odontologia\Concerns\TracksActors;

class Clinica extends Model
{
    use TracksActors;
    protected $table = 'odo_clinicas';
    protected $primaryKey = 'id_clinica';

    protected $fillable = [
        'nombre_comercial', 'razon_social', 'ruc', 'telefono', 'correo', 'direccion',
        'logo_path', 'ticket_footer', 'ticket_width', 'estado',
    ];

    protected $casts = ['ticket_width' => 'integer'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'id_clinica', 'id_clinica');
    }
}
