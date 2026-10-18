<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use App\Models\Odontologia\Concerns\BelongsToClinic;

class HistoriaAdjunto extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_historia_adjuntos';
    protected $primaryKey = 'id_adjunto';

    protected $fillable = [
        'id_historia',
        'tipo_documento',
        'nombre_original',
        'archivo_path',
        'ruta_publica_anterior',
        'extension',
        'mime_type',
        'tamano_bytes',
        'archivo_hash_sha256',
        'descripcion',
        'estado',
    ];

    public function historia()
    {
        return $this->belongsTo(HistoriaClinica::class, 'id_historia', 'id_historia');
    }
}
