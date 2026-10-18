<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\ConfiguracionEmpresa;
use App\Models\Odontologia\Cita;
use App\Models\Odontologia\MetodoPago;
use App\Models\Odontologia\Pago;
use App\Models\Odontologia\PlanTratamiento;
use App\Models\Odontologia\PresupuestoVersion;
use App\Models\Odontologia\TratamientoSesion;
use App\Models\Odontologia\Usuario;
use App\Support\PlanFinancialCalculator;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class ReporteController extends Controller
{
    public function cobros(Request $request)
    {
        $this->authorizeReport('cobros_ver');
        $filters = $this->filters($request);
        $query = $this->paymentsQuery($filters);
        $summary = $this->paymentsSummary($query);
        $payments = (clone $query)->with(['paciente', 'plan', 'metodo', 'usuarioCreacion', 'usuarioAnulacion'])
            ->orderByDesc('fecha_pago')->orderByDesc('id_pago')->paginate(25)->withQueryString();

        return view('odontologia.reportes.cobros', [
            'payments' => $payments,
            'summary' => $summary,
            'filters' => $filters,
            'methods' => MetodoPago::orderBy('nombre')->get(),
            'users' => $this->clinicUsers(),
        ]);
    }

    public function cobrosExport(Request $request)
    {
        $this->authorizeReport('cobros_exportar');
        $filters = $this->filters($request);
        $payments = $this->paymentsQuery($filters)->with(['paciente', 'plan', 'metodo', 'usuarioCreacion', 'usuarioAnulacion'])->orderBy('fecha_pago')->get();
        return $this->csvDownload('reporte-cobros-'.now('America/Lima')->format('Ymd-His').'.csv', function ($handle) use ($payments): void {
            $this->csvRow($handle, ['Fecha y hora de pago', 'N.º de pago', 'Paciente', 'Tipo de documento', 'Número de documento', 'N.º de presupuesto', 'Método de pago', 'Referencia de operación', 'Monto cobrado (S/)', 'Estado del pago', 'Registrado por']);
            foreach ($payments as $payment) {
                $this->csvRow($handle, [
                    optional($payment->fecha_pago)->timezone('America/Lima')->format('d/m/Y H:i'),
                    $payment->numero_constancia,
                    trim(($payment->paciente?->nombres ?? '').' '.($payment->paciente?->apellidos ?? '')),
                    $payment->paciente?->tipo_documento,
                    $payment->paciente?->numero_documento,
                    $payment->plan?->numero,
                    $payment->metodo?->nombre,
                    $payment->referencia_operacion,
                    number_format((float) $payment->monto, 2, '.', ''),
                    $payment->estado,
                    $payment->usuarioCreacion?->nombre_completo,
                ]);
            }
        });
    }

    public function cobrosPrint(Request $request)
    {
        $this->authorizeReport('cobros_imprimir');
        $filters = $this->filters($request);
        $query = $this->paymentsQuery($filters);
        $payments = $query->with(['paciente', 'plan', 'metodo', 'usuarioCreacion'])->orderBy('fecha_pago')->get();
        $summary = $this->paymentsSummary($this->paymentsQuery($filters));
        return view('odontologia.reportes.print-cobros', $this->documentData(compact('payments', 'summary', 'filters')));
    }

    public function saldos(Request $request)
    {
        $this->authorizeReport('saldos_ver');
        $result = $this->balancesData($request);
        return view('odontologia.reportes.saldos', $result + ['methods' => MetodoPago::orderBy('nombre')->get()]);
    }

    public function saldosExport(Request $request)
    {
        $this->authorizeReport('saldos_exportar');
        $result = $this->balancesData($request, false);
        $plans = $result['allPlans'];
        return $this->csvDownload('reporte-saldos-'.now('America/Lima')->format('Ymd-His').'.csv', function ($handle) use ($plans): void {
            $this->csvRow($handle, ['Paciente', 'Tipo de documento', 'Número de documento', 'Teléfono', 'N.º de presupuesto', 'Fecha de presupuesto', 'Odontólogo responsable', 'Versión', 'Total presupuesto (S/)', 'Abonado válido (S/)', 'Saldo pendiente (S/)', 'Fecha del último pago', 'Estado del tratamiento', 'Estado financiero']);
            foreach ($plans as $plan) {
                $f = $plan->financial;
                $this->csvRow($handle, [
                    trim($plan->paciente->nombres.' '.$plan->paciente->apellidos),
                    $plan->paciente->tipo_documento, $plan->paciente->numero_documento, $plan->paciente->telefono,
                    $plan->numero, optional($plan->fecha_plan)->format('d/m/Y'), $plan->odontologo?->nombre_completo,
                    $f['version']?->numero_version, number_format($f['total_cents'] / 100, 2, '.', ''),
                    number_format($f['paid_cents'] / 100, 2, '.', ''), number_format($f['saldo_cents'] / 100, 2, '.', ''),
                    $this->lastPaymentDate($f['payments']), $plan->estado_clinico, $f['status'],
                ]);
            }
        });
    }

    public function saldosPrint(Request $request)
    {
        $this->authorizeReport('saldos_imprimir');
        $result = $this->balancesData($request, false);
        return view('odontologia.reportes.print-saldos', $this->documentData($result));
    }

    public function citas(Request $request)
    {
        $this->authorizeReport('citas_ver');
        $result = $this->appointmentsData($request);
        return view('odontologia.reportes.citas', $result);
    }

    public function citasExport(Request $request)
    {
        $this->authorizeReport('citas_exportar');
        $result = $this->appointmentsData($request, false);
        return $this->csvDownload('reporte-citas-asistencia-'.now('America/Lima')->format('Ymd-His').'.csv', function ($handle) use ($result): void {
            $this->csvRow($handle, ['Fecha', 'Hora', 'Paciente', 'Tipo de documento', 'Número de documento', 'Odontólogo', 'Consultorio', 'Motivo', 'Estado de cita', 'Estado de atención']);
            foreach ($result['allAppointments'] as $appointment) {
                $this->csvRow($handle, $this->appointmentRow($appointment));
            }
        });
    }

    public function citasPrint(Request $request)
    {
        $this->authorizeReport('citas_imprimir');
        $result = $this->appointmentsData($request, false);
        return view('odontologia.reportes.print-citas', $this->documentData($result));
    }

    public function tratamientos(Request $request)
    {
        $this->authorizeReport('tratamientos_ver');
        return view('odontologia.reportes.tratamientos', $this->treatmentsData($request));
    }

    public function tratamientosExport(Request $request)
    {
        $this->authorizeReport('tratamientos_exportar');
        $result = $this->treatmentsData($request, false);
        return $this->csvDownload('reporte-tratamientos-avance-'.now('America/Lima')->format('Ymd-His').'.csv', function ($handle) use ($result): void {
            $this->csvRow($handle, ['Paciente', 'Tipo de documento', 'Número de documento', 'N.º de plan', 'Fecha de creación', 'Odontólogo responsable', 'Procedimientos incluidos', 'Terminados', 'Pendientes', 'Avance', 'Estado del plan']);
            foreach ($result['allPlans'] as $plan) {
                $this->csvRow($handle, $this->treatmentRow($plan));
            }
        });
    }

    public function tratamientosPrint(Request $request)
    {
        $this->authorizeReport('tratamientos_imprimir');
        $result = $this->treatmentsData($request, false);
        return view('odontologia.reportes.print-tratamientos', $this->documentData($result));
    }

    public function presupuestos(Request $request)
    {
        $this->authorizeReport('presupuestos_ver');
        return view('odontologia.reportes.presupuestos', $this->budgetsData($request));
    }

    public function presupuestosExport(Request $request)
    {
        $this->authorizeReport('presupuestos_exportar');
        $result = $this->budgetsData($request, false);
        return $this->csvDownload('reporte-presupuestos-'.now('America/Lima')->format('Ymd-His').'.csv', function ($handle) use ($result): void {
            $this->csvRow($handle, ['N.º de presupuesto', 'Fecha', 'Paciente', 'Tipo de documento', 'Número de documento', 'Odontólogo responsable', 'Versión', 'Estado comercial', 'Subtotal (S/)', 'Descuento (S/)', 'Total final (S/)']);
            foreach ($result['allPlans'] as $plan) $this->csvRow($handle, $this->budgetRow($plan));
        });
    }

    public function presupuestosPrint(Request $request)
    {
        $this->authorizeReport('presupuestos_imprimir');
        return view('odontologia.reportes.print-presupuestos', $this->documentData($this->budgetsData($request, false)));
    }

    public function produccion(Request $request)
    {
        $this->authorizeReport('produccion_ver');
        return view('odontologia.reportes.produccion', $this->productionData($request));
    }

    public function produccionExport(Request $request)
    {
        $this->authorizeReport('produccion_exportar');
        $result = $this->productionData($request, false);
        return $this->csvDownload('reporte-produccion-odontologo-'.now('America/Lima')->format('Ymd-His').'.csv', function ($handle) use ($result): void {
            $this->csvRow($handle, ['Fecha de realización', 'Paciente', 'Tipo de documento', 'Número de documento', 'N.º de plan', 'Procedimiento', 'Cantidad realizada', 'Odontólogo ejecutor', 'Valor de procedimientos realizados (S/)']);
            foreach ($result['allRows'] as $row) $this->csvRow($handle, $this->productionRow($row));
        });
    }

    public function produccionPrint(Request $request)
    {
        $this->authorizeReport('produccion_imprimir');
        return view('odontologia.reportes.print-produccion', $this->documentData($this->productionData($request, false)));
    }

    private function balancesData(Request $request, bool $paginate = true): array
    {
        $term = trim((string) $request->input('buscar', ''));
        $clinical = (string) $request->input('estado_clinico', '');
        $paymentsFilter = (string) $request->input('pagos', 'todos');
        $query = PlanTratamiento::with(['paciente', 'odontologo', 'versiones' => fn ($q) => $q->where('estado', 'ACEPTADO'), 'pagos' => fn ($q) => $q->where('estado', 'PAGADO')])
            ->whereHas('versiones', fn ($q) => $q->where('estado', 'ACEPTADO'))
            ->when($term !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('numero', 'like', "%{$term}%")
                ->orWhereHas('paciente', fn ($p) => $p->where('nombres', 'like', "%{$term}%")->orWhere('apellidos', 'like', "%{$term}%")->orWhere('numero_documento', 'like', "%{$term}%"))))
            ->when($clinical !== '', fn ($q) => $q->where('estado_clinico', $clinical))
            ->orderByDesc('id_plan_tratamiento');

        $allPlans = $query->get()->map(function (PlanTratamiento $plan): PlanTratamiento {
            $financial = app(PlanFinancialCalculator::class)->calculate($plan);
            $plan->setAttribute('financial', $financial);
            return $plan;
        })->filter(function (PlanTratamiento $plan) use ($paymentsFilter): bool {
            if ($plan->financial['saldo_cents'] <= 0) return false;
            $hasPayments = $plan->financial['paid_cents'] > 0;
            return $paymentsFilter === 'todos' || ($paymentsFilter === 'con_pagos' && $hasPayments) || ($paymentsFilter === 'sin_pagos' && ! $hasPayments);
        })->values();

        $summary = [
            'saldo_total_cents' => (int) $allPlans->sum(fn ($plan) => $plan->financial['saldo_cents']),
            'plans_count' => $allPlans->count(),
            'patients_count' => $allPlans->pluck('id_paciente')->unique()->count(),
        ];
        $paginator = $paginate ? $this->paginateCollection($allPlans, $request) : null;
        return ['plans' => $paginator, 'allPlans' => $allPlans, 'summary' => $summary, 'term' => $term, 'clinical' => $clinical, 'paymentsFilter' => $paymentsFilter];
    }

    private function appointmentsData(Request $request, bool $paginate = true): array
    {
        [$from, $to] = $this->dateRange($request, 'desde', 'hasta');
        $doctor = (string) $request->input('odontologo', '');
        $office = (string) $request->input('consultorio', '');
        $status = trim((string) $request->input('estado', ''));
        $query = Cita::with(['paciente', 'odontologo', 'consultorio', 'atencion'])
            ->whereDate('fecha', '>=', $from->toDateString())
            ->whereDate('fecha', '<=', $to->toDateString())
            ->when($doctor !== '' && \App\Models\Odontologia\Odontologo::whereKey((int) $doctor)->exists(), fn ($q) => $q->where('id_odontologo', (int) $doctor))
            ->when($office !== '' && \App\Models\Odontologia\Consultorio::whereKey((int) $office)->exists(), fn ($q) => $q->where('id_consultorio', (int) $office))
            ->when($status !== '', fn ($q) => $q->where('estado', $status))
            ->orderBy('fecha')->orderBy('hora_inicio')->orderBy('id_cita');
        $all = $query->get();
        $states = $all->groupBy('estado')->map->count()->sortKeys();
        $paginator = $paginate ? $this->paginateCollection($all, $request) : null;
        return [
            'appointments' => $paginator,
            'allAppointments' => $all,
            'summary' => ['total' => $all->count(), 'states' => $states],
            'filters' => ['from' => $from, 'to' => $to, 'doctor' => $doctor, 'office' => $office, 'status' => $status],
            'doctors' => \App\Models\Odontologia\Odontologo::where('estado', 'ACTIVO')->orderBy('apellidos')->orderBy('nombres')->get(),
            'offices' => \App\Models\Odontologia\Consultorio::where('estado', 'ACTIVO')->orderBy('nombre')->get(),
            'states' => Cita::query()->select('estado')->distinct()->orderBy('estado')->pluck('estado'),
        ];
    }

    private function appointmentRow(Cita $appointment): array
    {
        return [
            optional($appointment->fecha)->format('d/m/Y'),
            $appointment->hora_inicio,
            trim(($appointment->paciente?->nombres ?? '').' '.($appointment->paciente?->apellidos ?? '')),
            $appointment->paciente?->tipo_documento,
            $appointment->paciente?->numero_documento,
            $appointment->odontologo?->nombre_completo,
            $appointment->consultorio?->nombre,
            $appointment->motivo,
            $appointment->estado,
            $appointment->atencion?->estado,
        ];
    }

    private function treatmentsData(Request $request, bool $paginate = true): array
    {
        $term = trim((string) $request->input('buscar', ''));
        [$from, $to] = $this->dateRange($request, 'desde', 'hasta');
        $doctor = (string) $request->input('odontologo', '');
        $status = trim((string) $request->input('estado', ''));
        $query = PlanTratamiento::with(['paciente', 'odontologo', 'procedimientosClinicos'])
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->when($term !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('numero', 'like', "%{$term}%")
                ->orWhereHas('paciente', fn ($p) => $p->where('nombres', 'like', "%{$term}%")->orWhere('apellidos', 'like', "%{$term}%")->orWhere('numero_documento', 'like', "%{$term}%"))))
            ->when($doctor !== '' && \App\Models\Odontologia\Odontologo::whereKey((int) $doctor)->exists(), fn ($q) => $q->where('id_odontologo', (int) $doctor))
            ->when($status !== '', fn ($q) => $q->where('estado_clinico', $status))
            ->orderByDesc('fecha_plan')->orderByDesc('id_plan_tratamiento');
        $all = $query->get()->map(function (PlanTratamiento $plan): PlanTratamiento {
            $plan->setAttribute('clinical_report', $this->clinicalReportData($plan));
            return $plan;
        });
        $summary = [
            'plans' => $all->count(),
            'states' => $all->groupBy('estado_clinico')->map->count()->sortKeys(),
            'finished' => (int) $all->sum(fn ($plan) => $plan->clinical_report['finished']),
            'pending' => (int) $all->sum(fn ($plan) => $plan->clinical_report['pending']),
        ];
        return [
            'plans' => $paginate ? $this->paginateCollection($all, $request) : null,
            'allPlans' => $all,
            'summary' => $summary,
            'filters' => ['term' => $term, 'from' => $from, 'to' => $to, 'doctor' => $doctor, 'status' => $status],
            'doctors' => \App\Models\Odontologia\Odontologo::where('estado', 'ACTIVO')->orderBy('apellidos')->orderBy('nombres')->get(),
            'statuses' => PlanTratamiento::query()->select('estado_clinico')->distinct()->orderBy('estado_clinico')->pluck('estado_clinico'),
        ];
    }

    private function budgetsData(Request $request, bool $paginate = true): array
    {
        [$from, $to] = $this->dateRange($request, 'desde', 'hasta');
        $term = trim((string) $request->input('buscar', ''));
        $doctor = (string) $request->input('odontologo', '');
        $status = trim((string) $request->input('estado', ''));
        $query = PlanTratamiento::with(['paciente', 'odontologo', 'versiones' => fn ($q) => $q->with('detalles')->orderByDesc('numero_version')])
            ->whereDate('fecha_plan', '>=', $from->toDateString())
            ->whereDate('fecha_plan', '<=', $to->toDateString())
            ->whereHas('versiones')
            ->when($term !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('numero', 'like', "%{$term}%")
                ->orWhereHas('paciente', fn ($p) => $p->where('nombres', 'like', "%{$term}%")->orWhere('apellidos', 'like', "%{$term}%")->orWhere('numero_documento', 'like', "%{$term}%"))))
            ->when($doctor !== '' && \App\Models\Odontologia\Odontologo::whereKey((int) $doctor)->exists(), fn ($q) => $q->where('id_odontologo', (int) $doctor))
            ->orderByDesc('fecha_plan')->orderByDesc('id_plan_tratamiento');
        $all = $query->get()->map(function (PlanTratamiento $plan): PlanTratamiento {
            $version = $this->currentBudgetVersion($plan->versiones);
            $details = $version?->detalles ?? collect();
            $subtotal = (float) $details->sum(fn ($detail) => (float) $detail->precio_unitario * (int) $detail->cantidad);
            $discount = (float) $details->sum(fn ($detail) => (float) $detail->descuento);
            $plan->setAttribute('budget_report', ['version' => $version, 'status' => $version?->estado ?? 'SIN PRESUPUESTO', 'subtotal' => $subtotal, 'discount' => $discount, 'total' => (float) ($version?->total ?? $details->sum('subtotal'))]);
            return $plan;
        })->filter(fn (PlanTratamiento $plan) => $status === '' || $plan->budget_report['status'] === $status)->values();
        $decided = $all->whereIn('budget_report.status', ['ACEPTADO', 'RECHAZADO']);
        $accepted = $all->where('budget_report.status', 'ACEPTADO')->count();
        $rejected = $all->where('budget_report.status', 'RECHAZADO')->count();
        $summary = ['count' => $all->count(), 'by_status' => $all->groupBy(fn ($p) => $p->budget_report['status'])->map->count()->sortKeys(), 'amounts' => $all->groupBy(fn ($p) => $p->budget_report['status'])->map(fn ($items) => round($items->sum(fn ($p) => $p->budget_report['total']), 2)), 'acceptance' => $accepted + $rejected > 0 ? round(($accepted / ($accepted + $rejected)) * 100, 1) : null];
        return ['plans' => $paginate ? $this->paginateCollection($all, $request) : null, 'allPlans' => $all, 'summary' => $summary, 'filters' => ['from' => $from, 'to' => $to, 'term' => $term, 'doctor' => $doctor, 'status' => $status], 'doctors' => \App\Models\Odontologia\Odontologo::where('estado', 'ACTIVO')->orderBy('apellidos')->orderBy('nombres')->get(), 'statuses' => PresupuestoVersion::query()->select('estado')->distinct()->orderBy('estado')->pluck('estado')];
    }

    private function budgetRow(PlanTratamiento $plan): array
    {
        $b = $plan->budget_report;
        return [$plan->numero, optional($plan->fecha_plan)->format('d/m/Y'), trim(($plan->paciente?->nombres ?? '').' '.($plan->paciente?->apellidos ?? '')), $plan->paciente?->tipo_documento, $plan->paciente?->numero_documento, $plan->odontologo?->nombre_completo, $b['version']?->numero_version, $b['status'], number_format($b['subtotal'], 2, '.', ''), number_format($b['discount'], 2, '.', ''), number_format($b['total'], 2, '.', '')];
    }

    /**
     * A BORRADOR posterior es una preparación y no reemplaza la última
     * propuesta aceptada que sigue vigente para los reportes financieros.
     */
    private function currentBudgetVersion(Collection $versions): ?PresupuestoVersion
    {
        $latest = $versions->sortByDesc('numero_version')->first();
        if ($latest?->estado === 'BORRADOR') {
            return $versions->where('estado', 'ACEPTADO')->sortByDesc('numero_version')->first() ?? $latest;
        }
        return $latest;
    }

    private function productionData(Request $request, bool $paginate = true): array
    {
        [$from, $to] = $this->dateRange($request, 'desde', 'hasta');
        $doctor = (string) $request->input('odontologo', '');
        $procedure = trim((string) $request->input('procedimiento', ''));
        $sessions = TratamientoSesion::with(['plan.paciente', 'odontologo', 'detalles.procedimiento.detalleOrigen'])
            ->where('estado', 'FINALIZADA')
            ->whereDate('fecha_sesion', '>=', $from->toDateString())->whereDate('fecha_sesion', '<=', $to->toDateString())
            ->when($doctor !== '' && \App\Models\Odontologia\Odontologo::whereKey((int) $doctor)->exists(), fn ($q) => $q->where('id_odontologo', (int) $doctor))
            ->when($procedure !== '', fn ($q) => $q->whereHas('detalles.procedimiento', fn ($p) => $p->where('nombre_servicio', 'like', "%{$procedure}%")->orWhere('codigo_servicio', 'like', "%{$procedure}%")))
            ->orderBy('fecha_sesion')->orderBy('id_sesion')->get();
        $rows = $sessions->flatMap(function (TratamientoSesion $session) {
            return $session->detalles->filter(fn ($detail) => (int) $detail->cantidad_realizada > 0)->map(function ($detail) use ($session) {
                $procedure = $detail->procedimiento;
                $origin = $procedure?->detalleOrigen;
                $netUnit = $origin && (int) $origin->cantidad > 0 ? ((float) $origin->subtotal / (int) $origin->cantidad) : null;
                return (object) ['session' => $session, 'detail' => $detail, 'procedure' => $procedure, 'value' => $netUnit === null ? null : $netUnit * (int) $detail->cantidad_realizada];
            });
        })->values();
        $summary = ['quantity' => (int) $rows->sum(fn ($row) => (int) $row->detail->cantidad_realizada), 'known_value' => round((float) $rows->filter(fn ($row) => $row->value !== null)->sum('value'), 2), 'known_value_rows' => $rows->filter(fn ($row) => $row->value !== null)->count(), 'by_doctor' => $rows->groupBy(fn ($row) => $row->session->odontologo?->nombre_completo ?? 'Sin ejecutor')->map(fn ($items) => ['quantity' => (int) $items->sum(fn ($row) => (int) $row->detail->cantidad_realizada), 'value' => round((float) $items->filter(fn ($row) => $row->value !== null)->sum('value'), 2)])->sortKeys()];
        return ['rows' => $paginate ? $this->paginateCollection($rows, $request) : null, 'allRows' => $rows, 'summary' => $summary, 'filters' => ['from' => $from, 'to' => $to, 'doctor' => $doctor, 'procedure' => $procedure], 'doctors' => \App\Models\Odontologia\Odontologo::where('estado', 'ACTIVO')->orderBy('apellidos')->orderBy('nombres')->get()];
    }

    private function productionRow(object $row): array
    {
        $session = $row->session; $procedure = $row->procedure; $patient = $session->plan?->paciente;
        return [optional($session->fecha_sesion)->timezone('America/Lima')->format('d/m/Y H:i'), trim(($patient?->nombres ?? '').' '.($patient?->apellidos ?? '')), $patient?->tipo_documento, $patient?->numero_documento, $session->plan?->numero, trim(($procedure?->codigo_servicio ? $procedure->codigo_servicio.' · ' : '').($procedure?->nombre_servicio ?? 'Procedimiento')), $row->detail->cantidad_realizada, $session->odontologo?->nombre_completo, $row->value === null ? null : number_format($row->value, 2, '.', '')];
    }

    private function clinicalReportData(PlanTratamiento $plan): array
    {
        $procedures = $plan->procedimientosClinicos;
        $included = $procedures->count();
        $finished = $procedures->where('estado', 'REALIZADO')->count();
        $pending = max(0, $included - $finished);
        $required = (int) $procedures->sum(fn ($procedure) => max(1, (int) $procedure->cantidad_requerida));
        $realized = min($required, (int) $procedures->sum(fn ($procedure) => (int) $procedure->cantidad_realizada));
        return ['included' => $included, 'finished' => $finished, 'pending' => $pending, 'percent' => $required > 0 ? min(100, (int) round(($realized / $required) * 100)) : 0, 'required' => $required, 'realized' => $realized];
    }

    private function treatmentRow(PlanTratamiento $plan): array
    {
        $clinical = $plan->clinical_report;
        return [
            trim(($plan->paciente?->nombres ?? '').' '.($plan->paciente?->apellidos ?? '')),
            $plan->paciente?->tipo_documento,
            $plan->paciente?->numero_documento,
            $plan->numero,
            optional($plan->created_at)->timezone('America/Lima')->format('d/m/Y H:i'),
            $plan->odontologo?->nombre_completo,
            $clinical['included'] > 0 ? $clinical['included'] : 'Sin procedimientos',
            $clinical['finished'], $clinical['pending'], $clinical['percent'].'%', $plan->estado_clinico,
        ];
    }

    private function dateRange(Request $request, string $fromKey, string $toKey): array
    {
        $today = Carbon::now('America/Lima')->toDateString();
        $from = Carbon::parse($request->input($fromKey, $today), 'America/Lima');
        $to = Carbon::parse($request->input($toKey, $today), 'America/Lima');
        if ($from->gt($to)) [$from, $to] = [$to, $from];
        return [$from, $to];
    }

    private function paymentsQuery(array $filters)
    {
        return Pago::query()
            ->whereBetween('fecha_pago', [$filters['from']->copy()->startOfDay(), $filters['to']->copy()->endOfDay()])
            ->when($filters['method'] !== '', fn ($q) => $q->where('id_metodo_pago', $filters['method']))
            ->when($filters['user'] !== '', fn ($q) => $q->where('id_usuario_creacion', $filters['user']))
            ->when($filters['status'] !== '', fn ($q) => $q->where('estado', $filters['status']))
            ->when($filters['term'] !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('numero_constancia', 'like', "%{$filters['term']}%")
                ->orWhereHas('paciente', fn ($p) => $p->where('nombres', 'like', "%{$filters['term']}%")->orWhere('apellidos', 'like', "%{$filters['term']}%")->orWhere('numero_documento', 'like', "%{$filters['term']}%"))
                ->orWhereHas('plan', fn ($p) => $p->where('numero', 'like', "%{$filters['term']}%"))));
    }

    private function paymentsSummary($query): array
    {
        $valid = (clone $query)->where('estado', 'PAGADO');
        $cancelled = (clone $query)->where('estado', 'ANULADO');
        $methods = (clone $valid)->select('id_metodo_pago', DB::raw('COUNT(*) AS cantidad'), DB::raw('SUM(monto) AS total'))->groupBy('id_metodo_pago')->get()->map(function ($row) {
            $row->nombre = MetodoPago::find($row->id_metodo_pago)?->nombre ?? 'Sin método';
            return $row;
        });
        return ['valid_total_cents' => (int) round(((float) $valid->sum('monto')) * 100), 'valid_count' => $valid->count(), 'cancelled_total_cents' => (int) round(((float) $cancelled->sum('monto')) * 100), 'cancelled_count' => $cancelled->count(), 'methods' => $methods];
    }

    private function filters(Request $request): array
    {
        $today = Carbon::now('America/Lima')->toDateString();
        $from = Carbon::parse($request->input('desde', $today), 'America/Lima');
        $to = Carbon::parse($request->input('hasta', $today), 'America/Lima');
        if ($from->gt($to)) [$from, $to] = [$to, $from];
        $user = (string) $request->input('usuario', '');
        if ($user !== '' && ! Usuario::whereKey((int) $user)->exists()) $user = '';
        return ['from' => $from, 'to' => $to, 'method' => (string) $request->input('metodo', ''), 'user' => $user, 'status' => in_array($request->input('estado'), ['PAGADO', 'ANULADO'], true) ? $request->input('estado') : '', 'term' => trim((string) $request->input('buscar', ''))];
    }

    private function clinicUsers(): Collection { return Usuario::where('estado', 'ACTIVO')->orderBy('apellidos')->orderBy('nombres')->get(); }

    private function paginateCollection(Collection $items, Request $request): LengthAwarePaginator
    {
        $perPage = 25; $page = LengthAwarePaginator::resolveCurrentPage('page');
        return new LengthAwarePaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page, ['path' => $request->url(), 'query' => $request->query()]);
    }

    private function lastPaymentDate(Collection $payments): ?string
    {
        $date = $payments->sortByDesc('fecha_pago')->first()?->fecha_pago;

        return $date ? $date->timezone('America/Lima')->format('d/m/Y H:i') : null;
    }

    private function documentData(array $data): array
    {
        $empresa = Schema::hasTable('odo_configuracion_empresa') ? ConfiguracionEmpresa::first() : null;
        $logoUrl = $empresa?->logo_path && Storage::disk('public')->exists($empresa->logo_path) ? route('odontologia.empresa.logo', ['path' => $empresa->logo_path]) : null;
        return $data + compact('empresa', 'logoUrl');
    }

    private function authorizeReport(string $action): void { abort_unless(auth()->user()?->hasPermission('reportes', $action), 403, 'No tienes permiso para consultar este reporte.'); }

    private function csvDownload(string $filename, callable $writer)
    {
        return response()->streamDownload(function () use ($writer): void { $handle = fopen('php://output', 'wb'); fwrite($handle, "\xEF\xBB\xBF"); $writer($handle); fclose($handle); }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvRow($handle, array $values): void { fputcsv($handle, array_map(fn ($value) => $this->csvSafe($value), $values), ';'); }
    private function csvSafe(mixed $value): string { $value = (string) ($value ?? ''); return preg_match('/^[=+\\-@]/', $value) ? "'".$value : $value; }
}
