<?php

namespace App\Support;

use App\Models\Odontologia\Cita;
use App\Models\Odontologia\DisponibilidadBloqueo;
use App\Models\Odontologia\HorarioOdontologo;
use App\Models\Odontologia\HorarioExcepcion;
use App\Models\Odontologia\Odontologo;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;

class DisponibilidadOdontologo
{
    public static function validarCita(array $data, ?int $idCita = null): void
    {
        $doctor = Odontologo::whereKey((int) $data['id_odontologo'])->firstOrFail();
        if ($doctor->estado !== 'ACTIVO') {
            throw ValidationException::withMessages(['id_odontologo' => 'El odontólogo está inactivo y no puede recibir nuevas asignaciones.']);
        }

        $date = Carbon::parse($data['fecha'], TenantContext::timezone());
        $start = Carbon::createFromFormat('H:i', substr((string) $data['hora_inicio'], 0, 5), TenantContext::timezone());
        $end = Carbon::createFromFormat('H:i', substr((string) $data['hora_fin'], 0, 5), TenantContext::timezone());
        $slotsQuery = HorarioOdontologo::where('id_odontologo', $doctor->id_odontologo)->where('estado', 'ACTIVO')->where('dia_semana', $date->dayOfWeekIso);
        if (Schema::hasColumn('odo_horarios_odontologos', 'fecha_desde')) {
            $slotsQuery->where(function ($query) use ($date): void {
                $query->whereNull('fecha_desde')->orWhereDate('fecha_desde', '<=', $date->toDateString());
            })->where(function ($query) use ($date): void {
                $query->whereNull('fecha_hasta')->orWhereDate('fecha_hasta', '>=', $date->toDateString());
            });
        }
        $slots = $slotsQuery->get();
        if (Schema::hasTable('odo_horarios_excepciones')) {
            $exception = HorarioExcepcion::where('id_odontologo', $doctor->id_odontologo)->whereDate('fecha', $date->toDateString())->where('estado', 'ACTIVO')->latest('id_excepcion')->first();
            if ($exception?->tipo === 'BLOQUEADO') {
                throw ValidationException::withMessages(['hora_inicio' => 'La fecha seleccionada está bloqueada para este odontólogo.']);
            }
            if ($exception?->tipo === 'DISPONIBLE') {
                $slots = collect([$exception]);
            }
        }
        if ($slots->isEmpty()) {
            throw ValidationException::withMessages(['hora_inicio' => 'Sin horario configurado para este día. Configura disponibilidad antes de aceptar nuevas reservas.']);
        }
        $inside = $slots->contains(fn ($slot) => $start->format('H:i:s') >= $slot->hora_inicio && $end->format('H:i:s') <= $slot->hora_fin && (!$data['id_consultorio'] || !$slot->id_consultorio || (int) $slot->id_consultorio === (int) $data['id_consultorio']));
        if (!$inside) {
            throw ValidationException::withMessages(['hora_inicio' => 'La cita debe quedar completamente dentro de un tramo disponible del odontólogo.']);
        }
        $blocked = Schema::hasTable('odo_bloqueos_odontologos') && DisponibilidadBloqueo::where('id_odontologo', $doctor->id_odontologo)->where('estado', 'ACTIVO')->whereDate('fecha_desde', '<=', $date->toDateString())->whereDate('fecha_hasta', '>=', $date->toDateString())->get()->contains(function ($block) use ($start, $end) {
            return $block->dia_completo || (!$block->hora_desde || !$block->hora_hasta || ($block->hora_desde < $end->format('H:i:s') && $block->hora_hasta > $start->format('H:i:s')));
        });
        if ($blocked) {
            throw ValidationException::withMessages(['hora_inicio' => 'El horario está bloqueado por una ausencia o bloqueo del odontólogo.']);
        }
    }

    public static function validarCruceHorario(int $doctorId, int $day, string $start, string $end, ?int $ignore = null, ?string $rangeFrom = null, ?string $rangeTo = null): void
    {
        $query = HorarioOdontologo::where('id_odontologo', $doctorId)->where('dia_semana', $day)->where('estado', 'ACTIVO')->when($ignore, fn ($q) => $q->where('id_horario', '!=', $ignore))->where('hora_inicio', '<', $end)->where('hora_fin', '>', $start);
        if ($rangeFrom !== null && Schema::hasColumn('odo_horarios_odontologos', 'fecha_desde')) {
            $query->where(function ($q) use ($rangeFrom, $rangeTo): void {
                $q->whereNull('fecha_desde')->orWhereDate('fecha_desde', '<=', $rangeTo);
            })->where(function ($q) use ($rangeFrom): void {
                $q->whereNull('fecha_hasta')->orWhereDate('fecha_hasta', '>=', $rangeFrom);
            });
        }
        $overlap = $query->exists();
        if ($overlap) throw ValidationException::withMessages(['hora_inicio' => 'El tramo se cruza con otro horario del odontólogo, incluso en otro consultorio.']);
    }
}
