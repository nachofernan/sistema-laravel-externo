<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            $token = $this->authController->getNewToken();
            $user = Auth::user();
            
            // ✅ Obtener datos completos del proveedor desde API interna
            $response = Http::timeout(15)
                ->withToken($token)
                ->get($this->getApiUrl() . '/proveedor-dashboard/' . $user->username);
            
            if (!$response->successful()) {
                Log::error('Failed to get provider dashboard data', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                return redirect()->route('login')
                    ->with('error', 'Error al cargar datos del proveedor.');
            }
            
            $data = $response->json();
            
            // ✅ DEBUG: Log datos recibidos de API
            Log::info('Dashboard API Response', [
                'user_id' => $user->id,
                'response_keys' => array_keys($data),
                'proveedor_data' => $data['proveedor'] ?? 'MISSING',
                'has_cuit' => isset($data['proveedor']['cuit'])
            ]);
            
            // ✅ Convertir a objeto MANTENIENDO todas las propiedades
            $proveedor = $this->arrayToObjectRecursive($data['proveedor']);
            
            // ✅ DEBUG: Log datos finales
            Log::info('Dashboard Final Proveedor', [
                'user_id' => $user->id,
                'proveedor_type' => gettype($proveedor),
                'has_cuit' => isset($proveedor->cuit),
                'cuit_value' => $proveedor->cuit ?? 'MISSING',
                'properties' => get_object_vars($proveedor)
            ]);
            
            return view('dashboard', compact('proveedor'));

        } catch (\Exception $e) {
            Log::error('Dashboard exception', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('login')
                ->with('error', 'Error temporal del sistema. Intente nuevamente.');
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