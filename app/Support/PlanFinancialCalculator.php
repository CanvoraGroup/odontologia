<?php

namespace App\Support;

use App\Models\Odontologia\PlanTratamiento;

final class PlanFinancialCalculator
{
    public function calculate(PlanTratamiento $plan, bool $lockPayments = false): array
    {
        $version = $plan->relationLoaded('versiones')
            ? $plan->versiones->where('estado', 'ACEPTADO')->sortByDesc('numero_version')->first()
            : $plan->versiones()->where('estado', 'ACEPTADO')->orderByDesc('numero_version')->first();

        if ($plan->relationLoaded('pagos') && ! $lockPayments) {
            $payments = $plan->pagos->where('estado', 'PAGADO')->values();
        } else {
            $query = $plan->pagos()->where('estado', 'PAGADO');
            if ($lockPayments) $query->lockForUpdate();
            $payments = $query->get();
        }
        $totalCents = $version ? $this->moneyToCents($version->total) : 0;
        $paidCents = (int) $payments->sum(fn ($payment) => $this->moneyToCents($payment->monto));
        $balance = max(0, $totalCents - $paidCents);

        return [
            'version' => $version,
            'payments' => $payments,
            'total_cents' => $totalCents,
            'paid_cents' => $paidCents,
            'saldo_cents' => $balance,
            'status' => $balance === 0 && $totalCents > 0 ? 'PAGADO' : ($paidCents > 0 ? 'PARCIAL' : 'PENDIENTE'),
        ];
    }

    public function moneyToCents(mixed $amount): int
    {
        $normalized = number_format((float) $amount, 2, '.', '');
        [$whole, $decimal] = array_pad(explode('.', $normalized, 2), 2, '0');
        return ((int) $whole * 100) + (int) str_pad($decimal, 2, '0');
    }
}
