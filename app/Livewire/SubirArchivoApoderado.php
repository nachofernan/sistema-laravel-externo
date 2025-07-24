<?php

namespace App\Livewire;

use App\Http\Controllers\AuthController;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SubirArchivoApoderado extends Component
{
    public $open = false;
    public $proveedor_id;
    public $tipo = 'apoderado';
    public $documentos = [];
    public $vencimiento;

    public function mount($proveedor_id) 
    {
        $this->proveedor_id = $proveedor_id;
    }

    /**
     * ✅ Crear instancia fresca del AuthController en cada uso
     */
    private function getAuthController()
    {
        return new AuthController();
    }

    public function render()
    {
        return view('livewire.subir-archivo-apoderado');
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