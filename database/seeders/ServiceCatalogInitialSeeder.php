<?php

namespace Database\Seeders;

use App\Models\Odontologia\CategoriaServicio;
use App\Models\Odontologia\Servicio;
use Illuminate\Database\Seeder;

class ServiceCatalogInitialSeeder extends Seeder
{
    private array $categories = [
        'CAT-001' => 'Consulta y diagnóstico',
        'CAT-002' => 'Prevención e higiene',
        'CAT-003' => 'Restauración dental',
        'CAT-004' => 'Endodoncia',
        'CAT-005' => 'Periodoncia',
        'CAT-006' => 'Cirugía oral',
        'CAT-007' => 'Ortodoncia',
        'CAT-008' => 'Rehabilitación oral y prótesis',
        'CAT-009' => 'Odontopediatría',
        'CAT-010' => 'Estética dental',
    ];

    private array $serviceMap = [
        'ODO-CON-001' => 'CAT-001',
        'ODO-ORT-001' => 'CAT-007',
        'ODO-CTR-001' => 'CAT-001',
        'ODO-CUR-001' => 'CAT-003',
        'ODO-END-001' => 'CAT-004',
        'ODO-EXT-001' => 'CAT-006',
        'ODO-LIM-001' => 'CAT-002',
    ];

    public function run(): void
    {
        $categories = [];
        $created = 0;
        $reused = 0;

        foreach ($this->categories as $code => $name) {
            $byCode = CategoriaServicio::where('codigo', $code)->first();
            $byName = CategoriaServicio::where('nombre', $name)->first();

            if ($byCode && $byName && $byCode->id_categoria !== $byName->id_categoria) {
                $this->command?->warn("Conflicto {$code}: el código y el nombre pertenecen a categorías distintas. No se modificó ninguna.");
                continue;
            }

            $category = $byCode ?: $byName;
            if ($category) {
                $reused++;
                if ($category->codigo !== $code) {
                    $this->command?->line("Reutilizada por nombre: {$name} (conserva su código actual).");
                }
            } else {
                $category = CategoriaServicio::create([
                    'codigo' => $code,
                    'nombre' => $name,
                    'estado' => 'ACTIVO',
                ]);
                $created++;
                $this->command?->line("Creada: {$code} - {$name}");
            }

            $categories[$code] = $category;
        }

        $assigned = 0;
        $preserved = 0;
        $missing = 0;
        foreach ($this->serviceMap as $serviceCode => $categoryCode) {
            $service = Servicio::where('codigo', $serviceCode)->first();
            $category = $categories[$categoryCode] ?? null;

            if (! $service) {
                $missing++;
                $this->command?->warn("Servicio no encontrado: {$serviceCode}. No se creó reemplazo.");
                continue;
            }

            if (! $category) {
                $this->command?->warn("No se asignó {$serviceCode}: la categoría {$categoryCode} no está disponible.");
                continue;
            }

            if ($service->id_categoria !== null) {
                $preserved++;
                $this->command?->line("Servicio {$serviceCode}: conserva su categoría actual.");
                continue;
            }

            $service->update(['id_categoria' => $category->id_categoria]);
            $assigned++;
            $this->command?->line("Asignado: {$serviceCode} -> {$category->nombre}");
        }

        $this->command?->info("Categorías creadas: {$created}; reutilizadas: {$reused}; servicios asignados: {$assigned}; categorías de servicios conservadas: {$preserved}; servicios no encontrados: {$missing}.");
    }
}
