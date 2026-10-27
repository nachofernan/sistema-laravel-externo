<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifica el token de Cloudflare Turnstile contra la API de Cloudflare.
 * Si Cloudflare no responde, rechaza el envío (decisión: fail-closed).
 */
class Turnstile implements ValidationRule
{
    public const URL_VERIFICACION = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $respuesta = Http::asForm()->timeout(5)->post(self::URL_VERIFICACION, [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);
        } catch (ConnectionException $e) {
            Log::warning('Turnstile: Cloudflare no respondió', ['ip' => request()->ip()]);
            $fail('No pudimos verificar el formulario. Intentá de nuevo en unos minutos.');

            return;
        }

        if (! $respuesta->successful()) {
            Log::warning('Turnstile: respuesta inesperada de Cloudflare', [
                'status' => $respuesta->status(),
                'ip' => request()->ip(),
            ]);
            $fail('No pudimos verificar el formulario. Intentá de nuevo en unos minutos.');

            return;
        }

        if ($respuesta->json('success') !== true) {
            Log::info('Turnstile: token rechazado', [
                'error_codes' => $respuesta->json('error-codes', []),
                'ip' => request()->ip(),
            ]);
            $fail('La verificación anti-bot no fue válida. Recargá la página e intentá de nuevo.');
        }
    }
}
