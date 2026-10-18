<?php

namespace App\Models\Odontologia\Concerns;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

trait BelongsToClinic
{
    public static function bootBelongsToClinic(): void
    {
        static::addGlobalScope('clinic', function (Builder $builder): void {
            $table = $builder->getModel()->getTable();
            if (Schema::hasColumn($table, 'id_clinica')) {
                TenantContext::constrain($builder);
            }
        });

        static::creating(function (Model $model): void {
            if (Schema::hasColumn($model->getTable(), 'id_clinica') && $model->getAttribute('id_clinica') === null) {
                $model->setAttribute('id_clinica', TenantContext::requireId());
            }
            if (Schema::hasColumn($model->getTable(), 'id_usuario_creacion') && $model->getAttribute('id_usuario_creacion') === null) {
                $model->setAttribute('id_usuario_creacion', TenantContext::actorId());
            }
        });

        static::updating(function (Model $model): void {
            if (Schema::hasColumn($model->getTable(), 'id_clinica') && $model->isDirty('id_clinica')) {
                abort(403, 'La empresa propietaria no puede cambiarse en una edición ordinaria.');
            }
            if (Schema::hasColumn($model->getTable(), 'id_usuario_modificacion')) {
                $model->setAttribute('id_usuario_modificacion', TenantContext::actorId());
            }
            if (Schema::hasColumn($model->getTable(), 'id_usuario_anulacion') && $model->isDirty('estado') && in_array($model->getAttribute('estado'), ['ANULADO', 'CANCELADA'], true)) {
                $model->setAttribute('id_usuario_anulacion', TenantContext::actorId());
                if (Schema::hasColumn($model->getTable(), 'fecha_anulacion')) $model->setAttribute('fecha_anulacion', now());
            }
        });

        static::updated(function (Model $model): void {
            if (! Schema::hasTable('odo_auditoria') || ! $model->wasChanged()) return;
            $clinicId = TenantContext::id();
            if ($clinicId === null) return;
            $before = $model->getOriginal();
            $after = $model->getAttributes();
            unset($before['password'], $after['password']);
            $payload = [
                'id_clinica' => $clinicId,
                'id_usuario' => TenantContext::actorId(),
                'accion' => $model->wasChanged('estado') && in_array($model->estado, ['ANULADO', 'CANCELADA'], true) ? 'ANULAR' : 'EDITAR',
                'tabla' => $model->getTable(),
                'created_at' => now(),
            ];
            $payload[Schema::hasColumn('odo_auditoria', 'id_registro') ? 'id_registro' : 'registro_id'] = (string) $model->getKey();
            if (Schema::hasColumn('odo_auditoria', 'datos_anteriores')) {
                $payload['datos_anteriores'] = json_encode($before, JSON_UNESCAPED_UNICODE);
                $payload['datos_nuevos'] = json_encode($after, JSON_UNESCAPED_UNICODE);
            } else {
                $payload['valor_anterior'] = json_encode($before, JSON_UNESCAPED_UNICODE);
                $payload['valor_nuevo'] = json_encode($after, JSON_UNESCAPED_UNICODE);
            }
            DB::table('odo_auditoria')->insert($payload);
        });
    }
}
