<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pago extends Model
{
    protected $table = 'odo_pagos';
    protected $primaryKey = 'id_pago';

    protected $fillable = [
        'id_paciente',
        'id_cita',
        'id_plan_tratamiento',
        'id_comprobante',
        'id_metodo_pago',
        'fecha_pago',
        'concepto',
        'monto',
        'estado',
        'observacion',
        'id_usuario_creacion',
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
        'monto' => 'decimal:2',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita', 'id_cita');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(PagoDetalle::class, 'id_pago', 'id_pago');
    }

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class, 'id_comprobante', 'id_comprobante');
    }
}