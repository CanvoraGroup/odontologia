<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;

class HistoriaAdjunto extends Model
{
    protected $table = 'odo_historia_adjuntos';
    protected $primaryKey = 'id_adjunto';

    protected $fillable = [
        'id_historia',
        'tipo_documento',
        'nombre_original',
        'archivo_path',
        'extension',
        'mime_type',
        'tamano_bytes',
        'descripcion',
        'estado',
    ];

    public function historia()
    {
        return $this->belongsTo(HistoriaClinica::class, 'id_historia', 'id_historia');
    }
}