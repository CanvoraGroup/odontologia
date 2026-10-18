<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use App\Models\Odontologia\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PagoReceptor extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_pago_receptores';
    protected $primaryKey = 'id_receptor';
    protected $fillable = ['tipo_documento', 'numero_documento', 'nombre_razon_social', 'direccion'];

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_receptor', 'id_receptor');
    }
}
