<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comprobante extends Model
{
    protected $table = 'odo_comprobantes';
    protected $primaryKey = 'id_comprobante';

    protected $fillable = [
        'tipo',
        'serie',
        'numero',
        'fecha_emision',
        'cliente_nombre',
        'cliente_documento',
        'subtotal',
        'igv',
        'total',
        'estado',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_comprobante', 'id_comprobante');
    }
}