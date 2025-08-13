<?php

namespace App\Livewire\Concursos;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\ConcursosApiService;

class EliminarArchivo extends Component
{
    public $open = false;
    public $documento;
    public $concurso;
    public $invitacion;
    public $puede_eliminar;
    public $eliminando = false;

    public function mount($documento, $concurso = null, $invitacion = null) {
        $this->documento = $documento;
        $this->concurso = $concurso;
        $this->invitacion = $invitacion;
        $this->puede_eliminar = $this->puedeEliminar();
    }

    /**
     * Eliminar documento usando la API de concursos
     */
    public function eliminarDocumento()
    {
        if (!$this->puede_eliminar) {
            $this->addError('eliminacion', 'No se puede eliminar este documento.');
            return;
        }

        // Verificar que el concurso no esté cerrado
        if ($this->concursoCerrado()) {
            $this->addError('eliminacion', 'No se puede eliminar documentos de un concurso cerrado.');
            return;
        }

        $this->eliminando = true;

        try {
            $user = Auth::user();
            $token = session('jwt_token');
            $api = new ConcursosApiService($token, $user->username);
            
            // Llamar al endpoint de eliminación de documento
            $success = $api->eliminarDocumento($this->concurso->id, $this->documento->id);

            if ($success) {
                Log::info('Documento eliminado exitosamente', [
                    'user_id' => Auth::id(),
                    'concurso_id' => $this->concurso->id,
                    'documento_id' => $this->documento->id
                ]);

                $this->open = false;
                $this->eliminando = false;
                
                // Emitir evento para refrescar la lista de documentos
                $this->dispatch('documento-eliminado', [
                    'concurso_id' => $this->concurso->id,
                    'documento_id' => $this->documento->id
                ]);
                session()->flash('success', 'Documento eliminado correctamente.');
                $this->dispatch('documento-eliminado');
                
                //return redirect()->route('concursos.show', $this->concurso->id)->with('success', 'Documento eliminado correctamente.');

            } else {
                Log::error('Error al eliminar documento', [
                    'user_id' => Auth::id(),
                    'concurso_id' => $this->concurso->id,
                    'documento_id' => $this->documento->id
                ]);

                $this->addError('eliminacion', 'Error al eliminar el documento. Intente nuevamente.');
            }

        } catch (\Exception $e) {
            Log::error('Excepción al eliminar documento', [
                'user_id' => Auth::id(),
                'concurso_id' => $this->concurso->id,
                'documento_id' => $this->documento->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->addError('eliminacion', 'Error temporal del sistema. Intente nuevamente.');
        }

        $this->eliminando = false;
    }

    /**
     * Verificar si el concurso está cerrado
     */
    private function concursoCerrado()
    {
        if (!$this->concurso) {
            return true;
        }

        // Verificar estado del concurso
        if (isset($this->concurso->estado) && is_array($this->concurso->estado)) {
            if (isset($this->concurso->estado['id']) && $this->concurso->estado['id'] == 3) {
                return true; // Concurso finalizado
            }
        }

        // Verificar fecha de cierre
        if (isset($this->concurso->fecha_cierre)) {
            $fechaCierre = is_string($this->concurso->fecha_cierre) 
                ? \Carbon\Carbon::parse($this->concurso->fecha_cierre)
                : $this->concurso->fecha_cierre;
            
            if (now()->gte($fechaCierre)) {
                return true; // Concurso cerrado por fecha
            }
        }

        return false;
    }

    private function puedeEliminar() {
        // Verificar si los datos vienen de API o BD
        if (is_object($this->documento) && !method_exists($this->documento, 'invitacion')) {
            // Los datos vienen de API - usar propiedades directas
            return $this->puedeEliminarFromApi();
        } else {
            // Los datos vienen de BD - usar métodos de modelo (compatibilidad)
            return $this->puedeEliminarFromModel();
        }
    }

    /**
     * Verificación para datos que vienen de API
     */
    private function puedeEliminarFromApi()
    {
        // Verificar que el documento sea del proveedor (no de la empresa)
        if (isset($this->documento->user_id_created) && $this->documento->user_id_created !== null) {
            return false; // Documento subido por la empresa, no se puede eliminar
        }

        // Si no tiene documento_tipo_id, se puede eliminar (documento libre)
        if (!isset($this->documento->documento_tipo_id) || !$this->documento->documento_tipo_id) {
            return true;
        }

        // Buscar el documento tipo en los datos de API
        $documento_tipo = $this->findDocumentoTipoFromApi($this->documento->documento_tipo_id);
        
        if (!$documento_tipo || !$documento_tipo->obligatorio) {
            return true; // No es obligatorio, se puede eliminar
        }

        // Contar documentos del mismo tipo en la invitación
        $cantidad_docs = $this->countDocumentosSameTipoFromApi();
        
        // Si solo hay 1 documento de este tipo obligatorio, no se puede eliminar
        return $cantidad_docs > 1;
    }

    /**
     * Verificación para datos que vienen de BD (compatibilidad)
     */
    private function puedeEliminarFromModel()
    {
        // Verificar que el documento sea del proveedor (no de la empresa)
        if (isset($this->documento->user_id_created) && $this->documento->user_id_created !== null) {
            return false; // Documento subido por la empresa, no se puede eliminar
        }

        // Si tiene documento_tipo_id y es obligatorio
        if ($this->documento->documento_tipo_id) {
            $documento_tipo = \App\Models\Concursos\DocumentoTipo::find($this->documento->documento_tipo_id);
            
            if ($documento_tipo && $documento_tipo->obligatorio) {
                // Contar cuántos documentos de este tipo tiene la invitación
                $cantidad_docs = $this->documento->invitacion
                    ->documentos_con_tipo_id($this->documento->documento_tipo_id)
                    ->count();
                
                // Si solo hay 1 documento de este tipo obligatorio, no se puede eliminar
                return $cantidad_docs > 1;
            }
        }
        
        return true; // Se puede eliminar si no es obligatorio o hay más de uno
    }

    /**
     * Buscar documento tipo desde datos de API usando $this->concurso
     */
    private function findDocumentoTipoFromApi($documentoTipoId)
    {
        // Usar $this->concurso en lugar de $this->documento->concurso
        if ($this->concurso && isset($this->concurso->documentos_requeridos)) {
            foreach ($this->concurso->documentos_requeridos as $doc_tipo) {
                if ($doc_tipo->id == $documentoTipoId) {
                    return $doc_tipo;
                }
            }
        }

        // Usar $this->invitacion en lugar de $this->documento->invitacion
        if ($this->invitacion && isset($this->invitacion->documentos)) {
            foreach ($this->invitacion->documentos as $doc) {
                if ($doc->documento_tipo_id == $documentoTipoId && isset($doc->documento_tipo)) {
                    return $doc->documento_tipo;
                }
            }
        }
        
        return null;
    }

    /**
     * Contar documentos del mismo tipo usando $this->invitacion
     */
    private function countDocumentosSameTipoFromApi()
    {
        if (!$this->invitacion || !isset($this->invitacion->documentos)) {
            return 0;
        }

        $count = 0;
        foreach ($this->invitacion->documentos as $doc) {
            if ($doc->documento_tipo_id == $this->documento->documento_tipo_id) {
                $count++;
            }
        }
        
        return $count;
    }
    
    public function render()
    {
        return view('livewire.concursos.eliminar-archivo');
    }
}