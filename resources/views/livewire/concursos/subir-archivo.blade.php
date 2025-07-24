<div>
    {{-- Do your work, then step back. --}}
    {{-- <button class="boton-celeste text-sm w-full" > 
        Cargar Nueva Documentación
    </button> --}}
    <button wire:click="$set('open', true)" class="rounded py-1 px-3 text-blue-500 hover:underline text-sm">Cargar</button>
    <x-dialog-modal wire:model="open"> 
        <div class="max-w-10xl">
        <x-slot name="title"> 
            <div class="flex justify-between items-center p-4 border-b">
                <h2 class="text-xl font-semibold text-gray-800">Cargar: <span>{{$documento->nombre}}</span></h2>
                <button wire:click="$set('open', false)" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </x-slot> 
        <x-slot name="content">
            <div class="px-6">
                <form 
                    action="{{ route('file.upload', is_object($invitacion) ? $invitacion->id : $invitacion['id']) }}" 
                    method="POST" 
                    enctype="multipart/form-data"
                    class="space-y-4"
                >
                    @csrf
                    <input type="hidden" name="documento_tipo_id" value="{{is_object($documento) ? $documento->id : $documento['id']}}">
                    
                    <div>
                        <input 
                            type="file" 
                            name="file" 
                            required
                            accept=".pdf"
                            class="block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-full file:border-0
                                file:text-sm file:font-semibold
                                file:bg-cyan-50 file:text-cyan-700
                                hover:file:bg-cyan-100
                                focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
                        >
                    </div>
    
                    <button 
                        type="submit" 
                        class="w-full bg-cyan-500 text-white py-2 rounded-lg hover:bg-cyan-600 transition-colors duration-300"
                    >
                        Subir Archivo
                    </button>
                </form>
            </div>
        </x-slot> 
        <x-slot name="footer">
        </x-slot> 
        </div>
    </x-dialog-modal> 
</div>