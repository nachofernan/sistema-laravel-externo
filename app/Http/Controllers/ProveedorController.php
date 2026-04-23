<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\ProveedorApiService;

class ProveedorController extends Controller
{
    protected $authController;

    public function __construct(AuthController $authController)
    {
        $this->authController = $authController;
    }

    /**
     * Dashboard principal del proveedor - AHORA CON API
     */
    public function dashboard()
    {
        try {
            $user = Auth::user();
            $token = session('jwt_token');
            $api = new ProveedorApiService($token, $user->username);
            $proveedor = $api->getProveedor();
    
            if (!$proveedor) {
                Log::error('Dashboard: No se pudo obtener datos del proveedor', [
                    'user_id' => $user->id,
                    'cuit' => $user->username,
                    'jwt_token' => $token,
                    'session_id' => session()->getId(),
                    'user' => $user,
                ]);

                // ✅ Cambio clave: Especificar el guard 'web'
                Auth::guard('web')->logout();
                session()->invalidate();
                session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Error al cargar datos del proveedor.')->withCookie(cookie()->forget('laravel_session'));
            }

            $tipos_documentos = $api->getTiposDocumentos()['tipos_documentos'] ?? [];
    
            return view('dashboard', compact('proveedor', 'tipos_documentos'));
        } catch (\Exception $e) {
            Log::error('Dashboard exception', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'session_id' => session()->getId(),
            ]);

            // ✅ Cambio clave: Especificar el guard 'web'
            Auth::guard('web')->logout();
            session()->invalidate();
            session()->regenerateToken();
            
            return redirect()->route('login')
                ->with('error', 'Error temporal del sistema. Intente nuevamente.')->withCookie(cookie()->forget('laravel_session'));
        }
    }

    /**
     * ✅ Convertir arrays a objetos de manera recursiva manteniendo estructura
     */
    private function arrayToObjectRecursive($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        $object = new \stdClass();
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                // Si es un array indexado (lista), mantenerlo como colección
                if (array_keys($value) === range(0, count($value) - 1)) {
                    $object->$key = collect($value)->map(function($item) {
                        return $this->arrayToObjectRecursive($item);
                    });
                } else {
                    // Si es un array asociativo, convertir a objeto
                    $object->$key = $this->arrayToObjectRecursive($value);
                }
            } else {
                $object->$key = $value;
            }
        }
        
        return $object;
    }

    /**
     * ✅ Método centralizado para URL de API (mismo patrón que AuthController)
     */
    private function getApiUrl(): string
    {
        $url = env('PLATAFORMA_API_URL');
        
        if (empty($url)) {
            Log::critical('PLATAFORMA_API_URL not configured');
            throw new \Exception('Configuración del sistema incompleta');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Log::critical('Invalid PLATAFORMA_API_URL format', ['url' => $url]);
            throw new \Exception('Configuración del sistema inválida');
        }

        return rtrim($url, '/');
    }
}