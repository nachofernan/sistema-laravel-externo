<x-app-layout>
    @php
        $concursoObj = is_array($concurso) ? (object) $concurso : $concurso;
        $estado = is_array($concursoObj->estado ?? null) ? (object) ($concursoObj->estado ?? []) : ($concursoObj->estado ?? null);
        $invitacion = is_array($concursoObj->invitacion ?? null) ? (object) ($concursoObj->invitacion ?? []) : ($concursoObj->invitacion ?? null);
        $contactos = is_array($concursoObj->contactos ?? null) ? collect($concursoObj->contactos) : collect($concursoObj->contactos ?? []);
        $documentos = is_array($concursoObj->documentos ?? null) ? collect($concursoObj->documentos) : collect($concursoObj->documentos ?? []);
        $documentosRequeridos = is_array($concursoObj->documentos_requeridos ?? null) ? collect($concursoObj->documentos_requeridos) : collect($concursoObj->documentos_requeridos ?? []);
        $prorrogas = is_array($concursoObj->prorrogas ?? null) ? collect($concursoObj->prorrogas) : collect($concursoObj->prorrogas ?? []);
        $sedes = is_array($concursoObj->sedes ?? null) ? collect($concursoObj->sedes) : collect($concursoObj->sedes ?? []);
    @endphp
    
    <div class="w-full xl:w-10/12 mb-12 xl:mb-0 px-4 mx-auto pb-10 mt-4" 
         x-data="{}" 
         @documento-subido.window="window.location.reload()"
         @documento-eliminado.window="window.location.reload()"
         @oferta-dada-baja.window="window.location.reload()">
        
        {{-- Notificaciones --}}
        @if (session('success'))
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4 text-green-900 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4 text-red-900 rounded">
                {{ session('error') }}
            </div>
        @endif
        
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="flex justify-between border-b text-white font-bold bg-gradient-to-r p-6
                @if (isset($estado) && $estado->estado_actual == 'activo')
                    from-green-700 to-green-400
                @elseif (isset($estado) && $estado->estado_actual == 'abierto')
                    from-blue-700 to-blue-400
                @else
                    from-gray-700 to-gray-400
                @endif">
                <div>
                    <div class="text-2xl font-bold text-white">
                        {{ $concursoObj->nombre ?? 'Sin nombre' }}
                    </div>
                    <div class="font-normal">
                        #{{ $concursoObj->numero ?? 'Sin Número' }} - 
                        {{ $estado->estado_actual ?? 'Estado no definido' }}
                    </div>
                </div>
                <div class="flex items-center">
                    @if($invitacion)
                        @livewire('concursos.action-modal', ['concurso' => $concursoObj, 'invitacion' => $invitacion], key('action-modal-' . $concursoObj->id))
                    @endif
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid md:grid-cols-2 gap-8 p-6">
                <!-- General Information Column -->
                <div>
                    <!-- Datos Generales Card -->
                    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
                        <div class="bg-gray-100 p-4 text-lg flex justify-between items-center">
                            <h2 class="font-medium text-gray-700" id="datos-generales">
                                Datos Generales
                            </h2>
                        </div>

                        <div class="p-6">
                            <dl class="space-y-4 text-sm" aria-labelledby="datos-generales">
                                <div class="flex justify-between border-b pb-2">
                                    <dt class="font-medium text-gray-600">Nombre</dt>
                                    <dd class="text-gray-800">{{ $concursoObj->nombre ?? 'Sin nombre' }}</dd>
                                </div>

                                <div class="flex justify-between border-b pb-2">
                                    <dt class="font-medium text-gray-600">Número</dt>
                                    <dd class="text-gray-800">#{{ $concursoObj->numero ?? 'Sin Número' }}</dd>
                                </div>

                                <div class="flex justify-between border-b pb-2">
                                    <dt class="font-medium text-gray-600">Descripción</dt>
                                    <dd class="text-gray-800 text-right">{{ $concursoObj->descripcion ?? 'Sin descripción' }}</dd>
                                </div>

                                @if ($sedes->count() > 0)
                                    <div class="flex justify-between border-b pb-2">
                                        <dt class="font-medium text-gray-600">Sedes</dt>
                                        <dd class="text-right">
                                            @foreach ($sedes as $sede)
                                                @php $sedeObj = is_array($sede) ? (object) $sede : $sede; @endphp
                                                <div>{{ $sedeObj->nombre ?? 'Sede' }}</div>
                                            @endforeach
                                        </dd>
                                    </div>
                                @endif

                                @if (isset($concursoObj->fecha_inicio))
                                    <div class="flex justify-between border-b pb-2">
                                        <dt class="font-medium text-gray-600">Fecha Inicio</dt>
                                        <dd class="text-gray-800">
                                            {{ \Carbon\Carbon::parse($concursoObj->fecha_inicio)->format('d-m-Y - H:i') }}
                                        </dd>
                                    </div>
                                @endif

                                @if (isset($concursoObj->fecha_cierre))
                                    <div class="flex justify-between border-b pb-2">
                                        <dt class="font-medium text-gray-600">Fecha Cierre</dt>
                                        <dd class="text-gray-800">
                                            {{ \Carbon\Carbon::parse($concursoObj->fecha_cierre)->format('d-m-Y - H:i') }}
                                        </dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        @if ($prorrogas->count() > 0)
                            <div class="bg-gray-50 px-2 py-4">
                                <div class="mt-4 space-y-2 bg-white rounded-md shadow-sm py-2 text-xs">
                                    @foreach ($prorrogas as $key => $prorroga)
                                        @php $prorrogaObj = is_array($prorroga) ? (object) $prorroga : $prorroga; @endphp
                                        <div class="flex justify-between items-center px-4">
                                            <span class="font-medium">Prórroga {{ $key + 1 }}</span>
                                            <div class="text-xs text-gray-600">
                                                {{ \Carbon\Carbon::parse($prorrogaObj->fecha_anterior)->format('d-m-Y - H:i') }}
                                                <span class="mx-2">➔</span>
                                                {{ \Carbon\Carbon::parse($prorrogaObj->fecha_actual)->format('d-m-Y - H:i') }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Contactos Card -->
                    @if ($contactos->count() > 0)
                        <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
                            <div class="bg-gray-100 p-4 text-lg">
                                <h2 class="font-medium text-gray-700">Contactos</h2>
                            </div>
                            <div class="p-6">
                                @foreach ($contactos as $contacto)
                                    @php $contactoObj = is_array($contacto) ? (object) $contacto : $contacto; @endphp
                                    <div class="border-b pb-2 mb-2 last:border-b-0 last:mb-0">
                                        <div class="font-medium">
                                            {{ $contactoObj->nombre ?? 'Sin nombre' }} - 
                                            <span class="text-gray-600 text-sm">
                                                {{ $contactoObj->tipo == 'administrativo' ? 'Administrativo' : 'Técnico' }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-600">
                                            {{ $contactoObj->correo ?? 'Sin correo' }} - {{ $contactoObj->telefono ?? 'Sin teléfono' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Documentación Adjunta Card -->
                    @if ($documentos->count() > 0)
                        <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
                            <div class="bg-gray-100 p-4 text-lg">
                                <h2 class="font-medium text-gray-700">Documentación Adjunta al Concurso</h2>
                            </div>
                            <div class="p-6">
                                <div class="space-y-3">
                                    @foreach ($documentos as $documento)
                                        @php 
                                            $documentoObj = is_array($documento) ? (object) $documento : $documento;
                                            $documentoTipo = is_array($documentoObj->documento_tipo ?? null) ? (object) ($documentoObj->documento_tipo ?? []) : ($documentoObj->documento_tipo ?? null);
                                        @endphp
                                        <div class="bg-gray-50 p-3 rounded-lg flex justify-between items-center">
                                            <div>
                                                <div class="font-medium">{{ $documentoTipo->nombre ?? 'Documento' }}</div>
                                                <div class="text-xs text-gray-500">
                                                    Cargado el {{ \Carbon\Carbon::parse($documentoObj->created_at)->format('d-m-Y') }}
                                                </div>
                                            </div>
                                            <form action="{{ route('file.download-concurso-documento') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="concurso_id" value="{{ $concursoObj->id }}">
                                                <input type="hidden" name="documento_id" value="{{ $documentoObj->media_id }}">
                                                <button type="submit" class="text-blue-600 text-sm hover:underline">Descargar</button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Required Documentation Column -->
                <div class="space-y-4">
                    <div class="bg-white shadow-md rounded-lg overflow-hidden">
                        <div class="bg-gray-100 p-4 text-lg">
                            <h2 class="font-medium text-gray-700">
                                Documentación Requerida para Participar
                            </h2>
                        </div>
                        <div class="px-6 py-2">
                            @if ($documentosRequeridos->count() > 0)
                                <div class="space-y-2">
                                    
                                    {{-- Documentos de Oferta (solo si la invitación está aceptada) --}}
                                    @php
                                        $invitacionObj = is_array($invitacion) ? (object) $invitacion : $invitacion;
                                        $puedeSubirOferta = isset($invitacionObj->intencion) && in_array($invitacionObj->intencion, [1, 3]);
                                        
                                        // Lógica para determinar si puede cargar archivos
                                        $concursoActivo = isset($estado) && $estado->estado_actual == 'activo';
                                        $antesDelCierre = isset($concursoObj->fecha_cierre) && \Carbon\Carbon::parse($concursoObj->fecha_cierre)->isFuture();
                                        $concursoAnalisis = isset($estado) && $estado->estado_actual == 'analisis';
                                        $permiteCarga = isset($concursoObj->permite_carga) && ($concursoObj->permite_carga == true || $concursoObj->permite_carga == 1);
                                        
                                        // Para documentos de oferta: solo si está activo y antes del cierre
                                        $puedeCargarOferta = $concursoActivo && $antesDelCierre;
                                        
                                        // Para documentos adicionales: si está activo y antes del cierre, O si está en análisis y permite carga
                                        $puedeCargarAdicionales = ($concursoActivo && $antesDelCierre) || ($concursoAnalisis && $permiteCarga);
                                    @endphp
                                    
                                    @if ($puedeSubirOferta)
                                        {{-- Tipos de Documentos de Oferta --}}
                                        @php
                                            $tiposDocumentosOferta = isset($concursoObj->tipos_documentos_oferta) ? collect($concursoObj->tipos_documentos_oferta) : collect([]);
                                            $concursoActivo = isset($estado) && $estado->estado_actual == 'activo';
                                            $antesDelCierre = isset($concursoObj->fecha_cierre) && \Carbon\Carbon::parse($concursoObj->fecha_cierre)->isFuture();
                                            $puedeEliminar = $concursoActivo && $antesDelCierre;
                                        @endphp
                                        
                                        @if ($tiposDocumentosOferta && $tiposDocumentosOferta->count() > 0)
                                            @foreach ($tiposDocumentosOferta as $index => $tipoDocumentoOferta)
                                                @php 
                                                    $tipoDocOfertaObj = is_array($tipoDocumentoOferta) ? (object) $tipoDocumentoOferta : $tipoDocumentoOferta;
                                                    $documentosOferta = is_array($tipoDocOfertaObj->documentos_oferta ?? null) ? collect($tipoDocOfertaObj->documentos_oferta) : collect($tipoDocOfertaObj->documentos_oferta ?? []);
                                                    $tipoDocumentoProveedor = is_array($tipoDocOfertaObj->tipo_documento_proveedor ?? null) ? (object) ($tipoDocOfertaObj->tipo_documento_proveedor ?? []) : ($tipoDocOfertaObj->tipo_documento_proveedor ?? null);
                                                    // Si la invitación es 3, el documento es obligatorio y hay solo un archivo cargado, no se puede eliminar
                                                    if (
                                                        isset($invitacionObj->intencion) && $invitacionObj->intencion == 3 &&
                                                        $tipoDocOfertaObj->obligatorio &&
                                                        $documentosOferta->count() === 1
                                                        )
                                                    {
                                                        $puedeEliminar = false;
                                                    }

                                                @endphp
                                                
                                                <div class="py-4 {{ $index > 0 ? 'border-t border-gray-200' : '' }}">
                                                    <div class="flex justify-between items-start mb-3">
                                                        <div class="flex-1">
                                                            <h3 class="font-semibold text-gray-800 text-lg">
                                                                {{ $tipoDocOfertaObj->nombre ?? 'Documento de Oferta' }}
                                                                @if (isset($tipoDocOfertaObj->obligatorio) && $tipoDocOfertaObj->obligatorio)
                                                                    <span class="text-red-600 text-sm">(Obligatorio)</span>
                                                                @endif
                                                            </h3>
                                                            
                                                            @if ($tipoDocOfertaObj->descripcion)
                                                                <p class="text-gray-600 text-sm mt-1">
                                                                    {{ $tipoDocOfertaObj->descripcion }}
                                                                </p>
                                                            @endif
                                                        </div>
                                                        
                                                        <div class="ml-4">
                                                            @if ($puedeCargarOferta)
                                                                @livewire('concursos.subir-archivo', [
                                                                    'concurso' => $concursoObj, 
                                                                    'invitacion' => $invitacion, 
                                                                    'documento' => $tipoDocOfertaObj
                                                                ], key('subir-archivo-oferta-' . $tipoDocOfertaObj->id))
                                                            @else
                                                                <div class="text-gray-400 text-sm">
                                                                    Carga inactiva
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    
                                                    {{-- Información del tipo de documento del proveedor si existe --}}
                                                    @if ($tipoDocumentoProveedor)
                                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3 text-xs">
                                                            <div class="flex justify-between items-center">
                                                                <div class="flex-1">
                                                                    <div class="font-medium text-blue-800">
                                                                        Documento Asociado: {{ $tipoDocumentoProveedor->nombre ?? 'Sin nombre' }}
                                                                    </div>
                                                                    @if (isset($tipoDocumentoProveedor->fecha_vencimiento) && $tipoDocumentoProveedor->fecha_vencimiento)
                                                                        <div class="text-blue-700 text-xs mt-1">
                                                                            <span class="font-medium">Vencimiento:</span> 
                                                                            {{ \Carbon\Carbon::parse($tipoDocumentoProveedor->fecha_vencimiento)->format('d-m-Y') }}
                                                                            @if (\Carbon\Carbon::parse($concursoObj->fecha_cierre)->greaterThan(\Carbon\Carbon::parse($tipoDocumentoProveedor->fecha_vencimiento)))
                                                                                <span class="text-red-600 font-medium"> (Vencido al cierre)</span>
                                                                            @endif
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                
                                                                {{-- Botón de descarga del documento del proveedor --}}
                                                                @if ($tipoDocumentoProveedor->id)
                                                                <div class="ml-4">
                                                                    <form action="{{ route('file.download-proveedor-documento') }}" method="POST" class="inline">
                                                                        @csrf
                                                                        <input type="hidden" name="documento_id" value="{{ $tipoDocumentoProveedor->id }}">
                                                                        <button type="submit" class="text-blue-600 hover:underline text-xs">
                                                                            Descargar
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif
                                                    
                                                    {{-- Documentos de oferta ya cargados --}}
                                                    @if ($documentosOferta->count() > 0)
                                                        <div class="space-y-2">
                                                            @foreach ($documentosOferta as $documentoOferta)
                                                                @php $docOfertaObj = is_array($documentoOferta) ? (object) $documentoOferta : $documentoOferta; @endphp
                                                                <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                                                    <div class="flex items-center justify-between">
                                                                        <div class="flex items-center space-x-2">
                                                                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                                            </svg>
                                                                            <div>
                                                                                <div class="text-sm font-medium text-green-800">
                                                                                    {{ 
                                                                                        mb_strlen($docOfertaObj->archivo) > 25 
                                                                                                ? mb_substr($docOfertaObj->archivo, 0, 22) . '...' 
                                                                                                : $docOfertaObj->archivo
                                                                                    }}
                                                                                </div>
                                                                                <div class="text-xs text-gray-500">
                                                                                    {{ \Carbon\Carbon::parse($docOfertaObj->created_at)->format('d-m-Y H:i') }}
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="flex items-center space-x-2">
                                                                            <form action="{{ route('file.download-concurso-documento') }}" method="POST" class="inline">
                                                                                @csrf
                                                                                <input type="hidden" name="concurso_id" value="{{ $concursoObj->id }}">
                                                                                <input type="hidden" name="documento_id" value="{{ $docOfertaObj->media_id }}">
                                                                                <button type="submit" class="text-blue-600 hover:underline text-xs">Descargar</button>
                                                                            </form>
                                                                            @if ($puedeEliminar)
                                                                                @livewire('concursos.eliminar-archivo', [
                                                                                    'documento' => $docOfertaObj,
                                                                                    'concurso' => $concursoObj,
                                                                                    'invitacion' => $invitacion
                                                                                ], key('eliminar-archivo-oferta-' . $docOfertaObj->id))
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                    @if (isset($docOfertaObj->comentarios) && $docOfertaObj->comentarios)
                                                                        <div class="mt-2 text-xs text-gray-600">
                                                                            <span class="font-medium">Comentarios:</span> {{ $docOfertaObj->comentarios }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    @if ($documentosOferta->count() == 0 && $tipoDocumentoProveedor == null)
                                                        <div class="bg-gray-100 border border-gray-300 rounded-lg p-4 text-gray-600 text-sm text-center">
                                                            No hay documentos cargados en esta categoría
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        
                                            {{-- Documentos Adicionales --}}
                                            @php
                                                $documentosAdicionales = isset($concursoObj->documentos_adicionales) ? collect($concursoObj->documentos_adicionales) : collect([]);
                                                $documentosProveedor = collect($documentosAdicionales->get('documentos_proveedor', []));
                                                $documentosEmpresa = collect($documentosAdicionales->get('documentos_empresa', []));
                                                $totalProveedor = $documentosAdicionales->get('total_proveedor', 0);
                                                $totalEmpresa = $documentosAdicionales->get('total_empresa', 0);
                                            @endphp
                                            
                                            <div class="py-4 border-t border-gray-200">
                                                <div class="flex justify-between items-start mb-3">
                                                    <div class="flex-1">
                                                        <h3 class="font-semibold text-gray-800 text-lg">
                                                            Documentos Adicionales
                                                        </h3>
                                                        <p class="text-gray-600 text-sm mt-1">
                                                            Documentos complementarios para su oferta
                                                        </p>
                                                    </div>
                                                    
                                                    <div class="ml-4">
                                                        @if ($puedeCargarAdicionales)
                                                            @livewire('concursos.subir-archivo', [
                                                                'concurso' => $concursoObj, 
                                                                'invitacion' => $invitacion, 
                                                                'documento' => null
                                                            ], key('subir-archivo-adicional'))
                                                        @else
                                                            <div class="text-gray-400 text-sm">
                                                                Carga inactiva
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                {{-- Documentos del Proveedor --}}
                                                @if ($documentosProveedor->count() > 0)
                                                    <div class="mb-4">
                                                        <h4 class="font-medium text-gray-700 text-sm mb-2">Documentos subidos por usted ({{ $totalProveedor }})</h4>
                                                        <div class="space-y-2">
                                                            @foreach ($documentosProveedor as $documentoAdicional)
                                                                @php $docAdicionalObj = is_array($documentoAdicional) ? (object) $documentoAdicional : $documentoAdicional; @endphp
                                                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                                                    <div class="flex items-center justify-between">
                                                                        <div class="flex items-center space-x-2">
                                                                            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                                            </svg>
                                                                            <div>
                                                                                <div class="text-sm font-medium text-blue-800">
                                                                                    {{ 
                                                                                        mb_strlen($docAdicionalObj->archivo) > 25 
                                                                                                ? mb_substr($docAdicionalObj->archivo, 0, 22) . '...' 
                                                                                                : $docAdicionalObj->archivo
                                                                                    }}
                                                                                </div>
                                                                                <div class="text-xs text-gray-500">
                                                                                    {{ \Carbon\Carbon::parse($docAdicionalObj->created_at)->format('d-m-Y H:i') }}
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="flex items-center space-x-2">
                                                                            <form action="{{ route('file.download-concurso-documento') }}" method="POST" class="inline">
                                                                                @csrf
                                                                                <input type="hidden" name="concurso_id" value="{{ $concursoObj->id }}">
                                                                                <input type="hidden" name="documento_id" value="{{ $docAdicionalObj->media_id }}">
                                                                                <button type="submit" class="text-blue-600 hover:underline text-xs">Descargar</button>
                                                                            </form>
                                                                            @if ($puedeEliminar)
                                                                                @livewire('concursos.eliminar-archivo', [
                                                                                    'documento' => $docAdicionalObj,
                                                                                    'concurso' => $concursoObj,
                                                                                    'invitacion' => $invitacion
                                                                                ], key('eliminar-archivo-adicional-' . $docAdicionalObj->id))
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                    @if (isset($docAdicionalObj->comentarios) && $docAdicionalObj->comentarios)
                                                                        <div class="mt-2 text-xs text-gray-600">
                                                                            <span class="font-medium">Comentarios:</span> {{ $docAdicionalObj->comentarios }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                                
                                                {{-- Documentos de la Empresa --}}
                                                @if ($documentosEmpresa->count() > 0)
                                                    <div class="mb-4">
                                                        <h4 class="font-medium text-gray-700 text-sm mb-2">Documentos de la empresa ({{ $totalEmpresa }})</h4>
                                                        <div class="space-y-2">
                                                            @foreach ($documentosEmpresa as $documentoEmpresa)
                                                                @php $docEmpresaObj = is_array($documentoEmpresa) ? (object) $documentoEmpresa : $documentoEmpresa; @endphp
                                                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                                                    <div class="flex items-center justify-between">
                                                                        <div class="flex items-center space-x-2">
                                                                            <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                                            </svg>
                                                                            <div>
                                                                                <div class="text-sm font-medium text-gray-800">
                                                                                    {{ 
                                                                                        mb_strlen($docEmpresaObj->archivo) > 25 
                                                                                                ? mb_substr($docEmpresaObj->archivo, 0, 22) . '...' 
                                                                                                : $docEmpresaObj->archivo
                                                                                    }}
                                                                                </div>
                                                                                <div class="text-xs text-gray-500">
                                                                                    {{ \Carbon\Carbon::parse($docEmpresaObj->created_at)->format('d-m-Y H:i') }}
                                                                                    @if (isset($docEmpresaObj->creador) && $docEmpresaObj->creador)
                                                                                        - {{ $docEmpresaObj->creador->name ?? 'Empresa' }}
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="flex items-center space-x-2">
                                                                            <form action="{{ route('file.download-concurso-documento') }}" method="POST" class="inline">
                                                                                @csrf
                                                                                <input type="hidden" name="concurso_id" value="{{ $concursoObj->id }}">
                                                                                <input type="hidden" name="documento_id" value="{{ $docEmpresaObj->media_id }}">
                                                                                <button type="submit" class="text-blue-600 hover:underline text-xs">Descargar</button>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                    @if (isset($docEmpresaObj->comentarios) && $docEmpresaObj->comentarios)
                                                                        <div class="mt-2 text-xs text-gray-600">
                                                                            <span class="font-medium">Comentarios:</span> {{ $docEmpresaObj->comentarios }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                                
                                                {{-- Mensaje cuando no hay documentos --}}
                                                @if ($documentosProveedor->count() == 0 && $documentosEmpresa->count() == 0)
                                                    <div class="bg-gray-100 border border-gray-300 rounded-lg p-4 text-gray-600 text-sm text-center">
                                                        No hay documentos adicionales cargados
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    @else 
                                        @foreach ($concursoObj->tipos_documentos_oferta as $tipoDocumentoOferta)
                                        <div class="flex items-center space-x-2 py-2 border-b last:border-b-0">
                                            <span class="font-medium text-gray-800">
                                                {{ $tipoDocumentoOferta['nombre'] ?? 'Documento de Oferta' }}
                                            </span>
                                            @if(isset($tipoDocumentoOferta['obligatorio']) && $tipoDocumentoOferta['obligatorio'])
                                                <span class="text-red-600 text-xs font-semibold">(Obligatorio)</span>
                                            @endif
                                        </div>
                                        @endforeach
                                    @endif
                                </div>
                            @else
                                <div class="text-gray-500 italic">No hay documentación requerida definida</div>
                            @endif
                        </div>
                    </div>
                    <div class="flex justify-end">
                        @if($invitacion)
                            @livewire('concursos.action-modal', ['concurso' => $concursoObj, 'invitacion' => $invitacion], key('action-modal-' . $concursoObj->id))
                        @endif
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</x-app-layout>
