<div class="font-normal">
    @php
        $estadoBoton = $this->getEstadoBoton();
        $mostrarModal = $this->getMostrarModal();
    @endphp

    {{-- Botón principal según el estado --}}
    @if ($estadoBoton === 'participar')
        <button class="px-8 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-300 text-md mt-1" wire:click="$set('open', true)"> 
            ¿Participar?
        </button>
    @elseif ($estadoBoton === 'presentar_oferta_completa')
        <button class="px-8 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors duration-300 text-md mt-1" wire:click="$set('open', true)"> 
            Presentar Oferta Completa
        </button>
    @elseif ($estadoBoton === 'presentar_oferta_faltante')
        <button class="px-8 py-3 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors duration-300 text-md mt-1" wire:click="$set('open', true)"> 
            Presentar Oferta
        </button>
    @elseif ($estadoBoton === 'falta_documentacion_obligatoria')
        <span class="px-8 py-3 bg-red-500 text-white rounded-lg text-md mt-1 "> 
            Documentación Obligatoria Faltante
        </span>
    @elseif ($estadoBoton === 'arrepentimiento')
        <div class="flex items-center space-x-2">
            <span class="px-4 py-3 bg-red-100 text-red-700 rounded-lg text-md"> 
                Intención Rechazada
            </span>
            <button class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-300 text-md" wire:click="$set('open', true)"> 
                Participar
            </button>
        </div>
    @elseif ($estadoBoton === 'oferta_presentada')
        <div class="flex items-center space-x-2">
            <span class="px-4 py-3 bg-green-100 text-green-700 rounded-lg text-md"> 
                Oferta Presentada
            </span>
            <button class="px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-300 text-md" wire:click="$set('open', true)"> 
                Dar de Baja
            </button>
        </div>
    @else
        {{-- Estado inactivo --}}
        @if (isset($invitacion->intencion))
            @if ($invitacion->intencion == 0)
                <span class="px-4 py-3 bg-gray-100 text-gray-700 rounded-lg text-md"> 
                    Sin Intención Definida
                </span>
            @elseif ($invitacion->intencion == 1)
                <span class="px-4 py-3 bg-blue-100 text-blue-700 rounded-lg text-md"> 
                    Con Intención de Participar
                </span>
            @elseif ($invitacion->intencion == 2)
                <span class="px-4 py-3 bg-red-100 text-red-700 rounded-lg text-md"> 
                    Intención Rechazada
                </span>
            @elseif ($invitacion->intencion == 3)
                <span class="px-4 py-3 bg-green-100 text-green-700 rounded-lg text-md"> 
                    Oferta Presentada
                </span>
            @endif
        @else
            <span class="px-4 py-3 bg-gray-100 text-gray-700 rounded-lg text-md"> 
                Estado No Definido
            </span>
        @endif
    @endif

    {{-- Modal --}}
    @if ($mostrarModal)
        <x-dialog-modal wire:model="open">
            <x-slot name="title">
                <div class="flex justify-between items-center p-4 border-b">
                    <h2 class="text-xl font-semibold text-gray-800">
                        @if ($estadoBoton === 'participar')
                            Participación en el Concurso
                        @elseif ($estadoBoton === 'presentar_oferta_completa' || $estadoBoton === 'presentar_oferta_faltante')
                            Presentar Oferta
                        @elseif ($estadoBoton === 'arrepentimiento')
                            Cambiar Intención
                        @elseif ($estadoBoton === 'oferta_presentada')
                            Dar de Baja Oferta
                        @endif
                    </h2>
                    <button wire:click="$set('open', false)" class="text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </x-slot>

            <x-slot name="content">
                <div class="p-4 space-y-4">
                    {{-- Estado: Participar --}}
                    @if ($estadoBoton === 'participar')
                        <div class="bg-gray-50 border-l-4 border-gray-500 p-4 mb-4">
                            <p class="text-gray-700 font-normal">
                                Seleccionar una opción a continuación no implica un compromiso contractual ni obligación alguna. Solo expresa su intención de participar o no en este concurso de precios.
                            </p>
                            <p class="text-gray-700 mt-2 text-sm italic font-bold">
                                Es necesario que indique su intención para dar inicio al proceso de carga de la documentación.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <form action="{{ route('concursos.intencion', $concurso->id) }}" method="POST" class="w-full">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="intencion" value="1">
                                <button type="submit" class="w-full bg-green-500 text-white font-bold text-lg py-3 rounded-lg shadow hover:bg-green-600 transition-colors duration-300">
                                    Deseo participar en el Concurso de Precios
                                </button>
                            </form>

                            <form action="{{ route('concursos.intencion', $concurso->id) }}" method="POST" class="w-full">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="intencion" value="2">
                                <button type="submit" class="w-full bg-red-500 text-white font-bold text-lg py-3 rounded-lg shadow hover:bg-red-600 transition-colors duration-300">
                                    No participaré del Concurso de Precios
                                </button>
                            </form>
                        </div>
                    @endif

                    {{-- Estado: Presentar Oferta (Completa) --}}
                    @if ($estadoBoton === 'presentar_oferta_completa')
                        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
                            <p class="text-green-700 font-medium">
                                ¡Excelente! La documentación se encuentra completa y la oferta está lista para ser presentada.
                            </p>
                        </div>

                        <form action="{{ route('concursos.intencion', $concurso->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="intencion" value="3">
                            <button type="submit" class="w-full bg-green-500 text-white font-bold text-lg py-3 rounded-lg shadow hover:bg-green-600 transition-colors duration-300">
                                Presentar Oferta
                            </button>
                        </form>
                    @endif

                    {{-- Estado: Presentar Oferta (Faltante) --}}
                    @if ($estadoBoton === 'presentar_oferta_faltante')
                        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
                            <p class="text-yellow-700 font-medium">
                                Atención: Documentación incompleta
                            </p>
                            <p class="text-yellow-600 text-sm mt-2">
                                Presentar la oferta con documentación faltante puede ser motivo de desestimación de la misma.
                            </p>
                        </div>

                        <form action="{{ route('concursos.intencion', $concurso->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="intencion" value="3">
                            <button type="submit" class="w-full bg-yellow-500 text-white font-bold text-lg py-3 rounded-lg shadow hover:bg-yellow-600 transition-colors duration-300">
                                Presentar Oferta con Documentación Faltante
                            </button>
                        </form>
                    @endif

                    {{-- Estado: Arrepentimiento --}}
                    @if ($estadoBoton === 'arrepentimiento')
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                            <p class="text-blue-700">
                                Puede cambiar su intención y participar en el concurso. Esta acción no implica un compromiso contractual ni obligación alguna.
                            </p>
                        </div>

                        <form action="{{ route('concursos.intencion', $concurso->id) }}" method="POST" class="w-full">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="intencion" value="1">
                            <button type="submit" class="w-full bg-green-500 text-white font-bold text-lg py-3 rounded-lg shadow hover:bg-green-600 transition-colors duration-300">
                                Participar en el Concurso de Precios
                            </button>
                        </form>
                    @endif

                    {{-- Estado: Dar de Baja Oferta --}}
                    @if ($estadoBoton === 'oferta_presentada')
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                            <p class="text-red-700 font-medium mb-2">
                                ⚠️ Advertencia Importante
                            </p>
                            <p class="text-red-700 text-sm">
                                Dar de baja la oferta implica que se borrarán <strong>TODOS</strong> los archivos subidos por usted en esta oferta. 
                                La intención de participación se mantendrá, pero esta acción no puede ser revertida.
                            </p>
                        </div>
                        
                        <p class="text-gray-700 mb-4">
                            Para confirmar esta acción, ingrese su contraseña:
                        </p>
                        
                        <div class="mb-4">
                            <input 
                                type="password" 
                                wire:model="password"
                                class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-red-500"
                                placeholder="Ingrese su contraseña"
                                {{ $dando_baja ? 'disabled' : '' }}
                            >
                            @error('password')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        @if ($errors->has('baja'))
                            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                                <p class="text-red-700">
                                    {{ $errors->first('baja') }}
                                </p>
                            </div>
                        @endif
                        
                        <div class="flex justify-end space-x-3">
                            <button 
                                wire:click="$set('open', false)" 
                                class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                {{ $dando_baja ? 'disabled' : '' }}
                            >
                                Cancelar
                            </button>
                            <button 
                                wire:click="darBajaOferta" 
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                {{ $dando_baja ? 'disabled' : '' }}
                            >
                                @if ($dando_baja)
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Procesando...
                                @else
                                    Dar de Baja
                                @endif
                            </button>
                        </div>
                    @endif
                </div>
            </x-slot>

            <x-slot name="footer">
                {{-- Footer intentionally left empty --}}
            </x-slot>
        </x-dialog-modal>
    @endif
</div>
