<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\ConfiguracionEmpresa;
use App\Models\Odontologia\Clinica;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

class ConfiguracionEmpresaController extends Controller
{
    public function edit()
    {
        $this->authorizeConfig('ver');
        $clinicId = Schema::hasColumn('odo_configuracion_empresa', 'id_clinica') ? TenantContext::requireId() : null;
        return view('odontologia.configuracion.empresa', ['configuracion' => ConfiguracionEmpresa::first() ?? new ConfiguracionEmpresa(['id_clinica' => $clinicId, 'ticket_width' => 80])]);
    }

    public function update(Request $request)
    {
        $this->authorizeConfig('editar');
        $data = $request->validate([
            'razon_social' => ['required','string','max:180'], 'nombre_comercial' => ['nullable','string','max:180'],
            'ruc' => ['nullable','regex:/^\d{11}$/'], 'direccion' => ['nullable','string','max:250'],
            'telefono' => ['nullable','string','max:40'], 'correo' => ['nullable','email','max:150'],
            'ticket_footer' => ['nullable','string','max:250'], 'ticket_width' => ['required','in:58,80'],
            'logo' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
        ], [
            'razon_social.required' => 'La razón social es obligatoria.', 'ruc.regex' => 'El RUC debe tener 11 dígitos.',
            'correo.email' => 'Ingresa un correo válido.', 'ticket_width.in' => 'El ancho debe ser 58 mm u 80 mm.',
            'logo.image' => 'El logo debe ser una imagen válida.', 'logo.max' => 'El logo no puede superar 2 MB.',
        ]);
        $clinicId = Schema::hasColumn('odo_configuracion_empresa', 'id_clinica') ? TenantContext::requireId() : null;
        $config = ConfiguracionEmpresa::first() ?? new ConfiguracionEmpresa(['id_clinica' => $clinicId]);
        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('empresas/'.TenantContext::requireId().'/logo', 'public');
            Clinica::whereKey(TenantContext::requireId())->update(['logo_path' => $data['logo_path']]);
        }
        unset($data['logo']);
        $config->fill($data)->save();
        Clinica::whereKey(TenantContext::requireId())->update([
            'razon_social' => $config->razon_social,
            'nombre_comercial' => $config->nombre_comercial,
            'ruc' => $config->ruc,
            'direccion' => $config->direccion,
            'telefono' => $config->telefono,
            'correo' => $config->correo,
            'logo_path' => $config->logo_path,
            'ticket_footer' => $config->ticket_footer,
            'ticket_width' => $config->ticket_width,
        ]);
        return back()->with('success', 'Configuración de empresa guardada. Las constancias anteriores conservan su propia información.');
    }

    private function authorizeConfig(string $action): void
    {
        abort_unless(auth()->user()?->hasPermission('configuracion', $action), 403, 'No tienes permiso para configurar la empresa.');
    }
}
