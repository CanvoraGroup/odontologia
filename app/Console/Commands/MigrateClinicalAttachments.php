<?php

namespace App\Console\Commands;

use App\Models\Odontologia\HistoriaAdjunto;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateClinicalAttachments extends Command
{
    protected $signature = 'odontologia:migrate-clinical-attachments {--dry-run : Solo inspecciona e inventaría sin modificar referencias ni archivos}';
    protected $description = 'Migra adjuntos clínicos históricos a almacenamiento privado por empresa con verificación SHA-256';

    public function handle(): int
    {
        if (! Schema::hasTable('odo_historia_adjuntos') || ! Schema::hasColumn('odo_historia_adjuntos', 'id_clinica')) {
            $this->error('La estructura de adjuntos no tiene pertenencia por empresa. Ejecuta primero las migraciones específicas.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $inventory = [];
        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        HistoriaAdjunto::withoutGlobalScope('clinic')->orderBy('id_adjunto')->chunkById(50, function ($attachments) use (&$inventory, &$migrated, &$skipped, &$failed, $dryRun): void {
            foreach ($attachments as $attachment) {
                $oldRelative = (string) $attachment->archivo_path;
                $oldPath = $this->resolvePublicPath($oldRelative);
                $entry = [
                    'id_adjunto' => (int) $attachment->id_adjunto,
                    'id_clinica' => (int) $attachment->id_clinica,
                    'ruta_original' => $oldRelative,
                    'ruta_privada' => null,
                    'tamano' => null,
                    'sha256' => null,
                    'estado' => null,
                ];

                $privateExisting = storage_path('app/private/'.ltrim($oldRelative, '/'));
                if (str_starts_with($oldRelative, 'empresas/') && is_file($privateExisting)) {
                    $entry['ruta_privada'] = $oldRelative;
                    $entry['tamano'] = filesize($privateExisting);
                    $entry['sha256'] = hash_file('sha256', $privateExisting);
                    $entry['estado'] = ($attachment->archivo_hash_sha256 && hash_file('sha256', $privateExisting) === $attachment->archivo_hash_sha256)
                        ? 'YA_PRIVADO_VERIFICADO'
                        : 'PRIVADO_SIN_HASH_COINCIDENTE';
                    $inventory[] = $entry;
                    if ($entry['estado'] === 'YA_PRIVADO_VERIFICADO') $skipped++;
                    else $failed++;
                    continue;
                }

                if (! $oldPath || ! is_file($oldPath)) {
                    $entry['estado'] = 'NO_ENCONTRADO';
                    $inventory[] = $entry;
                    $failed++;
                    $this->warn("Adjunto {$attachment->id_adjunto}: archivo no encontrado; no se modificó.");
                    continue;
                }

                $hash = hash_file('sha256', $oldPath);
                $size = filesize($oldPath);
                $newRelative = 'empresas/'.(int) $attachment->id_clinica.'/historias-clinicas/'.basename($oldRelative);
                $newPath = storage_path('app/private/'.$newRelative);
                $entry['ruta_privada'] = $newRelative;
                $entry['tamano'] = $size;
                $entry['sha256'] = $hash;

                if ($dryRun) {
                    $entry['estado'] = 'PREVISTO';
                    $inventory[] = $entry;
                    $skipped++;
                    continue;
                }

                try {
                    if (! is_dir(dirname($newPath))) mkdir(dirname($newPath), 0775, true);
                    if (! copy($oldPath, $newPath) || ! is_file($newPath) || filesize($newPath) !== $size || hash_file('sha256', $newPath) !== $hash) {
                        throw new \RuntimeException('La copia no superó la verificación de tamaño/hash.');
                    }

                    DB::transaction(function () use ($attachment, $oldRelative, $newRelative, $size, $hash): void {
                        TenantContext::run((int) $attachment->id_clinica, null, function () use ($attachment, $oldRelative, $newRelative, $size, $hash): void {
                            $attachment->update([
                                'archivo_path' => $newRelative,
                                'ruta_publica_anterior' => $oldRelative,
                                'tamano_bytes' => $size,
                                'archivo_hash_sha256' => $hash,
                            ]);
                        });
                    });

                    if (! unlink($oldPath)) throw new \RuntimeException('No se pudo retirar la copia pública después de actualizar la referencia.');
                    $entry['estado'] = 'MIGRADO_Y_VERIFICADO';
                    $migrated++;
                } catch (\Throwable $e) {
                    if (is_file($newPath) && (! is_file($oldPath) || hash_file('sha256', $newPath) === $hash)) @unlink($newPath);
                    $entry['estado'] = 'ERROR: '.$e->getMessage();
                    $failed++;
                    $this->error("Adjunto {$attachment->id_adjunto}: {$e->getMessage()}");
                }
                $inventory[] = $entry;
            }
        }, 'id_adjunto');

        $filename = 'clinical_attachment_inventory_'.now()->format('Ymd_His').'.json';
        $path = storage_path('app/backups/'.$filename);
        if (! is_dir(dirname($path))) mkdir(dirname($path), 0775, true);
        file_put_contents($path, json_encode($inventory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Inventario: {$path}");
        $this->info("Migrados y verificados: {$migrated}; previstos/omitidos: {$skipped}; fallidos: {$failed}.");
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolvePublicPath(string $relative): ?string
    {
        $candidate = storage_path('app/public/'.ltrim($relative, '/'));
        return is_file($candidate) ? $candidate : null;
    }
}
