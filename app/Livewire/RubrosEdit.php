<?php

namespace App\Livewire;

use App\Http\Controllers\AuthController;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class RubrosEdit extends Component
{
    public $proveedor;
    public $subrubros = [];
    public $search = '';
    public $open = false;

    public function mount($proveedor) 
    {
        $this->proveedor = $proveedor;
        
        // ✅ Convertir a objeto si viene como array de API
        if (is_array($this->proveedor)) {
            $this->proveedor = (object) $this->proveedor;
        }
        
        // Si el proveedor viene de API, convertir subrubros a colección para compatibilidad
        $this->subrubros = collect($this->proveedor->subrubros ?? []);
    }

    /**
     * ✅ Obtener CUIT de manera segura
     */
    private function getProveedorCuit()
    {
        if (is_array($this->proveedor)) {
            return $this->proveedor['cuit'] ?? null;
        }
        
        if (is_object($this->proveedor)) {
            return $this->proveedor->cuit ?? null;
        }
        
        return null;
    }

    /**
     * ✅ Crear instancia fresca del AuthController en cada uso
     */
    private function getAuthController()
    {
        return new AuthController();
    }

    public function agregarSubrubro($subrubroId) 
    {
        try {
            $cuit = $this->getProveedorCuit();
            
            if (!$cuit) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Error: CUIT del proveedor no disponible'
                ]);
                return;
            }

            $token = $this->getAuthController()->getNewToken();
            
            // Determinar si agregar o quitar
            $hasSubrubro = $this->subrubros->contains('id', $subrubroId);
            $action = $hasSubrubro ? 'detach' : 'attach';
            
            $response = Http::timeout(10)
                ->withToken($token)
                ->post($this->getApiUrl() . '/proveedor-subrubro', [
                    'cuit' => (string) $cuit,
                    'subrubro_id' => $subrubroId,
                    'action' => $action
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // ✅ Verificar que la respuesta tenga el formato esperado
                if (!is_array($responseData) || !isset($responseData['subrubros'])) {
                    Log::error('API Response format invalid in agregarSubrubro', [
                        'user_id' => Auth::id(),
                        'response_data' => $responseData
                    ]);
                    
                    $this->dispatch('notify', [
                        'type' => 'error',
                        'message' => 'Error: Respuesta de API en formato inválido'
                    ]);
                    return;
                }
                
                $this->subrubros = collect($responseData['subrubros']);
                
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Subrubro actualizado correctamente'
                ]);
            } else {
                Log::error('API Error in agregarSubrubro', [
                    'user_id' => Auth::id(),
                    'subrubro_id' => $subrubroId,
                    'status' => $response->status(),
                    'url' => $this->getApiUrl() . '/proveedor-subrubro'
                ]);
                
                $this->dispatch('notify', [
                    'type' => 'error', 
                    'message' => 'Error al actualizar subrubro'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Exception in agregarSubrubro', [
                'user_id' => Auth::id(),
                'subrubro_id' => $subrubroId,
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error temporal. Intente nuevamente.'
            ]);
        }
    }

    public function marcarTodos($rubroId) 
    {
        try {
            $cuit = $this->getProveedorCuit();
            
            if (!$cuit) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Error: CUIT del proveedor no disponible'
                ]);
                return;
            }

            $token = $this->getAuthController()->getNewToken();
            
            // Obtener rubros actuales para determinar acción
            $rubrosResponse = Http::timeout(10)
                ->withToken($token)
                ->get($this->getApiUrl() . '/rubros', [
                    'search' => ''
                ]);

            if (!$rubrosResponse->successful()) {
                throw new \Exception('Error al obtener rubros desde API');
            }

            $rubrosData = $rubrosResponse->json();
            $rubro = collect($rubrosData['rubros'])->firstWhere('rubro.id', $rubroId);
            
            if (!$rubro) {
                throw new \Exception('Rubro no encontrado');
            }

            // ✅ NORMALIZAR: Convertir subrubros del proveedor a estructura consistente
            $subrubrosProveedorNormalizados = $this->subrubros->map(function($subrubro) {
                // Si está wrapeado en stdClass, extraer el contenido
                if (is_object($subrubro) && property_exists($subrubro, 'stdClass')) {
                    return (array) $subrubro->stdClass;
                }
                // Si ya es array, mantenerlo
                if (is_array($subrubro)) {
                    return $subrubro;
                }
                // Si es objeto directo, convertir a array
                return (array) $subrubro;
            });

            $subrubrosRubro = collect($rubro['subrubros']);
            $subrubrosProveedorIds = $subrubrosProveedorNormalizados->pluck('id');
            $subrubrosRubroIds = $subrubrosRubro->pluck('id');
            
            $marcar = $subrubrosRubroIds->diff($subrubrosProveedorIds)->count() > 0;
            $action = $marcar ? 'attach_all' : 'detach_all';
            
            $response = Http::timeout(10)
                ->withToken($token)
                ->post($this->getApiUrl() . '/proveedor-rubro-completo', [
                    'cuit' => (string) $cuit,
                    'rubro_id' => $rubroId,
                    'action' => $action
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!is_array($data) || !isset($data['subrubros'])) {
                    Log::error('API Response format invalid in marcarTodos', [
                        'user_id' => Auth::id(),
                        'response_data' => $data
                    ]);
                    
                    $this->dispatch('notify', [
                        'type' => 'error',
                        'message' => 'Error: Respuesta de API en formato inválido'
                    ]);
                    return;
                }
                
                $this->subrubros = collect($data['subrubros']);
                
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Rubro actualizado correctamente'
                ]);
            } else {
                Log::error('API Error in marcarTodos', [
                    'user_id' => Auth::id(),
                    'rubro_id' => $rubroId,
                    'status' => $response->status()
                ]);
                
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Error al actualizar rubro'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error in marcarTodos', [
                'user_id' => Auth::id(),
                'rubro_id' => $rubroId,
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error temporal. Intente nuevamente.'
            ]);
        }
    }

    public function render()
    {
        try {
            $token = $this->getAuthController()->getNewToken();
            
            $response = Http::timeout(10)
                ->withToken($token)
                ->get($this->getApiUrl() . '/rubros', [
                    'search' => $this->search
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $resultados = collect($data['rubros']);
            } else {
                Log::error('Error loading rubros for render', [
                    'user_id' => Auth::id(),
                    'status' => $response->status()
                ]);
                $resultados = collect();
            }

        } catch (\Exception $e) {
            Log::error('Error in rubros render', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            $resultados = collect();
        }

        return view('livewire.rubros-edit', compact('resultados'));
    }

    /**
     * ✅ Método centralizado para URL de API
     */
    private function getApiUrl(): string
    {
        $url = env('PLATAFORMA_API_URL');
        return rtrim($url, '/');
    }
}