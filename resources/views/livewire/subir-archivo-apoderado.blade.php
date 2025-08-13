<div>
    <div class="text-right">
        <button class="link-azul text-sm" type="button" wire:click="$set('open', true)"> 
            Nuevo Apoderado
        </button> 
    </div>

    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Cargar Nuevo Apoderado
        </x-slot>

        <x-slot name="content">
            <form wire:submit.prevent="submit" enctype="multipart/form-data">
                <div class="grid grid-cols-10 gap-4">
                    <div class="col-span-3 text-right mt-2">
                        Archivo
                    </div>
                    <div class="col-span-7">
                        <input type="file" wire:model="file" class="input-full" required accept=".pdf">
                    </div>

                    <div class="col-span-3 text-right mt-2">
                        Tipo
                    </div>
                    <div class="col-span-7">
                        <select wire:model.live="tipo" class="input-full" required>
                            <option value="apoderado">Apoderado</option>
                            <option value="representante">Representante Legal</option>
                        </select>
                    </div>

                    @if ($tipo == 'representante')
                        <div class="col-span-3 text-right mt-2">
                            Nombre
                        </div>
                        <div class="col-span-7">
                            <input type="text" wire:model="nombre" class="input-full" placeholder="Nombre del Representante Legal" required autocomplete="off">
                        </div>

                        <div class="col-span-3 text-right mt-2">
                            Vencimiento
                        </div>
                        <div class="col-span-7">
                            <input type="date" wire:model="vencimiento" class="input-full" value="{{now()->addYear()->format('Y-m-d')}}">
                        </div>
                    @endif
                </div>

                <div class="text-right pt-4">
                    <button type="submit" class="boton-celeste">Cargar Documento</button>
                </div>
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
                <strong>Información:</strong> Una vez cargado el documento, deberá ser validado por la Gerencia de Legales de la empresa. En caso de rechazo, se le notificará por correo electrónico las razones. Gracias y disculpe las molestias.
            </div>
        </x-slot>

        <x-slot name="footer">
        </x-slot>
    </x-dialog-modal>
</div>