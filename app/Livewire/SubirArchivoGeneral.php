<?php

namespace App\Livewire;

use App\Services\ProveedorApiService;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
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
        try {
            $this->validate([
                'file' => 'required|file|mimes:pdf|max:5120',
                'documento_tipo_id' => ['required', Rule::in(collect($this->tipos_documentos)->pluck('id')->toArray())],
                'vencimiento' => 'nullable|date',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validación fallida al subir documento general', [
                'user_id' => Auth::id(),
                'proveedor_id' => $this->proveedor_id,
                'documento_tipo_id' => $this->documento_tipo_id,
                'file_name' => $this->file?->getClientOriginalName(),
                'file_size' => $this->file?->getSize(),
                'errors' => $e->errors(),
            ]);
            throw $e;
        }

        $fileName = $this->file?->getClientOriginalName();
        $fileSize = $this->file?->getSize();
        $documentoTipoId = $this->documento_tipo_id;

        $api = new ProveedorApiService();
        $result = $api->subirDocumento($this->file, (int) $this->documento_tipo_id, $this->vencimiento);

        if ($result) {
            $this->successMessage = 'Documento subido correctamente. Pendiente de validación.';
            $this->reset(['file', 'documento_tipo_id', 'vencimiento', 'open']);
            $this->dispatch('documento-subido');
        } else {
            $this->errorMessage = 'Error al subir el documento. Intente nuevamente.';

            Log::error('Error al subir documento general vía API', [
                'user_id' => Auth::id(),
                'proveedor_id' => $this->proveedor_id,
                'documento_tipo_id' => $documentoTipoId,
                'file_name' => $fileName,
                'file_size' => $fileSize,
            ]);
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