<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function getNewToken()
    {
        $user = User::find(Auth::id());
        
        if (!$user) {
            Log::warning('Token request without valid user', ['ip' => request()->ip()]);
            throw new \Exception('Usuario no válido');
        }

        try {
            $response = Http::timeout(10) // ✅ Timeout de 10 segundos
                ->post($this->getApiUrl() . '/generate-token', [
                    'cuit' => $user->username,
                    'email' => $user->email
                ]);

            // ✅ Validar respuesta completa
            if (!$response->successful()) {
                Log::error('API token generation failed', [
                    'status' => $response->status(),
                    'user_id' => $user->id
                ]);
                throw new \Exception('Error de comunicación con el servidor');
            }

            $data = $response->json();
            
            // ✅ Validar que existe el token y no está vacío
            if (!isset($data['token']) || empty($data['token'])) {
                Log::error('API returned invalid token format', [
                    'user_id' => $user->id,
                    'response_keys' => array_keys($data ?? [])
                ]);
                throw new \Exception('Respuesta inválida del servidor');
            }

            return $data['token'];

        } catch (\Exception $e) {
            Log::error('Token generation exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);
            
            // ✅ No exponer detalles internos
            throw new \Exception('Error temporal del sistema. Intente nuevamente.');
        }
    }

    // ✅ Método centralizado para URL de API
    private function getApiUrl(): string
    {
        $url = env('PLATAFORMA_API_URL');
        
        if (empty($url)) {
            Log::critical('PLATAFORMA_API_URL not configured');
            throw new \Exception('Configuración del sistema incompleta');
        }

        // Validar que sea una URL válida
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Log::critical('Invalid PLATAFORMA_API_URL format', ['url' => $url]);
            throw new \Exception('Configuración del sistema inválida');
        }

        return rtrim($url, '/'); // Quitar slash final
    }
}