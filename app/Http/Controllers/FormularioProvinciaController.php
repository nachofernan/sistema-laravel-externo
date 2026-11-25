<?php

namespace App\Http\Controllers;

use App\Models\RegistroFormularioProvincia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Formulario de un día para la 2° Ronda de Negocios de Energía y Minería (QR en el stand).
 * Se guarda local como JSON: no es dato de negocio de la API interna. Ningún campo es
 * obligatorio. Se apaga con FORMULARIO_PROVINCIA_ACTIVO=false.
 */
class FormularioProvinciaController extends Controller
{
    private const CLAVE_SESION = 'formulario_provincia_id';

    public function show(): View
    {
        $this->verificarActivo();

        return view('formulario-provincia');
    }

    /** Paso 1: crea el registro y recuerda su id en la sesión para el Paso 2. */
    public function store(Request $request): JsonResponse
    {
        $this->verificarActivo();

        $contacto = $request->validate([
            'razon' => ['nullable', 'string', 'max:200'],
            'cuit' => ['nullable', 'string', 'max:50'],
            'contacto' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'string', 'max:150'],
            'tel' => ['nullable', 'string', 'max:50'],
            'empresa' => ['nullable', 'in:Ambas,BAESA,BAGSA'],
            'rubros' => ['nullable', 'array', 'max:100'],
            'rubros.*' => ['string', 'max:150'],
            'otro' => ['nullable', 'string', 'max:500'],
        ]);

        $registro = RegistroFormularioProvincia::create([
            'datos' => ['contacto' => $contacto],
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        $request->session()->put(self::CLAVE_SESION, $registro->id);

        return response()->json(['ok' => true]);
    }

    /** Paso 2 (opcional): completa el perfil del registro creado en esta misma sesión. */
    public function update(Request $request): JsonResponse
    {
        $this->verificarActivo();

        // El id sale solo de la sesión: nunca del request, para que nadie pise un registro ajeno.
        $registro = RegistroFormularioProvincia::find($request->session()->get(self::CLAVE_SESION));

        if (! $registro) {
            return response()->json(['ok' => false], 409);
        }

        $perfil = $request->validate([
            'dir' => ['nullable', 'string', 'max:200'],
            'loc' => ['nullable', 'string', 'max:100'],
            'prov' => ['nullable', 'string', 'max:50'],
            'iva' => ['nullable', 'string', 'max:50'],
            'tam' => ['nullable', 'string', 'max:50'],
            'emp' => ['nullable', 'string', 'max:50'],
            'web' => ['nullable', 'string', 'max:200'],
            'desc' => ['nullable', 'string', 'max:3000'],
            'clientes' => ['nullable', 'string', 'max:500'],
            'a_reg' => ['nullable', 'boolean'],
            'a_sector' => ['nullable', 'boolean'],
            'a_iso' => ['nullable', 'boolean'],
        ]);

        $registro->update([
            'datos' => array_merge($registro->datos, ['perfil' => $perfil]),
            'perfil_completado_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    /** "Registrar otra empresa" (tablet del stand): el próximo envío crea una fila nueva. */
    public function reiniciar(Request $request): JsonResponse
    {
        $this->verificarActivo();

        $request->session()->forget(self::CLAVE_SESION);

        return response()->json(['ok' => true]);
    }

    private function verificarActivo(): void
    {
        abort_unless(config('services.formulario_provincia.activo'), 404);
    }
}
