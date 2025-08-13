<div>
    <button wire:click="$set('open', true)" class="rounded py-1 px-3 text-blue-500 hover:underline text-sm">Cargar</button>
    
    <x-dialog-modal wire:model="open"> 
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
                <form wire:submit.prevent="submit" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Seleccionar archivo PDF
                        </label>
                        <input 
                            type="file" 
                            wire:model="file" 
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
                        @error('file') 
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    @if ($esDocumentoAdicional)
                        <div class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Comentarios (opcional)
                            </label>
                            <textarea 
                                wire:model="comentarios" 
                                rows="3"
                                placeholder="Agregue comentarios sobre este documento adicional..."
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm"
                            ></textarea>
                            @error('comentarios') 
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif
    
                    <button 
                        type="submit" 
                        class="w-full bg-cyan-500 text-white py-2 rounded-lg hover:bg-cyan-600 transition-colors duration-300"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                    >
                        <span wire:loading.remove>Subir Archivo</span>
                        <span wire:loading>Subiendo...</span>
                    </button>
                </form>

                @if ($successMessage)
                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mt-6 text-green-900 text-sm rounded">
                        {{ $successMessage }}
                    </div>
                @endif

                @if ($errorMessage)
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mt-6 text-red-900 text-sm rounded">
                        {{ $errorMessage }}
                    </div>
                @endif

                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mt-6 text-blue-900 text-sm rounded">
                    <strong>Información:</strong> El archivo será procesado y validado por el sistema. Asegúrese de que el documento sea legible y esté en formato PDF.
                </div>
            </div>
        </x-slot> 
        
        <x-slot name="footer">
        </x-slot> 
    </x-dialog-modal> 
</div>