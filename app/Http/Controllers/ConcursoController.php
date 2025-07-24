<?php

namespace App\Http\Controllers;

use App\Models\Concursos\Concurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConcursoController extends Controller
{
    protected $authController;

    public function __construct(AuthController $authController)
    {
        $this->authController = $authController;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $token = $this->authController->getNewToken();
            $user = Auth::user();
            
            // ✅ Obtener datos del proveedor desde API interna
            $proveedorResponse = Http::timeout(15)
                ->withToken($token)
                ->get($this->getApiUrl() . '/provider-data/' . $user->username);
            
            if (!$proveedorResponse->successful()) {
                Log::error('Failed to get provider data', [
                    'user_id' => $user->id,
                    'status' => $proveedorResponse->status()
                ]);
                
                return redirect()->route('dashboard')
                    ->with('error', 'Error al cargar datos del proveedor.');
            }
            
            $proveedorData = $proveedorResponse->json();
            $providerId = $proveedorData['proveedor']['id'];
            
            // ✅ Obtener invitaciones desde API interna
            $invitacionesResponse = Http::timeout(15)
                ->withToken($token)
                ->get($this->getApiUrl() . '/provider-invitations/' . $providerId);
            
            if (!$invitacionesResponse->successful()) {
                Log::error('Failed to get provider invitations', [
                    'user_id' => $user->id,
                    'provider_id' => $providerId,
                    'status' => $invitacionesResponse->status()
                ]);
                
                return redirect()->route('dashboard')
                    ->with('error', 'Error al cargar concursos.');
            }
            
            $invitacionesData = $invitacionesResponse->json();
            
            // ✅ Convertir arrays a objetos para mantener compatibilidad con las vistas
            $invitaciones_activas = collect($invitacionesData['invitaciones_activas'])
                ->map(function($invitacion) {
                    return $this->arrayToObject($invitacion);
                });

            $invitaciones_finalizadas = collect($invitacionesData['invitaciones_finalizadas'])
                ->map(function($invitacion) {
                    return $this->arrayToObject($invitacion);
                });
            
            Log::info('Concursos loaded successfully via API', [
                'user_id' => $user->id,
                'activas_count' => $invitaciones_activas->count(),
                'finalizadas_count' => $invitaciones_finalizadas->count()
            ]);
            
            return view('concursos.index', compact('invitaciones_activas', 'invitaciones_finalizadas'));
            
        } catch (\Exception $e) {
            Log::error('Exception loading concursos via API', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('dashboard')
                ->with('error', 'Error temporal del sistema. Intente nuevamente.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($concursoId)  // ✅ Cambiar de (Concurso $concurso) a ($concursoId)
    {
        try {
            $token = $this->authController->getNewToken();
            $user = Auth::user();
            
            // ✅ Obtener datos completos del concurso desde API interna
            $concursoResponse = Http::timeout(15)
                ->withToken($token)
                ->get($this->getApiUrl() . '/concurso-details/' . $concursoId . '/' . $user->username);  // ✅ Usar $concursoId
            
            if (!$concursoResponse->successful()) {
                Log::error('Failed to get concurso details', [
                    'user_id' => $user->id,
                    'concurso_id' => $concursoId,  // ✅ Usar $concursoId
                    'status' => $concursoResponse->status()
                ]);
                
                return redirect()->route('concursos.index')
                    ->with('error', 'Error al cargar detalles del concurso.');
            }
            
            $concursoData = $concursoResponse->json();
            
            // ✅ Convertir arrays a objetos para mantener compatibilidad con las vistas
            $concurso = $this->arrayToObject($concursoData['concurso']);
            $invitacion = $this->arrayToObject($concursoData['invitacion']);
            $user = $this->arrayToObject($concursoData['user']);
            
            Log::info('Concurso details loaded successfully via API', [
                'user_id' => Auth::id(),
                'concurso_id' => $concurso->id
            ]);
            
            return view('concursos.show', compact('user', 'concurso', 'invitacion'));
            
        } catch (\Exception $e) {
            Log::error('Exception loading concurso details via API', [
                'user_id' => Auth::id(),
                'concurso_id' => $concursoId ?? 'unknown',  // ✅ Usar $concursoId
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('concursos.index')
                ->with('error', 'Error temporal del sistema. Intente nuevamente.');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // No implementado - solo lectura desde API
        abort(404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // No implementado - solo lectura desde API
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Concurso $concurso)
    {
        // No implementado - solo lectura desde API
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Concurso $concurso)
    {
        // No implementado - solo lectura desde API
        abort(404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Concurso $concurso)
    {
        // No implementado - solo lectura desde API
        abort(404);
    }

    // ✅ Métodos auxiliares
    
    /**
     * Convertir array asociativo a objeto para mantener compatibilidad con vistas
     */
    private function arrayToObject($array)
    {
        if (is_array($array)) {
            $obj = new \stdClass();
            foreach ($array as $key => $value) {
                $obj->$key = is_array($value) ? $this->arrayToObject($value) : $value;
            }
            return $obj;
        }
        return $array;
    }

    /**
     * URL centralizada de la API interna
     */
    private function getApiUrl(): string
    {
        $url = env('PLATAFORMA_API_URL');
        
        if (empty($url)) {
            Log::critical('PLATAFORMA_API_URL not configured');
            throw new \Exception('Configuración del sistema incompleta');
        }

        return rtrim($url, '/');
    }
}