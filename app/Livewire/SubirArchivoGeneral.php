<?php

namespace App\Livewire;

use App\Http\Controllers\AuthController;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SubirArchivoGeneral extends Component
{
    public $open = false;
    public $proveedor_id;
    public $documento_tipo_id;
    public $documentos = [];
    public $vencimiento;

    public function mount($proveedor_id) 
    {
        $this->proveedor_id = $proveedor_id;
        $this->loadDocumentTypes();
    }

    /**
     * ✅ Crear instancia fresca del AuthController en cada uso
     */
    private function getAuthController()
    {
        return new AuthController();
    }

    /**
     * Cargar tipos de documentos desde API
     */
    public function loadDocumentTypes()
    {
        try {
            $token = $this->getAuthController()->getNewToken();
            
            $response = Http::timeout(10)
                ->withToken($token)
                ->get($this->getApiUrl() . '/documento-tipos');

            if ($response->successful()) {
                $data = $response->json();
                $this->documentos = $data['documento_tipos'];
            } else {
                Log::error('Error loading document types', [
                    'user_id' => Auth::id(),
                    'status' => $response->status()
                ]);
                $this->documentos = [];
            }

        } catch (\Exception $e) {
            Log::error('Error in loadDocumentTypes', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            $this->documentos = [];
        }
    }

    public function render()
    {
        return view('livewire.subir-archivo-general');
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