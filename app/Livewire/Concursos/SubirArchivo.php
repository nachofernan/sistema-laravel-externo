<?php

namespace App\Livewire\Concursos;

use Livewire\Component;
use stdClass;

class SubirArchivo extends Component
{
    public $open = false;
    public $concurso;
    public $invitacion;
    public $documento;

    public function mount($concurso, $invitacion, $documento = null) {
        $this->concurso = $concurso;
        $this->invitacion = $invitacion;
        if($documento) {
            $this->documento = $documento;
        } else {
            $this->documento = new stdClass();
            $this->documento->nombre = 'Otros documentos';
            $this->documento->id = 0;
        }
    }

    public function render()
    {
        return view('livewire.concursos.subir-archivo');
    }
}
