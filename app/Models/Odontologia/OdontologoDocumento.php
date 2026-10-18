<?php

namespace App\Models\Odontologia;

use App\Models\Odontologia\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdontologoDocumento extends Model
{
    use BelongsToClinic;

    protected $table = 'odo_odontologo_documentos';
    protected $primaryKey = 'id_documento_odontologo';
    protected $fillable = [
        'id_clinica', 'id_odontologo', 'id_especialidad', 'tipo_documento', 'nombre',
        'institucion_emisora', 'referencia', 'fecha_emision', 'fecha_vencimiento',
        'archivo_path', 'archivo_nombre_original', 'archivo_mime', 'archivo_tamano',
        'archivo_hash', 'estado_revision', 'observaciones_revision', 'id_usuario_revision',
        'fecha_revision', 'id_documento_anterior', 'vigente', 'observaciones', 'id_usuario_creacion',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_revision' => 'datetime',
        'vigente' => 'boolean',
    ];

    public function odontologo(): BelongsTo { return $this->belongsTo(Odontologo::class, 'id_odontologo', 'id_odontologo'); }
    public function especialidad(): BelongsTo { return $this->belongsTo(Especialidad::class, 'id_especialidad', 'id_especialidad'); }
    public function anterior(): BelongsTo { return $this->belongsTo(self::class, 'id_documento_anterior', 'id_documento_odontologo'); }
}
