<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Odontologia\Odontograma;
use App\Models\Odontologia\Cita;
use App\Models\Odontologia\Pago;
use App\Models\Odontologia\Concerns\BelongsToClinic;
class Paciente extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_pacientes';
    protected $primaryKey = 'id_paciente';

    protected $fillable = [
        'id_clinica',
        'tipo_documento',
        'numero_documento',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'sexo',
        'telefono',
        'correo',
        'direccion',
        'contacto_emergencia',
        'telefono_emergencia',
        'observacion',
        'estado',
        'id_usuario_creacion',
        'id_usuario_desactivacion',
        'fecha_desactivacion',
        'motivo_desactivacion',
        'id_usuario_reactivacion',
        'fecha_reactivacion',
    ];

    protected $casts = [
        'fecha_desactivacion' => 'datetime',
        'fecha_reactivacion' => 'datetime',
    ];


    public function odontogramas(): HasMany
    {
        return $this->hasMany(Odontograma::class, 'id_paciente', 'id_paciente');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_paciente', 'id_paciente');
    }
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_paciente', 'id_paciente');
    }

    public function cuentaAcceso(): HasOne
    {
        return $this->hasOne(UsuarioPaciente::class, 'id_paciente', 'id_paciente');
    }
}
