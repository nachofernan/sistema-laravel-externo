<?php

namespace App\Livewire\Concursos;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ActionModal extends Component
{
    public $open = false;
    public $concurso;
    public $invitacion;
    public $documentacion_completa;

    public function mount($concurso, $invitacion) {
        $this->concurso = $concurso;
        $this->invitacion = $invitacion;
        $this->documentacion_completa = $this->checkDocumentacion();
    }

    public function checkDocumentacion() {
        try {
            // ✅ Verificar si los datos vienen de API (objeto stdClass) o BD (modelo)
            if (is_object($this->concurso) && !method_exists($this->concurso, 'documentos_requeridos')) {
                // Los datos vienen de API - usar propiedades directas
                return $this->checkDocumentacionFromApi();
            } else {
                // Los datos vienen de BD - usar métodos de modelo (compatibilidad)
                return $this->checkDocumentacionFromModel();
            }
        } catch (\Exception $e) {
            Log::error('Error checking documentation', [
                'error' => $e->getMessage(),
                'concurso_id' => $this->concurso->id ?? 'unknown'
            ]);
            return false;
        }
    }

    /**
     * ✅ Verificación para datos que vienen de API
     */
    private function checkDocumentacionFromApi()
    {
        if (!isset($this->concurso->documentos_requeridos) || !is_array($this->concurso->documentos_requeridos)) {
            return true; // Si no hay documentos requeridos, está completa
        }

        foreach($this->concurso->documentos_requeridos as $documento_tipo) {
            // Solo validar si es obligatorio
            if (!$documento_tipo->obligatorio) {
                continue;
            }

            // Contar documentos subidos de este tipo
            $docs_subidos = $this->countDocumentosByTipoFromApi($documento_tipo->id);
            
            if ($docs_subidos == 0) {
                // Si tiene documento de proveedor asociado, verificar si está vigente
                if (isset($documento_tipo->tipo_documento_proveedor) && $documento_tipo->tipo_documento_proveedor) {
                    $doc_proveedor = $this->findProveedorDocumentFromApi($documento_tipo->tipo_documento_proveedor->id);
                    
                    if (!$doc_proveedor) {
                        return false; // Documento de proveedor no existe
                    }
                    
                    if (isset($doc_proveedor->vencimiento) && $doc_proveedor->vencimiento) {
                        if (Carbon::parse($doc_proveedor->vencimiento)->isPast()) {
                            return false; // Documento vencido
                        }
                    }
                } else {
                    return false; // Documento requerido no subido
                }
            }
        }
        
        return true;
    }

    /**
     * ✅ Verificación para datos que vienen de BD (compatibilidad hacia atrás)
     */
    private function checkDocumentacionFromModel()
    {
        foreach($this->concurso->documentos_requeridos as $documento_tipo) {
            $sin_docs_subidos = count($this->invitacion->documentos_con_tipo_id($documento_tipo->id)) == 0;
        
            // Solo validar si es obligatorio
            if ($documento_tipo->obligatorio) {
                if($documento_tipo->tipo_documento_proveedor) {
                    $doc_prov = Auth::user()->proveedor->traer_documento($documento_tipo->tipo_documento_proveedor->id);
                    if($doc_prov && $doc_prov->vencimiento && Carbon::create($doc_prov->vencimiento)->isPast() && $sin_docs_subidos) {
                        return false;
                    } elseif(!$doc_prov && $sin_docs_subidos) {
                        return false;
                    }
                } elseif($sin_docs_subidos) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * ✅ Contar documentos de un tipo específico desde datos de API
     */
    private function countDocumentosByTipoFromApi($documentoTipoId)
    {
        if (!isset($this->invitacion->documentos) || !is_array($this->invitacion->documentos)) {
            return 0;
        }

        $count = 0;
        foreach ($this->invitacion->documentos as $documento) {
            if ($documento->documento_tipo_id == $documentoTipoId) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * ✅ Encontrar documento de proveedor desde datos de API
     */
    private function findProveedorDocumentFromApi($documentoTipoId)
    {
        // Si los datos del proveedor están en invitacion
        if (isset($this->invitacion->proveedor->documentos) && is_array($this->invitacion->proveedor->documentos)) {
            foreach ($this->invitacion->proveedor->documentos as $documento) {
                if ($documento->documento_tipo_id == $documentoTipoId) {
                    return $documento;
                }
            }
        }
        
        return null;
    }

    public function render()
    {
        return view('livewire.concursos.action-modal');
    }
}