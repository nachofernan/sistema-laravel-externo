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
                                @php $td_id = 0; @endphp
                                @foreach ($documentos as $documento)
                                    @php
                                        $doc = is_array($documento) ? (object) $documento : $documento;
                                        $docTipo = is_array($doc->documento_tipo ?? null)
                                            ? (object) $doc->documento_tipo
                                            : $doc->documento_tipo ?? null;
                                    @endphp
                                    @if ($docTipo && $docTipo->id != $td_id)
                                        <div class="bg-white shadow rounded-lg p-4 mb-2">
                                            <div class="flex justify-between items-center">
                                                <span class="font-semibold text-gray-700">{{ $docTipo->nombre }}</span>
                                                <div class="flex space-x-2">
                                                    <form action="{{ route('file.download') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="disk" value="proveedores">
                                                        <input type="hidden" name="fileName"
                                                            value="{{ $doc->file_storage }}">
                                                        <button type="submit"
                                                            class="text-blue-600 hover:text-blue-800 text-sm">Descargar</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        @php $td_id = $docTipo->id; @endphp
                                    @endif
                                @endforeach
                            @endif
                        </div>
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
                                @foreach ($apoderados as $apoderado)
                                    @php
                                        $apo = is_array($apoderado) ? (object) $apoderado : $apoderado;
                                        $documentosApo = is_array($apo->documentos ?? [])
                                            ? collect($apo->documentos)
                                            : collect($apo->documentos ?? []);
                                    @endphp
                                    @if ($documentosApo->count() > 0)
                                        <div class="bg-white shadow rounded-lg p-4 mb-2">
                                            @foreach ($documentosApo as $documento)
                                                @php $doc = is_array($documento) ? (object) $documento : $documento; @endphp
                                                <div
                                                    class="flex justify-between items-center border-b last:border-b-0">
                                                    <span class="font-semibold text-gray-t00">
                                                        {{ ucfirst($apo->tipo) }}
                                                        @if ($apo->nombre)
                                                            - {{ $apo->nombre }}
                                                        @endif
                                                    </span>
                                                    <div class="flex space-x-2">
                                                        <form action="{{ route('file.download') }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="disk" value="proveedores">
                                                            <input type="hidden" name="fileName"
                                                                value="{{ $doc->file_storage }}">
                                                            <button type="submit"
                                                                class="text-blue-600 hover:text-blue-800 text-sm">Descargar</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <div class="col">

                        {{-- ✅ RUBROS - Compatible con datos de API --}}
                        @livewire('rubros-edit', ['proveedor' => $proveedor], key($proveedor->id . microtime(true)))
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ Agregar notificaciones para Livewire --}}
</x-app-layout>
