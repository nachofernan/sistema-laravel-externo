<?php

namespace App\Http\Controllers;

use App\Mail\RegistroProveedor;
use App\Rules\Turnstile;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SolicitudRegistroController extends Controller
{
    public const DESTINATARIO = 'infoproveedores@buenosairesenergia.com.ar';

    public function store(Request $request)
    {
        // Honeypot: un humano no ve este campo. Fingimos éxito para no dar pistas.
        if ($request->filled('verification_code')) {
            Log::info('Solicitud de registro descartada', ['motivo' => 'honeypot', 'ip' => $request->ip()]);

            return redirect()->route('gracias');
        }

        if (is_string($request->input('cuit'))) {
            $request->merge([
                'cuit' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $request->input('cuit'))),
            ]);
        }

        $validados = $request->validate([
            'nombre_y_apellido' => ['required', 'string', 'max:150'],
            'tipo' => ['required', 'string', 'max:20'],
            'numero' => ['required', 'string', 'max:20'],
            'correo_personal' => ['required', 'email', 'max:150'],
            'razonsocial' => ['required', 'string', 'max:200'],
            'cuit' => ['required', 'alpha_num', 'between:6,20'],
            'correo_institucional' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'firma' => ['accepted'],
            'cf-turnstile-response' => ['required', 'string', new Turnstile],
        ], [
            'required' => 'Este campo es obligatorio.',
            'string' => 'El valor ingresado no es válido.',
            'max' => 'Máximo :max caracteres.',
            'email' => 'Ingresá un correo electrónico válido.',
            'cuit.alpha_num' => 'El CUIT/CUIL solo puede contener letras y números.',
            'cuit.between' => 'El CUIT/CUIL debe tener entre 6 y 20 caracteres.',
            'firma.accepted' => 'Tenés que activar la casilla para validar el formulario.',
            'cf-turnstile-response.required' => 'Completá la verificación anti-bot antes de enviar.',
        ]);

        $datos = Arr::except($validados, ['firma', 'cf-turnstile-response']);

        // Solo a la casilla interna: nunca a una dirección que escribe el visitante.
        Mail::to(self::DESTINATARIO)->send(new RegistroProveedor($datos));

        return redirect()->route('gracias');
    }
}
