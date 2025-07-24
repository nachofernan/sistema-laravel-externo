<div>
    {{-- Knowing others is intelligence; knowing yourself is true wisdom. --}}
    <div class="text-right">
        <button class="link-azul text-sm" type="submit" wire:click="$set('open', true)"> 
            Nuevo Apoderado
        </button> 
    </div>

    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Cargar Nuevo Apoderado
        </x-slot>

        <x-slot name="content">
            <form 
                action="{{ route('file.uploadDocumentacionApoderado') }}" 
                method="POST" 
                enctype="multipart/form-data"
            >
                @csrf
                <div class="grid grid-cols-10 gap-4">
                    <input type="hidden" name="proveedor_id" value="{{ $proveedor_id }}">

                    <div class="col-span-3 text-right mt-2">
                        Archivo
                    </div>
                    <div class="col-span-7">
                        <input type="file" name="file" class="input-full" required accept=".pdf">
                    </div>

                    <div class="col-span-3 text-right mt-2">
                        Tipo
                    </div>
                    <div class="col-span-7">
                        <select name="tipo" wire:model.live="tipo" class="input-full" required>
                            <option value="apoderado">Apoderado</option>
                            <option value="representante">Representante Legal</option>
                        </select>
                    </div>

                    @if ($tipo == 'representante')
                        <div class="col-span-3 text-right mt-2">
                            Nombre
                        </div>
                        <div class="col-span-7">
                            <input type="text" name="nombre" class="input-full" placeholder="Nombre del Representante Legal" required autocomplete="off">
                        </div>

                        <div class="col-span-3 text-right mt-2">
                            Vencimiento
                        </div>
                        <div class="col-span-7">
                            <input type="date" name="vencimiento" class="input-full" value="{{now()->addYear()->format('Y-m-d')}}">
                        </div>
                    @endif
                </div>

                <div class="text-right pt-4">
                    <button type="submit" class="boton-celeste">Cargar Documento</button>
                </div>
            </form>
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mt-6 text-blue-900 text-sm rounded">
                <strong>Información:</strong> Una vez cargado el documento, deberá ser validado por la Gerencia de Legales de la empresa. En caso de rechazo, se le notificará por correo electrónico las razones. Gracias y disculpe las molestias.
            </div>
        </x-slot>

        <x-slot name="footer">
        </x-slot>
    </x-dialog-modal>
</div>