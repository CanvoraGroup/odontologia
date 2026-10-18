<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyMultiCompanyIsolation extends Command
{
    protected $signature = 'odontologia:verify-multi-company
        {--clinic= : First clinic id}
        {--other-clinic= : Second clinic id}
        {--json : Emit machine-readable output}';

    protected $description = 'Verifica pertenencia y trazabilidad de tablas multiempresa en la conexión actual.';

    private array $tables = [
        'odo_pacientes', 'odo_odontologos', 'odo_consultorios', 'odo_categorias_servicios', 'odo_servicios',
        'odo_citas', 'odo_atenciones', 'odo_historias_clinicas', 'odo_historia_adjuntos', 'odo_odontogramas',
        'odo_odontograma_detalles', 'odo_planes_tratamiento', 'odo_presupuesto_versiones', 'odo_presupuesto_detalles',
        'odo_plan_eventos', 'odo_plan_procedimientos', 'odo_tratamiento_sesiones', 'odo_tratamiento_sesion_detalles',
        'odo_pagos', 'odo_pago_receptores', 'odo_pago_detalles', 'odo_comprobantes', 'odo_configuracion_empresa',
        'odo_correlativos', 'odo_auditoria',
    ];

    public function handle(): int
    {
        $clinic = (int) $this->option('clinic');
        $other = (int) $this->option('other-clinic');
        if ($clinic <= 0 || $other <= 0 || $clinic === $other) {
            $this->error('Indica dos empresas distintas con --clinic y --other-clinic.');
            return self::FAILURE;
        }

        $result = ['connection' => config('database.default'), 'clinics' => [$clinic, $other], 'tables' => [], 'passed' => true];
        foreach ($this->tables as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                $result['tables'][$table] = ['status' => 'missing'];
                continue;
            }
            if (! DB::getSchemaBuilder()->hasColumn($table, 'id_clinica')) {
                $result['tables'][$table] = ['status' => 'global'];
                continue;
            }
            $counts = DB::table($table)->select('id_clinica', DB::raw('COUNT(*) as total'))
                ->whereIn('id_clinica', [$clinic, $other])->groupBy('id_clinica')->pluck('total', 'id_clinica')->all();
            $result['tables'][$table] = [
                'status' => 'tenantized',
                'clinic' => (int) ($counts[$clinic] ?? 0),
                'other_clinic' => (int) ($counts[$other] ?? 0),
                'traceability' => DB::getSchemaBuilder()->hasColumn($table, 'id_usuario_creacion'),
            ];
        }

        $result['cross_company_relation_rejected'] = $this->crossCompanyRelationIsRejected($clinic, $other);
        $result['passed'] = $result['cross_company_relation_rejected'];
        foreach ($result['tables'] as $row) $result['passed'] = $result['passed'] && ($row['status'] !== 'missing');

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(['Tabla', 'Estado', 'Empresa 1', 'Empresa 2', 'Trazabilidad'], collect($result['tables'])->map(fn ($row, $table) => [$table, $row['status'], $row['clinic'] ?? '-', $row['other_clinic'] ?? '-', !empty($row['traceability']) ? 'sí' : 'no'])->all());
            $this->line('Relación cruzada rechazada: '.($result['cross_company_relation_rejected'] ? 'sí' : 'no'));
            $this->line('Resultado: '.($result['passed'] ? 'APROBADO' : 'FALLIDO'));
        }

        return $result['passed'] ? self::SUCCESS : self::FAILURE;
    }

    private function crossCompanyRelationIsRejected(int $clinic, int $other): bool
    {
        if (! DB::getSchemaBuilder()->hasTable('odo_pacientes') || ! DB::getSchemaBuilder()->hasTable('odo_citas')) return false;
        $patient = DB::table('odo_pacientes')->where('id_clinica', $other)->value('id_paciente');
        if (!$patient || ! DB::getSchemaBuilder()->hasColumn('odo_citas', 'id_paciente')) return true;
        return ! DB::table('odo_citas')->where('id_clinica', $clinic)->where('id_paciente', $patient)->exists();
    }
}
