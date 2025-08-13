<div>
    {{-- Success is as dangerous as failure. --}}
    @if (!($concurso->estado['id'] == 3 || $concurso->fecha_cierre < now()) && $puede_eliminar) 
        <button wire:click="$set('open', true)" class="py-1 px-3 text-red-500 hover:underline text-xs">Eliminar</button>
    @elseif(!($concurso->estado['id'] == 3 || $concurso->fecha_cierre < now()) && !$puede_eliminar)
        <span class="py-1 px-3 text-gray-400 text-xs cursor-not-allowed" title="No se puede eliminar el último documento obligatorio">
            No eliminable
        </span>
    @endif

    <x-dialog-modal wire:model="open"> 
        <div class="max-w-10xl">
            <x-slot name="title"> 
                <div class="border-b py-2"> 
                    Eliminar Documento
                </div>
            </x-slot> 
            <x-slot name="content">
                <div class="pb-4">
                    <p class="text-gray-700 mb-4">
                        ¿Está seguro que desea eliminar este documento? Esta acción no se puede deshacer.
                    </p>
                    
                    @if ($errors->has('eliminacion'))
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                            <p class="text-red-700">
                                {{ $errors->first('eliminacion') }}
                            </p>
                        </div>
                    @endif
                </div>
            </x-slot> 
            <x-slot name="footer">
                <div class="flex justify-end space-x-3">
                    <button 
                        wire:click="$set('open', false)" 
                        class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500"
                        {{ $eliminando ? 'disabled' : '' }}
                    >
                        Cancelar
                    </button>
                    <button 
                        wire:click="eliminarDocumento" 
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        {{ $eliminando ? 'disabled' : '' }}
                    >
                        @if ($eliminando)
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Eliminando...
                        @else
                            Eliminar
                        @endif
                    </button>
                </div>
            </x-slot> 
        </div>
    </x-dialog-modal> 
</div>
