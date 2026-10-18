<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use App\Models\Odontologia\Concerns\BelongsToClinic;

class ConfiguracionEmpresa extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_configuracion_empresa';
    protected $primaryKey = 'id_configuracion';
    protected $fillable = ['id_clinica','razon_social','nombre_comercial','ruc','direccion','telefono','correo','logo_path','ticket_footer','ticket_width'];
}
