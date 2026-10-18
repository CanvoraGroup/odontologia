<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use App\Models\Odontologia\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresupuestoDetalle extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_presupuesto_detalles';
    protected $primaryKey = 'id_detalle';
    protected $fillable = ['id_version','id_servicio','codigo_servicio','nombre_servicio','forma_cobro','requiere_pieza','requiere_caras','precio_catalogo','precio_unitario','descuento','cantidad','pieza_fdi','caras','prioridad','sesiones_estimadas','observaciones','motivo_precio','motivo_descuento','subtotal'];
    protected $casts = ['requiere_pieza' => 'boolean', 'requiere_caras' => 'boolean', 'precio_catalogo' => 'decimal:2', 'precio_unitario' => 'decimal:2', 'descuento' => 'decimal:2', 'prioridad' => 'integer', 'cantidad' => 'integer', 'sesiones_estimadas' => 'integer', 'subtotal' => 'decimal:2'];
    public function version(): BelongsTo { return $this->belongsTo(PresupuestoVersion::class, 'id_version', 'id_version'); }
    public function servicio(): BelongsTo { return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio'); }
}
