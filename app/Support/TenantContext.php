<?php

namespace App\Support;

use App\Models\Odontologia\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Closure;

final class TenantContext
{
    private static ?int $forcedTenantId = null;
    private static ?int $forcedActorId = null;
    private static bool $forced = false;
    public static function user(): ?Usuario
    {
        $user = auth()->user();
        return $user instanceof Usuario ? $user : null;
    }

    public static function id(): ?int
    {
        if (self::$forced) return self::$forcedTenantId;
        $id = self::user()?->id_clinica;
        return $id === null ? null : (int) $id;
    }

    public static function isPlatformAdministrator(): bool
    {
        if (self::$forced) return false;
        return (bool) self::user()?->es_admin_plataforma;
    }

    public static function actorId(): ?int
    {
        if (self::$forced) return self::$forcedActorId;
        return self::user()?->id_usuario;
    }

    /** Ejecuta un trabajo con contexto explícito y siempre lo limpia al terminar. */
    public static function run(int $clinicId, ?int $actorId, Closure $callback): mixed
    {
        $previous = [self::$forced, self::$forcedTenantId, self::$forcedActorId];
        self::$forced = true;
        self::$forcedTenantId = $clinicId;
        self::$forcedActorId = $actorId;
        try {
            return $callback();
        } finally {
            [self::$forced, self::$forcedTenantId, self::$forcedActorId] = $previous;
        }
    }

    public static function requireId(): int
    {
        $id = self::id();
        if ($id === null) {
            throw new HttpException(403, 'Esta cuenta no tiene una empresa clínica activa.');
        }

        return $id;
    }

    public static function timezone(): string
    {
        return (string) config('app.timezone', 'America/Lima');
    }

    public static function constrain(Builder $query, string $column = 'id_clinica'): Builder
    {
        if (self::isPlatformAdministrator()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($query->getModel()->qualifyColumn($column), self::requireId());
    }
}
