<?php

namespace App\Livewire;

use App\Services\ProveedorApiService;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class SubirArchivoGeneral extends Component
{
    use WithFileUploads;

    public $open = false;
    public $proveedor_id;
    public $documento_tipo_id;
    public $documentos = [];
    public $vencimiento;
    public $file;
    public $tipos_documentos = [];
    public $successMessage = null;
    public $errorMessage = null;

    public function mount($proveedor_id)
    {
        $this->proveedor_id = $proveedor_id;
        $this->loadDocumentTypes();
    }

    public function loadDocumentTypes()
    {
        $api = new ProveedorApiService();
        $tipos = $api->getTiposDocumentos();
        $this->tipos_documentos = $tipos['tipos_documentos'] ?? [];
    }

    public function submit()
    {
	Log::info('Inicia');
        $this->validate([
            'file' => 'required|file|mimes:pdf|max:5120',
            'documento_tipo_id' => ['required', Rule::in(collect($this->tipos_documentos)->pluck('id')->toArray())],
            'vencimiento' => 'nullable|date',
        ]);
        Log::info('Valida');
        $api = new ProveedorApiService();
	Log::info('API: info datos del proveedor', ['api' => $api]);
        $result = $api->subirDocumento($this->file, (int)$this->documento_tipo_id, $this->vencimiento);
        Log::info('API: resultado de carga', ['result' => $result]);
        if ($result) {
            $this->successMessage = 'Documento subido correctamente. Pendiente de validación.';
            $this->reset(['file', 'documento_tipo_id', 'vencimiento', 'open']);
            $this->dispatch('documento-subido');
        } else {
            $this->errorMessage = 'Error al subir el documento. Intente nuevamente.';
        }
    }

    public function render()
    {
        return view('livewire.subir-archivo-general', [
            'tipos_documentos' => $this->tipos_documentos,
            'successMessage' => $this->successMessage,
            'errorMessage' => $this->errorMessage,
        ]);
    }
}