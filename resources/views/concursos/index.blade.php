<x-app-layout>
    <div class="w-full xl:w-10/12 mb-12 xl:mb-0 px-4 mx-auto mt-4 pb-10">
        <div class="titulo-index border-b">
            Concursos Activos
        </div>
        <div class="container mx-auto grid grid-cols-1 md:grid-cols-3 gap-4 py-4">
            @if (count($invitaciones_activas) == 0)
                <span class="px-10 font-bold italic">
                    No hay concursos activos
                </span>    
            @endif
            @foreach($invitaciones_activas as $concurso)
                @php
                    $concursoObj = is_array($concurso) ? (object) $concurso : $concurso;
                    $estado = is_array($concursoObj->estado ?? null) ? (object) ($concursoObj->estado ?? []) : ($concursoObj->estado ?? null);
                    $invitacion = is_array($concursoObj->invitacion ?? null) ? (object) ($concursoObj->invitacion ?? []) : ($concursoObj->invitacion ?? null);
                @endphp
                <a href="{{ route('concursos.show', $concursoObj->id) }}" class="w-full hover:shadow-lg">
                    <div class="bg-white shadow-md rounded-lg overflow-hidden border border-gray-200">
                        <div class="bg-green-500 text-white px-4 py-2 flex justify-between items-center">
                            <h3 class="text-lg font-semibold">{{ $concursoObj->nombre ?? 'Sin nombre' }}</h3>
                            <span class="rounded bg-green-200 text-green-800 font-bold py-1 px-3 text-xs">
                                {{ $estado->estado_actual ?? 'Activo' }}
                            </span>
                        </div>
                        <div class="p-4 space-y-2">
                            <p class="text-gray-700 text-sm">{{ $concursoObj->descripcion ?? 'Sin descripción' }}</p>
                            <div class="flex items-center text-gray-600 text-sm">
                                <span class="font-bold mr-2">Número de Concurso:</span>
                                <span>{{ $concursoObj->numero ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600 text-sm">
                                <div>
                                    <span class="font-bold block">Inicio:</span>
                                    <span>
                                        @if(isset($concursoObj->fecha_inicio))
                                            {{ \Carbon\Carbon::parse($concursoObj->fecha_inicio)->format('d-m-Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </span>
                                </div>
                                <div>
                                    <span class="font-bold block">Cierre:</span>
                                    <span>
                                        @if(isset($concursoObj->fecha_cierre))
                                            {{ \Carbon\Carbon::parse($concursoObj->fecha_cierre)->format('d-m-Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </span>
                                </div>
                            </div>
                            @if($invitacion)
                            <div class="flex items-center text-gray-600 text-sm">
                                <span class="font-bold mr-2">Intención:</span>
                                <span>
                                    @switch($invitacion->intencion ?? 0)
                                        @case(0)
                                            Pregunta
                                            @break
                                        @case(1)
                                            Participa
                                            @break
                                        @case(2)
                                            No participa
                                            @break
                                        @case(3)
                                            Ofertó
                                            @break
                                        @default
                                            Pregunta
                                    @endswitch
                                </span>
                            </div>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
        
        <div class="titulo-index border-b">
            Concursos en Análisis y Terminados
        </div>
        <div class="container mx-auto grid grid-cols-1 md:grid-cols-3 gap-4">
            @if (count($invitaciones_finalizadas) == 0)
                <span class="px-10 font-bold italic">
                    No hay concursos en el historial
                </span>    
            @endif
            @foreach($invitaciones_finalizadas as $concurso)
                @php
                    $concursoObj = is_array($concurso) ? (object) $concurso : $concurso;
                    $estado = is_array($concursoObj->estado ?? null) ? (object) ($concursoObj->estado ?? []) : ($concursoObj->estado ?? null);
                    $invitacion = is_array($concursoObj->invitacion ?? null) ? (object) ($concursoObj->invitacion ?? []) : ($concursoObj->invitacion ?? null);
                @endphp
                <a href="{{ route('concursos.show', $concursoObj->id) }}" class="w-full hover:shadow-lg">
                    <div class="bg-white shadow-md rounded-lg overflow-hidden border border-gray-200">
                        <div class="bg-gray-500 text-white px-4 py-2 flex justify-between items-center">
                            <h3 class="text-lg font-semibold">{{ $concursoObj->nombre ?? 'Sin nombre' }}</h3>
                            <span class="rounded bg-gray-200 text-gray-800 font-bold py-1 px-3 text-xs">
                                {{ $estado->estado_actual ?? 'Finalizado' }}
                            </span>
                        </div>
                        <div class="p-4 space-y-2">
                            <p class="text-gray-700 text-sm">{{ $concursoObj->descripcion ?? 'Sin descripción' }}</p>
                            <div class="flex items-center text-gray-600 text-sm">
                                <span class="font-bold mr-2">Número de Concurso:</span>
                                <span>{{ $concursoObj->numero ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600 text-sm">
                                <div>
                                    <span class="font-bold block">Inicio:</span>
                                    <span>
                                        @if(isset($concursoObj->fecha_inicio))
                                            {{ \Carbon\Carbon::parse($concursoObj->fecha_inicio)->format('d-m-Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </span>
                                </div>
                                <div>
                                    <span class="font-bold block">Cierre:</span>
                                    <span>
                                        @if(isset($concursoObj->fecha_cierre))
                                            {{ \Carbon\Carbon::parse($concursoObj->fecha_cierre)->format('d-m-Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </span>
                                </div>
                            </div>
                            @if($invitacion)
                            <div class="flex items-center text-gray-600 text-sm">
                                <span class="font-bold mr-2">Intención:</span>
                                <span>
                                    @switch($invitacion->intencion ?? 0)
                                        @case(0)
                                            Pregunta
                                            @break
                                        @case(1)
                                            Participa
                                            @break
                                        @case(2)
                                            No participa
                                            @break
                                        @case(3)
                                            Ofertó
                                            @break
                                        @default
                                            Pregunta
                                    @endswitch
                                </span>
                            </div>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>