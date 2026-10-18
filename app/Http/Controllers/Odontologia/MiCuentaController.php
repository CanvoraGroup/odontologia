<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MiCuentaController extends Controller
{
    public function edit(Request $request)
    {
        return view('odontologia.mi-cuenta.edit', ['usuario' => $request->user()]);
    }

    public function update(Request $request)
    {
        $usuario = $request->user();
        $data = $request->validate([
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['nullable', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (! empty($data['password'])) {
            if (empty($data['current_password']) || ! Hash::check($data['current_password'], $usuario->password)) {
                throw ValidationException::withMessages(['current_password' => 'La contraseña actual no es válida.']);
            }
            $usuario->password = Hash::make($data['password']);
        }

        $usuario->nombres = $data['nombres'];
        $usuario->apellidos = $data['apellidos'] ?? null;
        $usuario->telefono = $data['telefono'] ?? null;
        $usuario->save();

        return back()->with('success', 'Datos de la cuenta actualizados.');
    }
}
