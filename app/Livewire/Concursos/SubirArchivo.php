<?php

namespace App\Livewire\Concursos;

use App\Services\ConcursosApiService;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use stdClass;

class SubirArchivo extends Component
{
    use WithFileUploads;

    public $open = false;
    public $concurso;
    public $invitacion;
    public $documento;
    public $file;
    public $comentarios = '';
    public $successMessage = null;
    public $errorMessage = null;

    public function mount($concurso, $invitacion, $documento = null) {
        $this->concurso = $concurso;
        $this->invitacion = $invitacion;
        if($documento) {
            $this->documento = $documento;
        } else {
            $this->documento = new stdClass();
            $this->documento->nombre = 'Documento Adicional';
            $this->documento->id = null; // null indica documento adicional
        }
    }

    public function submit()
    {
        /* Log::info('SubirArchivo submit', [
            'file' => $this->file,
            'comentarios' => $this->comentarios,
        ]); */
        /* Log::info('SubirArchivo validate'); */
        try {
            $this->validate([
                'file' => 'required|file|mimes:pdf|max:10240',
                'comentarios' => 'nullable|string|max:500',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validación fallida al subir archivo de concurso', [
                'user_id' => Auth::id(),
                'concurso_id' => is_object($this->concurso) ? $this->concurso->id : ($this->concurso['id'] ?? null),
                'file_name' => $this->file?->getClientOriginalName(),
                'file_size' => $this->file?->getSize(),
                'errors' => $e->errors(),
            ]);
            throw $e;
        }
        /* Log::info('SubirArchivo validate passed'); */

        try {
            $user = Auth::user();
            $token = session('jwt_token');
            $api = new ConcursosApiService($token, $user->username);
            
            $concursoId = is_object($this->concurso) ? $this->concurso->id : $this->concurso['id'];
            $documentoTipoId = is_object($this->documento) ? $this->documento->id : $this->documento['id'];
            $fileName = $this->file?->getClientOriginalName();
            $fileSize = $this->file?->getSize();

            // Si documentoTipoId es null o 0, es un documento adicional
            $esDocumentoAdicional = empty($documentoTipoId);

            if ($esDocumentoAdicional) {
                // Para documentos adicionales, no enviamos documento_tipo_id
                $result = $api->subirDocumentoAdicional($concursoId, $this->file, $this->comentarios);
            } else {
                // Para documentos requeridos, enviamos documento_tipo_id
                $result = $api->subirDocumentoConcurso($concursoId, $this->file, $documentoTipoId);
            }

            if ($result) {
                $this->successMessage = 'Archivo subido exitosamente.';
                $this->reset(['file', 'comentarios', 'open']);
                $this->dispatch('documento-subido');
                
                Log::info('File uploaded successfully via Livewire', [
                    'user_id' => Auth::id(),
                    'concurso_id' => $concursoId,
                    'documento_tipo_id' => $documentoTipoId,
                    'es_adicional' => $esDocumentoAdicional,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                ]);
            } else {
                $this->errorMessage = 'Error al procesar el archivo. Intente nuevamente.';

                Log::error('File upload failed via Livewire', [
                    'user_id' => Auth::id(),
                    'concurso_id' => $concursoId,
                    'documento_tipo_id' => $documentoTipoId,
                    'es_adicional' => $esDocumentoAdicional,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                ]);
            }

        } catch (\Exception $e) {
            $this->errorMessage = 'Error temporal del sistema. Intente nuevamente.';

            Log::error('File upload exception via Livewire', [
                'user_id' => Auth::id(),
                'file_name' => $this->file?->getClientOriginalName(),
                'file_size' => $this->file?->getSize(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        $esDocumentoAdicional = empty($this->documento->id);
        
        return view('livewire.concursos.subir-archivo', [
            'successMessage' => $this->successMessage,
            'errorMessage' => $this->errorMessage,
            'esDocumentoAdicional' => $esDocumentoAdicional,
        ]);
    }
}
