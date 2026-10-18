<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use App\Models\Odontologia\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pago extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_pagos';
    protected $primaryKey = 'id_pago';

    protected $fillable = [
        'id_paciente',
        'numero_constancia',
        'idempotency_key',
        'id_receptor',
        'id_cita',
        'id_plan_tratamiento',
        'id_comprobante',
        'id_metodo_pago',
        'referencia_operacion',
        'fecha_pago',
        'concepto',
        'monto',
        'estado',
        'observacion',
        'id_usuario_creacion',
        'fecha_anulacion',
        'id_usuario_anulacion',
        'motivo_anulacion',
        'snapshot_razon_social', 'snapshot_nombre_comercial', 'snapshot_ruc', 'snapshot_direccion', 'snapshot_telefono', 'snapshot_correo', 'snapshot_logo_path', 'snapshot_ticket_footer', 'snapshot_ticket_width',
        'snapshot_receptor_tipo_documento', 'snapshot_receptor_numero_documento', 'snapshot_receptor_nombre', 'snapshot_receptor_direccion', 'snapshot_total_presupuesto', 'snapshot_abonado_acumulado', 'snapshot_saldo_pendiente',
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
        'monto' => 'decimal:2',
        'fecha_anulacion' => 'datetime',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita', 'id_cita');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanTratamiento::class, 'id_plan_tratamiento', 'id_plan_tratamiento');
    }

    public function metodo(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'id_metodo_pago', 'id_metodo_pago');
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(PagoReceptor::class, 'id_receptor', 'id_receptor');
    }

    public function usuarioCreacion(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_creacion', 'id_usuario');
    }

    public function usuarioAnulacion(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_anulacion', 'id_usuario');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(PagoDetalle::class, 'id_pago', 'id_pago');
    }

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class, 'id_comprobante', 'id_comprobante');
    }

    public function evidencias(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PagoEvidencia::class, 'id_pago', 'id_pago');
    }

    public function evidenciaActiva(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PagoEvidencia::class, 'id_pago', 'id_pago')->where('estado', 'ACTIVA')->latestOfMany('id_evidencia');
    }

    public function receiptCompanySnapshot(): object
    {
        return (object) [
            'razon_social' => $this->snapshot_razon_social,
            'nombre_comercial' => $this->snapshot_nombre_comercial,
            'ruc' => $this->snapshot_ruc,
            'direccion' => $this->snapshot_direccion,
            'telefono' => $this->snapshot_telefono,
            'correo' => $this->snapshot_correo,
            'logo_path' => $this->snapshot_logo_path,
            'ticket_footer' => $this->snapshot_ticket_footer,
            'ticket_width' => $this->snapshot_ticket_width,
        ];
    }
}
