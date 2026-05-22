<x-app-layout>
    <div class="w-full xl:w-10/12 mb-12 xl:mb-0 px-4 mx-auto mt-4 pb-10">

        @if (session('success'))
            <div x-data="{ open: true }" x-show="open" class="mb-4 rounded-lg border border-green-300 bg-green-50 p-5">
                <div class="flex items-start gap-4">
                    <div class="shrink-0 mt-0.5">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-base font-semibold text-green-800">{{ session('success') }}</p>
                        <p class="mt-1 text-sm text-green-700">
                            El documento fue recibido correctamente y será revisado por el área de Legales. Una vez que sea aprobado, aparecerá en su legajo. Este proceso puede demorar algunos días hábiles.
                        </p>
                    </div>
                    <button @click="open = false" class="shrink-0 text-green-500 hover:text-green-800 text-xl font-bold leading-none" aria-label="Cerrar">&times;</button>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ open: true }" x-show="open" class="mb-4 rounded-lg border border-red-300 bg-red-50 p-5">
                <div class="flex items-start gap-4">
                    <div class="shrink-0 mt-0.5">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-base font-semibold text-red-800">{{ session('error') }}</p>
                        <p class="mt-1 text-sm text-red-700">
                            Por favor intente nuevamente. Si el problema persiste, comuníquese con el área de Compras.
                        </p>
                    </div>
                    <button @click="open = false" class="shrink-0 text-red-500 hover:text-red-800 text-xl font-bold leading-none" aria-label="Cerrar">&times;</button>
                </div>
            </div>
        @endif
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
                                    {{-- @livewire('subir-archivo-general', ['proveedor_id' => $proveedor->id]) --}}
                                    <div x-data="{ openModal: false }">
                                        <button type="button" @click="openModal = true" class="link-azul">
                                            Nuevo Documento
                                        </button>

                                        <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
                                            <div class="flex items-center justify-center min-h-screen px-4">
                                                <div class="fixed inset-0 bg-gray-500 opacity-75"></div>

                                                <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full p-6">
                                                    <h3 class="text-lg font-medium mb-4">Subir Documento General</h3>
                                                    
                                                    <form action="{{ route('proveedor.subir.documento') }}" method="POST" enctype="multipart/form-data">
                                                        @csrf
                                                        <div class="space-y-4">
                                                            <div>
                                                                <label>Archivo PDF</label>
                                                                <input type="file" name="file" class="block w-full border" required>
                                                            </div>
                                                            
                                                            <div>
                                                                <label>Tipo de Documento</label>
                                                                <select name="documento_tipo_id" class="block w-full border" required>
                                                                    <option value="">Seleccione...</option>
                                                                    @foreach($tipos_documentos as $tipo)
                                                                        <option value="{{ $tipo['id'] }}">{{ $tipo['nombre'] }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <div>
                                                                <label>Vencimiento</label>
                                                                <input type="date" name="vencimiento" class="block w-full border">
                                                            </div>
                                                        </div>

                                                        <div class="mt-6 flex justify-end gap-2">
                                                            <button type="button" @click="openModal = false" class="bg-gray-300 px-4 py-2 rounded">Cancelar</button>
                                                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Subir ahora</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                                                <div>
                                                    <span class="font-semibold text-gray-700">{{ $doc->nombre }}</span>
                                                </div>
                                                @if ($doc->vencimiento)
                                                    <div class="text-sm text-gray-500 mt-1">
                                                        Vencimiento:
                                                        {{ \Carbon\Carbon::parse($doc->vencimiento)->format('d/m/Y') }}
                                                        @if (\Carbon\Carbon::parse($doc->vencimiento)->isPast())
                                                            <span class="text-red-600 font-medium">(Vencido)</span>
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
                                    <div x-data="{ openApoderado: false, tipo: 'apoderado' }">
                                        <button type="button" @click="openApoderado = true" class="link-azul text-sm">
                                            Nuevo Apoderado
                                        </button>

                                        <div x-show="openApoderado" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-cloak>
                                            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6" @click.away="openApoderado = false">
                                                <h3 class="text-lg font-bold mb-4">Cargar Nuevo Apoderado / Representante</h3>

                                                <form action="{{ route('proveedor.subir.apoderado') }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <div class="space-y-4">
                                                        <div>
                                                            <label class="block text-sm font-medium">Archivo PDF</label>
                                                            <input type="file" name="file" class="w-full border p-2 rounded" required accept=".pdf">
                                                        </div>

                                                        <div>
                                                            <label class="block text-sm font-medium">Tipo</label>
                                                            <select name="tipo" x-model="tipo" class="w-full border p-2 rounded" required>
                                                                <option value="apoderado">Apoderado</option>
                                                                <option value="representante">Representante Legal</option>
                                                            </select>
                                                        </div>

                                                        <div x-show="tipo === 'representante'">
                                                            <label class="block text-sm font-medium">Nombre Completo</label>
                                                            <input type="text" name="nombre" class="w-full border p-2 rounded" :required="tipo === 'representante'">
                                                        </div>

                                                        <div>
                                                            <label class="block text-sm font-medium">Vencimiento</label>
                                                            <input type="date" name="vencimiento" class="w-full border p-2 rounded">
                                                        </div>
                                                    </div>

                                                    <div class="mt-6 flex justify-end space-x-3">
                                                        <button type="button" @click="openApoderado = false" class="px-4 py-2 bg-gray-200 rounded">Cancelar</button>
                                                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Cargar Documento</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
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
                                                    <span class="font-semibold">{{ $rep->nombre ?? 'Sin nombre' }}</span>
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
