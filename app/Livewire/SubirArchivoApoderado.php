<?php

namespace App\Livewire;

use App\Services\ProveedorApiService;
use Livewire\WithFileUploads;
use Livewire\Component;

class SubirArchivoApoderado extends Component
{
    use WithFileUploads;

    public $open = false;
    public $proveedor_id;
    public $tipo = 'apoderado';
    public $nombre;
    public $vencimiento;
    public $file;
    public $successMessage = null;
    public $errorMessage = null;

    public function mount($proveedor_id)
    {
        $this->proveedor_id = $proveedor_id;
    }

    public function submit()
    {
        $this->validate([
            'file' => 'required|file|mimes:pdf|max:5120',
            'tipo' => 'required|in:apoderado,representante',
            'nombre' => $this->tipo === 'representante' ? 'required' : 'nullable',
            'vencimiento' => 'nullable|date',
        ]);

        $api = new ProveedorApiService();
        $result = $api->subirApoderado($this->file, $this->tipo, $this->nombre, $this->vencimiento);

        if ($result) {
            $this->successMessage = 'Apoderado subido correctamente. Pendiente de validación.';
            $this->reset(['file', 'tipo', 'nombre', 'vencimiento', 'open']);
            $this->dispatch('apoderado-subido');
        } else {
            $this->errorMessage = 'Error al subir el apoderado. Intente nuevamente.';
        }
    }

    public function render()
    {
        return view('livewire.subir-archivo-apoderado', [
            'successMessage' => $this->successMessage,
            'errorMessage' => $this->errorMessage,
        ]);
    }
}