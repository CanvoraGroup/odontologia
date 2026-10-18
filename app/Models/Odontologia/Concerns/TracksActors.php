<?php

namespace App\Models\Odontologia\Concerns;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait TracksActors
{
    public static function bootTracksActors(): void
    {
        static::creating(function (Model $model): void {
            if (Schema::hasColumn($model->getTable(), 'id_usuario_creacion') && $model->getAttribute('id_usuario_creacion') === null) {
                $model->setAttribute('id_usuario_creacion', TenantContext::actorId());
            }
        });

        static::updating(function (Model $model): void {
            if (Schema::hasColumn($model->getTable(), 'id_usuario_modificacion')) {
                $model->setAttribute('id_usuario_modificacion', TenantContext::actorId());
            }
        });

        static::updated(function (Model $model): void {
            if (! Schema::hasTable('odo_auditoria') || ! $model->wasChanged() || TenantContext::id() === null) return;
            $before = $model->getOriginal();
            $after = $model->getAttributes();
            unset($before['password'], $after['password']);
            $payload = [
                'id_clinica' => TenantContext::id(),
                'id_usuario' => TenantContext::actorId(),
                'accion' => 'EDITAR',
                'tabla' => $model->getTable(),
                'registro_id' => (string) $model->getKey(),
                'created_at' => now(),
            ];
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
