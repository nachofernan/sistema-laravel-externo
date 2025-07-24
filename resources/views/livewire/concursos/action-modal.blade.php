<div class="font-normal">
    {{-- A good traveler has no fixed plans and is not intent upon arriving. --}}
    @if ($invitacion->intencion == 0 && $concurso->fecha_cierre > now() && $concurso->estado->id == 2)
        <button class="px-8 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-300 text-md mt-1" wire:click="$set('open', true)"> 
            ¿Participar?
        </button>
    @elseif ($invitacion->intencion == 1 && $concurso->fecha_cierre > now() && $concurso->estado->id == 2)
        @if ($documentacion_completa)
            <button class="px-8 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-300 text-md mt-1" wire:click="$set('open', true)"> 
                Presentar Oferta
            </button>
        @else
            <button class="px-8 py-3 bg-yellow-200 text-black rounded-lg hover:bg-yellow-400 transition-colors duration-300 text-md mt-1" wire:click="$set('open', true)"> 
                Presentar Oferta con Documentación Faltante
            </button>
        @endif
    @elseif ($invitacion->intencion < 2)
        <button class="px-8 py-3 bg-blue-100 text-blue-700 rounded-lg cursor-default text-md mt-1"> 
            No se Presentó Oferta
        </button>
    @elseif ($invitacion->intencion == 2)
        <button class="px-8 py-3 bg-red-100 text-red-700 text-sm font-bold rounded-lg cursor-default text-md mt-1 mr-10"> 
            Intención Rechazada
        </button>
        @if ($concurso->fecha_cierre > now())
            <button wire:click="$set('open', true)" class="px-2 hover:underline text-blue-500 text-sm px-8 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-300 text-md mt-1">
                Participar
            </button>
        @endif
    @elseif ($invitacion->intencion == 3)
        <button class="px-8 py-3 bg-green-100 text-green-700 rounded-lg cursor-default text-md mt-1 mr-10"> 
            Oferta Presentada
        </button>
        @if ($concurso->fecha_cierre > now())
        <button wire:click="$set('open', true)" class="px-8 py-3 bg-red-100 text-red-700 text-sm font-bold rounded-lg cursor-default text-md mt-1 hover:bg-red-200 hover:text-red-800 cursor-pointer">
            Dar de baja la oferta
        </button>
        @endif
    @endif

    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            <div class="flex justify-between items-center p-4 border-b">
                <h2 class="text-xl font-semibold text-gray-800">Participación en el Concurso</h2>
                <button wire:click="$set('open', false)" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="p-4 space-y-4">
                @if ($invitacion->intencion == 0)
                    <div class="bg-gray-50 border-l-4 border-gray-500 p-4 mb-4">
                        <p class="text-gray-700 font-normal">
                            Seleccionar una opción a continuación no implica un compromiso contractual ni obligación alguna. Solo expresa su intención de participar o no en este concurso de precios.
                        </p>
                        <p class="text-gray-700 mt-2 text-sm italic font-bold">
                            Es necesario que indique su intención para dar inicio al proceso de carga de la documentación.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <form action="{{ route('data-request.editar-invitacion') }}" method="POST" class="w-full">
                            @csrf
                            <input type="hidden" name="invitacion" value="{{$invitacion->id}}">
                            <input type="hidden" name="intencion" value="1">
                            <button type="submit" class="w-full bg-green-500 text-white font-bold text-lg py-3 rounded-lg shadow hover:bg-green-600 transition-colors duration-300">
                                Deseo participar en el Concurso de Precios
                            </button>
                        </form>

                        <form action="{{ route('data-request.editar-invitacion') }}" method="POST" class="w-full">
                            @csrf
                            <input type="hidden" name="invitacion" value="{{$invitacion->id}}">
                            <input type="hidden" name="intencion" value="2">
                            <button type="submit" class="w-full bg-red-500 text-white font-bold text-lg py-3 rounded-lg shadow hover:bg-red-600 transition-colors duration-300">
                                No participaré del Concurso de Precios
                            </button>
                        </form>
                    </div>
                @elseif ($invitacion->intencion == 1)
                    @if ($documentacion_completa)
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                            <p class="text-blue-700">
                                La documentación se encuentra completa y la oferta está lista para ser presentada. 
                                De todos modos, podrá subir nueva documentación de ser necesario.
                            </p>
                        </div>

                        <form action="{{ route('data-request.editar-invitacion') }}" method="POST">
                            @csrf
                            <input type="hidden" name="invitacion" value="{{$invitacion->id}}">
                            <input type="hidden" name="intencion" value="3">
                            <button type="submit" class="w-full bg-blue-500 text-white font-bold text-lg py-3 rounded-lg shadow hover:bg-blue-600 transition-colors duration-300">
                                Presentar Oferta
                            </button>
                        </form>
                    @else
                        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
                            <p class="text-yellow-700">
                                Presentar la oferta con documentación faltante puede ser motivo de desestimación de la misma. Tenga presente que podrá seguir cargado documentación aún habiendo presentado la oferta.
                            </p>
                        </div>

                        <form action="{{ route('data-request.editar-invitacion') }}" method="POST">
                            @csrf
                            <input type="hidden" name="invitacion" value="{{$invitacion->id}}">
                            <input type="hidden" name="intencion" value="3">
                            <button type="submit" class="w-full bg-yellow-200 text-black font-bold text-lg py-3 rounded-lg shadow hover:bg-yellow-400 transition-colors duration-300">
                                Presentar Oferta
                            </button>
                        </form>
                    @endif
                @elseif ($invitacion->intencion == 2 && $concurso->fecha_cierre > now())
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                        <p class="text-red-700">
                            La siguiente acción no implica un compromiso contractual ni obligación alguna. Solo expresa su intención de participar o no en este concurso de precios.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <form action="{{ route('data-request.editar-invitacion') }}" method="POST" class="w-full">
                            @csrf
                            <input type="hidden" name="invitacion" value="{{$invitacion->id}}">
                            <input type="hidden" name="intencion" value="1">
                            <button type="submit" class="w-full bg-green-500 text-white font-bold text-lg py-3 rounded-lg shadow hover:bg-green-600 transition-colors duration-300">
                                Participar en el Concurso de Precios
                            </button>
                        </form>
                    </div>
                @elseif ($invitacion->intencion == 3 && $concurso->fecha_cierre > now())
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                        <p class="text-red-700">
                            Dar de baja la oferta implica que se borrarán todos los archivos subidos, la intención se mantendrá. Esta acción no puede ser revertida.
                        </p>
                        <p class="text-red-700">
                            Ingrese su contraseña y haga clic en el botón de Baja para completar la acción
                        </p>
                        <form action="{{ route('data-request.bajar-oferta') }}" method="POST" class="w-full">
                            @csrf
                            <input type="hidden" name="invitacion" value="{{$invitacion->id}}">
                            <input type="hidden" name="intencion" value="1">
                            <input type="password" name="password" class="w-full border border-gray-300 rounded-lg p-2 my-2" placeholder="Ingrese su contraseña">
                            <button type="submit" class="w-full bg-red-500 text-white font-bold text-lg py-3 rounded-lg shadow hover:bg-red-600 transition-colors duration-300">
                                Dar de Baja la Oferta
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            {{-- Footer intentionally left empty --}}
        </x-slot>
    </x-dialog-modal>
</div>
