<div>
    {{-- Success is as dangerous as failure. --}}
    @if (!($concurso->estado->id == 3 || $concurso->fecha_cierre < now()) && $puede_eliminar) 
        <button wire:click="$set('open', true)" class="rounded py-1 px-3 text-red-500 hover:underline text-sm">Eliminar</button>
    @elseif(!($concurso->estado->id == 3 || $concurso->fecha_cierre < now()) && !$puede_eliminar)
        <span class="rounded py-1 px-3 text-gray-400 text-sm cursor-not-allowed" title="No se puede eliminar el último documento obligatorio">
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
                    Esto borrará el archivo de forma permanente.
                </div>
                <form action="{{ route('file.delete') }}" method="POST" class="col-span-2">
                    @csrf
                    <input type="hidden" name="disk" value="concursos">
                    <input type="hidden" name="fileName" value="{{$documento->file_storage}}">
                    <button type="submit" class="w-full bg-red-500 text-white py-2 rounded-lg hover:bg-red-600 transition-colors duration-300">Eliminar</button>
                </form>
            </x-slot> 
            <x-slot name="footer">
            </x-slot> 
        </div>
    </x-dialog-modal> 
</div>
