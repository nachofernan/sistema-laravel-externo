<x-app-layout>
    <div class="w-full xl:w-10/12 mb-12 xl:mb-0 px-4 mx-auto mt-4 pb-10">
        <div
            class="relative flex flex-col min-w-0 break-words bg-white w-full mb-6 shadow-lg rounded-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-700 to-blue-400 p-6">
                <div class="text-2xl font-bold text-white">
                    {{ $proveedor->razonsocial }}
                </div>
                <div class="text-blue-100">
                    CUIT: {{ $proveedor->cuit }}
                </div>
            </div>
            <div class="block w-full overflow-x-auto py-5 px-5">
                <div class="grid grid-cols-2 gap-5">
                    <div class="col">
                        <h2 class="text-xl font-semibold border-b pb-2 mb-4 text-gray-700">Datos Generales</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Razón Social</span>
                                <span class="font-medium">{{ $proveedor->razonsocial }}</span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Correo Institucional</span>
                                <span class="font-medium">{{ $proveedor->correo }}</span>
                            </div>
                            @if (isset($proveedor->telefono) && $proveedor->telefono)
                                <div class="flex justify-between border-b pb-2">
                                    <span class="text-gray-600">Teléfono</span>
                                    <span class="font-medium">{{ $proveedor->telefono }}</span>
                                </div>
                            @endif
                            @if (isset($proveedor->webpage) && $proveedor->webpage)
                                <div class="flex justify-between border-b pb-2">
                                    <span class="text-gray-600">Sitio Web</span>
                                    <a href="{{ $proveedor->webpage }}" target="_blank"
                                        class="text-blue-600 hover:underline">
                                        {{ $proveedor->webpage }}
                                    </a>
                                </div>
                            @endif
                        </div>

                        {{-- ✅ DOCUMENTACIÓN - Compatible con datos de API --}}
                        <div class="p-6 bg-gray-100 border-t mt-6">
                            <h2 class="text-xl mb-4 text-gray-700 flex justify-between items-center">
                                <div class="font-semibold">Documentación</div>
                                <div>
                                    @livewire('subir-archivo-general', ['proveedor_id' => $proveedor->id])
                                </div>
                            </h2>

                            @php
                                $documentos = is_array($proveedor->documentos)
                                    ? collect($proveedor->documentos)
                                    : collect($proveedor->documentos ?? []);
                            @endphp

                            @if ($documentos->count() == 0)
                                <div class="text-gray-500 italic">No existen documentos asociados al proveedor</div>
                            @else
                                @foreach ($documentos as $documento)
                                    @php
                                        $doc = is_array($documento) ? (object) $documento : $documento;
                                    @endphp
                                    <div class="bg-white shadow rounded-lg p-4 mb-2">
                                        <div class="flex justify-between items-center">
                                            <div class="flex-1">
                                                <span class="font-semibold text-gray-700">{{ $doc->nombre }}</span>
                                                @if ($doc->vencimiento)
                                                    <div class="text-sm text-gray-500 mt-1">
                                                        Vencimiento:
                                                        {{ \Carbon\Carbon::parse($doc->vencimiento)->format('d/m/Y') }}
                                                        @if (\Carbon\Carbon::parse($doc->vencimiento)->isPast())
                                                            <span class="text-red-600 font-medium">(Vencido)</span>
                                                        @elseif (\Carbon\Carbon::parse($doc->vencimiento)->diffInDays(now()) <= 30)
                                                            <span class="text-orange-600 font-medium">(Por
                                                                vencer)</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex space-x-2">
                                                <form action="{{ route('file.download-proveedor-documento') }}"
                                                    method="POST">
                                                    @csrf
                                                    <input type="hidden" name="documento_id"
                                                        value="{{ $doc->id }}">
                                                    <button type="submit"
                                                        class="text-blue-600 hover:text-blue-800 text-sm">Descargar</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>


                        {{-- ✅ APODERADOS - Compatible con datos de API --}}
                        <div class="p-6 bg-gray-100 border-t mt-6">
                            <h2 class="text-xl mb-4 text-gray-700 flex justify-between items-center">
                                <div class="font-semibold">Representantes Legales/Apoderados</div>
                                <div>
                                    @livewire('subir-archivo-apoderado', ['proveedor_id' => $proveedor->id])
                                </div>
                            </h2>

                            @php
                                $apoderados = is_array($proveedor->apoderados)
                                    ? collect($proveedor->apoderados)
                                    : collect($proveedor->apoderados ?? []);
                            @endphp

                            @if ($apoderados->count() == 0)
                                <div class="text-gray-500 italic">No existen apoderados asociados al proveedor</div>
                            @else
                                @php
                                    $apoderadosCollection = $apoderados->map(fn($a) => is_array($a) ? (object) $a : $a);
                                    $representantes = $apoderadosCollection->where('tipo', 'representante')->values();
                                    $apoderadosSimples = $apoderadosCollection->where('tipo', 'apoderado')->values();
                                @endphp

                                {{-- Representantes Legales --}}
                                @if ($representantes->count())
                                    <div class="mb-4">
                                        <div class="font-bold text-gray-700 mb-2">Representantes Legales</div>
                                        @foreach ($representantes as $rep)
                                            <div
                                                class="bg-white shadow rounded-lg p-4 mb-2 flex justify-between items-center">
                                                <div>
                                                    <span
                                                        class="font-semibold">{{ $rep->nombre ?? 'Sin nombre' }}</span>
                                                </div>
                                                <form action="{{ route('file.download-proveedor-documento') }}"
                                                    method="POST">
                                                    @csrf
                                                    <input type="hidden" name="documento_id"
                                                        value="{{ $rep->id }}">
                                                    <button type="submit"
                                                        class="text-blue-600 hover:text-blue-800 text-sm">Descargar</button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Apoderados --}}
                                @if ($apoderadosSimples->count())
                                    <div>
                                        <div class="font-bold text-gray-700 mb-2">Apoderados</div>
                                        @foreach ($apoderadosSimples as $apo)
                                            <div
                                                class="bg-white shadow rounded-lg p-4 mb-2 flex justify-between items-center">
                                                <div>
                                                    <span class="font-semibold">Apoderado</span>
                                                </div>
                                                <form action="{{ route('file.download-proveedor-documento') }}"
                                                    method="POST">
                                                    @csrf
                                                    <input type="hidden" name="documento_id"
                                                        value="{{ $apo->id }}">
                                                    <button type="submit"
                                                        class="text-blue-600 hover:text-blue-800 text-sm">Descargar</button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    <div class="col">
                        {{-- ✅ CONTACTOS - Compatible con datos de API --}}
                        @php
                            $contactos = is_array($proveedor->contactos)
                                ? collect($proveedor->contactos)
                                : collect($proveedor->contactos ?? []);
                        @endphp

                        @if ($contactos->count() > 0)
                            <div class="subtitulo-show">
                                Contactos
                            </div>
                            @foreach ($contactos as $key => $contacto)
                                @php $cont = is_array($contacto) ? (object) $contacto : $contacto; @endphp
                                <div class="grid-datos-show gap-y-2 {{ $key ? 'pt-2 mt-2 border-t' : '' }}">
                                    <div class="atributo-show">
                                        Nombre
                                    </div>
                                    <div class="valor-show grid grid-cols-10">
                                        <div class="col-span-8">
                                            {{ $cont->nombre }}
                                        </div>
                                    </div>
                                    <div class="atributo-show">
                                        Teléfono
                                    </div>
                                    <div class="valor-show">
                                        {{ $cont->telefono }}
                                    </div>
                                    <div class="atributo-show">
                                        Correo
                                    </div>
                                    <div class="valor-show">
                                        {{ $cont->correo }}
                                    </div>
                                </div>
                            @endforeach
                            <div class="pt-4">&nbsp;</div>
                        @endif

                        {{-- ✅ DIRECCIONES - Compatible con datos de API --}}
                        @php
                            $direcciones = is_array($proveedor->direcciones)
                                ? collect($proveedor->direcciones)
                                : collect($proveedor->direcciones ?? []);
                        @endphp

                        @if ($direcciones->count() > 0)
                            <div class="subtitulo-show">
                                Direcciones
                            </div>
                            @foreach ($direcciones as $key => $direccion)
                                @php $dir = is_array($direccion) ? (object) $direccion : $direccion; @endphp
                                <div class="grid-datos-show gap-y-2 {{ $key ? 'pt-2 mt-2 border-t' : '' }}">
                                    <div class="atributo-show">
                                        Tipo
                                    </div>
                                    <div class="valor-show grid grid-cols-10">
                                        <div class="col-span-8">
                                            {{ $dir->tipo }}
                                        </div>
                                    </div>
                                    <div class="atributo-show">
                                        Dirección
                                    </div>
                                    <div class="valor-show">
                                        {{ $dir->calle }} #{{ $dir->altura }}, {{ $dir->piso }},
                                        {{ $dir->departamento }}
                                    </div>
                                    <div class="atributo-show">
                                    </div>
                                    <div class="valor-show">
                                        {{ $dir->ciudad }} ({{ $dir->codigopostal }}),
                                        {{ $dir->provincia }}, {{ $dir->pais }}
                                    </div>
                                </div>
                            @endforeach
                            <div class="pt-4">&nbsp;</div>
                        @endif
                        {{-- ✅ RUBROS - Compatible con datos de API --}}
                        @livewire('rubros-edit', ['proveedor' => $proveedor], key($proveedor->id . microtime(true)))
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ Agregar notificaciones para Livewire --}}
</x-app-layout>
