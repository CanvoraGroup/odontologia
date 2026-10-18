<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class EmpresaLogoController extends Controller
{
    public function show(Request $request, string $path): Response
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        abort_unless(preg_match('#^empresas/([0-9]+)/logo/[^/]+$#', $path, $matches) === 1, 404);
        abort_unless((int) $matches[1] === TenantContext::requireId(), 404);
        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), ['Cache-Control' => 'private, max-age=300']);
    }
}
