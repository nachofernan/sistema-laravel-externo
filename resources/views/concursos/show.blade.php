<x-app-layout>
    <div class="w-full xl:w-10/12 mb-12 xl:mb-0 px-4 mx-auto pb-10 mt-4">
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div
                class="flex justify-between border-b text-white font-bold bg-gradient-to-r p-6
            @switch($concurso->estado->id ?? $concurso->estado['id'])
                @case(1)
                from-orange-700 to-orange-400
                    @break
                @case(2)
                @if (\Carbon\Carbon::parse($concurso->fecha_cierre)->isFuture())
                    from-green-700 to-green-400
                @else
                    from-yellow-700 to-yellow-400
                @endif
                    @break
                @case(3)
                from-blue-700 to-blue-400
                    @break
                @case(4)
                from-blue-700 to-blue-400
                    @break
                @case(5)
                from-red-700 to-red-400
                    @break
            @endswitch
            ">
                <div>
                    <div class="text-2xl font-bold text-white">
                        {{ $concurso->nombre }}
                    </div>
                    <div class="font-normal">
                        #{{ $concurso->numero ?? 'Sin Número' }} -
                        @switch($concurso->estado->id ?? $concurso->estado['id'])
                            @case(2)
                                @if (\Carbon\Carbon::parse($concurso->fecha_cierre)->isFuture())
                                    Activo
                                @else
                                    Cerrado
                                @endif
                            @break

                            @default
                                {{ $concurso->estado->nombre ?? $concurso->estado['nombre'] }}
                        @endswitch
                    </div>
                </div>
                <div class="text-sm flex justify-end gap-4 items-center">
                    @livewire('concursos.action-modal', ['concurso' => $concurso, 'invitacion' => $invitacion])
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
                                    <dd class="text-gray-800">{{ $concurso->nombre }}</dd>
                                </div>

                                <div class="flex justify-between border-b pb-2">
                                    <dt class="font-medium text-gray-600">Número</dt>
                                    <dd class="text-gray-800">#{{ $concurso->numero ?? 'Sin Número' }}</dd>
                                </div>

                                <div class="flex justify-between border-b pb-2">
                                    <dt class="font-medium text-gray-600">Descripción</dt>
                                    <dd class="text-gray-800 text-right">{{ $concurso->descripcion }}</dd>
                                </div>

                                <div class="flex justify-between border-b pb-2">
                                    <dt class="font-medium text-gray-600">Sedes</dt>
                                    <dd class="text-right">
                                        @php
                                            $sedes = $concurso->sedes ?? [];
                                            if (is_object($sedes)) {
                                                $sedes = (array) $sedes;
                                            }
                                        @endphp
                                        @foreach ($sedes as $sede)
                                            @php
                                                // ✅ Manejar tanto objetos como arrays
                                                $nombreSede = '';
                                                if (is_object($sede)) {
                                                    $nombreSede = $sede->nombre_sede ?? ($sede->nombre ?? 'Sede');
                                                } elseif (is_array($sede)) {
                                                    $nombreSede = $sede['nombre_sede'] ?? ($sede['nombre'] ?? 'Sede');
                                                }

                                                // Mapeo de IDs a nombres (fallback)
                                                if (empty($nombreSede) && isset($sede['sede_id'])) {
                                                    $sedeMap = [
                                                        1 => 'La Plata',
                                                        2 => 'Mar del Plata',
                                                        3 => 'Villa Gesell',
                                                        4 => 'Mar de Ajó',
                                                        5 => 'Necochea',
                                                    ];
                                                    $nombreSede = $sedeMap[$sede['sede_id']] ?? 'Sede no definida';
                                                }
                                            @endphp
                                            <div>{{ $nombreSede }}</div>
                                        @endforeach
                                    </dd>
                                </div>

                                <div class="flex justify-between border-b pb-2">
                                    <dt class="font-medium text-gray-600">Fecha Inicio</dt>
                                    <dd class="text-gray-800">
                                        {{ \Carbon\Carbon::parse($concurso->fecha_inicio)->format('d-m-Y - H:i') }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="bg-gray-50 px-2 py-4">
                            <div class="flex justify-between items-center px-4">
                                <span class="font-medium text-gray-600">Fecha Cierre</span>
                                <span
                                    class="text-gray-800 font-bold">{{ \Carbon\Carbon::parse($concurso->fecha_cierre)->format('d-m-Y - H:i') }}</span>
                            </div>

                            @php
                                $prorrogas = $concurso->prorrogas ?? [];
                                if (is_object($prorrogas)) {
                                    $prorrogas = (array) $prorrogas;
                                }
                            @endphp
                            @if (count($prorrogas) > 0)
                                <div class="mt-4 space-y-2 bg-white rounded-md shadow-sm py-2 text-xs">
                                    @foreach ($prorrogas as $key => $prorroga)
                                        @php
                                            $fechaAnterior = is_object($prorroga)
                                                ? $prorroga->fecha_anterior
                                                : $prorroga['fecha_anterior'];
                                            $fechaActual = is_object($prorroga)
                                                ? $prorroga->fecha_actual
                                                : $prorroga['fecha_actual'];
                                        @endphp
                                        <div class="flex justify-between items-center px-4">
                                            <span class="font-medium">Prórroga {{ $key + 1 }}</span>
                                            <div class="text-xs text-gray-600">
                                                {{ \Carbon\Carbon::parse($fechaAnterior)->format('d-m-Y - H:i') }}
                                                <span class="mx-2">➔</span>
                                                {{ \Carbon\Carbon::parse($fechaActual)->format('d-m-Y - H:i') }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Contactos Card -->
                    @php
                        $contactos = $concurso->contactos ?? [];
                        if (is_object($contactos)) {
                            $contactos = (array) $contactos;
                        }
                    @endphp
                    @if (count($contactos) > 0)
                        <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
                            <div class="bg-gray-100 p-4 text-lg">
                                <h2 class="font-medium text-gray-700">Contactos</h2>
                            </div>
                            <div class="p-6">
                                @foreach ($contactos as $contacto)
                                    @php
                                        $nombre = is_object($contacto) ? $contacto->nombre : $contacto['nombre'];
                                        $tipo = is_object($contacto) ? $contacto->tipo : $contacto['tipo'];
                                        $correo = is_object($contacto) ? $contacto->correo : $contacto['correo'];
                                        $telefono = is_object($contacto) ? $contacto->telefono : $contacto['telefono'];
                                    @endphp
                                    <div class="border-b pb-2 mb-2 last:border-b-0 last:mb-0">
                                        <div class="font-medium">
                                            {{ $nombre }} -
                                            <span
                                                class="text-gray-600 text-sm">{{ $tipo == 'administrativo' ? 'Administrativo' : 'Técnico' }}</span>
                                        </div>
                                        <div class="text-xs text-gray-600">{{ $correo }} - {{ $telefono }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Documentación Adjunta Card -->
                    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
                        <div class="bg-gray-100 p-4 text-lg">
                            <h2 class="font-medium text-gray-700">Documentación Adjunta al Concurso</h2>
                        </div>
                        <div class="p-6">
                            @php
                                $documentos = $concurso->documentos ?? [];
                                if (is_object($documentos)) {
                                    $documentos = (array) $documentos;
                                }
                            @endphp
                            @if (count($documentos) > 0)
                                <div class="space-y-3">
                                    @foreach ($documentos as $documento)
                                        @php
                                            $docTipo = is_object($documento)
                                                ? $documento->documentoTipo
                                                : $documento['documento_tipo'];
                                            $nombreTipo = is_object($docTipo) ? $docTipo->nombre : $docTipo['nombre'];
                                            $createdAt = is_object($documento)
                                                ? $documento->created_at
                                                : $documento['created_at'];
                                            $fileStorage = is_object($documento)
                                                ? $documento->file_storage
                                                : $documento['file_storage'];
                                        @endphp
                                        <div class="bg-gray-50 p-3 rounded-lg flex justify-between items-center">
                                            <div>
                                                <div class="font-medium">{{ $nombreTipo }}</div>
                                                <div class="text-xs text-gray-500">Cargado el
                                                    {{ \Carbon\Carbon::parse($createdAt)->format('d-m-Y') }}</div>
                                            </div>
                                            <form action="{{ route('file.download') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="disk" value="concursos">
                                                <input type="hidden" name="fileName" value="{{ $fileStorage }}">
                                                <button type="submit"
                                                    class="text-blue-600 hover:underline">Descargar</button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-gray-500 italic">Sin documentación</div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Required Documentation Column -->
                <div class="space-y-4">
                    <div class="bg-white shadow-md rounded-lg overflow-hidden">
                        <div class="bg-gray-100 p-4 text-lg">
                            <h2 class="font-medium text-gray-700">
                                Documentación Requerida para Participar
                            </h2>
                        </div>
                        <div class="p-6">
                            @if (
                                ($invitacion->intencion == 1 || $invitacion->intencion == 3) &&
                                    ($concurso->estado->id == 2 && \Carbon\Carbon::parse($concurso->fecha_cierre)->isFuture()))
                                <div
                                    class="mb-4 border-l-4 border-orange-800 bg-orange-50 text-orange-800 px-4 py-2 text-xs">
                                    La documentación cargada se encuentra encriptada, sólo podrá ser visualizada por el
                                    personal una vez finalizado el concurso.
                                    <br>
                                    De ser necesaria, la documentación faltante será requerida mediante correo
                                    electrónico.
                                    <br>
                                    <span class="font-semibold">La documentación debe ser subida en formato PDF, JPG,
                                        JPEG o PNG con un máximo de 10Mb por archivo.</span>
                                </div>
                            @endif

                            <div class="mb-4 border-l-4 border-green-800 bg-green-50 text-green-800 px-4 py-2 text-xs">
                                Documentación ya cargada de Representantes legales y apoderados habilitados para firmar:
                                @php
                                    $apoderados = $invitacion->proveedor->apoderados ?? [];
                                    if (is_object($apoderados)) {
                                        $apoderados = (array) $apoderados;
                                    }
                                @endphp
                                @foreach ($apoderados as $apoderado)
                                    @php
                                        $documentosApoderado = is_object($apoderado)
                                            ? $apoderado->documentos ?? []
                                            : $apoderado['documentos'] ?? [];
                                        if (is_object($documentosApoderado)) {
                                            $documentosApoderado = (array) $documentosApoderado;
                                        }
                                        $activo = is_object($apoderado)
                                            ? $apoderado->activo ?? true
                                            : $apoderado['activo'] ?? true;
                                    @endphp
                                    @if ($activo && count($documentosApoderado) > 0)
                                        @php
                                            $primerDoc = array_values($documentosApoderado)[0];
                                            $fileStorage = is_object($primerDoc)
                                                ? $primerDoc->file_storage
                                                : $primerDoc['file_storage'];
                                            $nombre = is_object($apoderado) ? $apoderado->nombre : $apoderado['nombre'];
                                        @endphp
                                        <div class="font-medium">
                                            <form action="{{ route('file.download') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="disk" value="proveedores">
                                                <input type="hidden" name="fileName" value="{{ $fileStorage }}">
                                                <button type="submit" class="px-3 hover:underline">
                                                    @if ($nombre)
                                                        Descargar documento de {{ $nombre }}
                                                    @else
                                                        Descargar documento de Apoderado
                                                    @endif
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                @endforeach
                                En caso de que exista un nuevo representante o apoderado, por favor, agregue el aval
                                correspondiente en "Otros Documentos"
                            </div>

                            <div class="space-y-4">
                                @php
                                    $documentosRequeridos = $concurso->documentos_requeridos ?? [];
                                    if (is_object($documentosRequeridos)) {
                                        $documentosRequeridos = (array) $documentosRequeridos;
                                    }
                                @endphp
                                @foreach ($documentosRequeridos as $documento_tipo)
                                    @php
                                        $nombre = is_object($documento_tipo)
                                            ? $documento_tipo->nombre
                                            : $documento_tipo['nombre'];
                                        $descripcion = is_object($documento_tipo)
                                            ? $documento_tipo->descripcion
                                            : $documento_tipo['descripcion'];
                                        $obligatorio = is_object($documento_tipo)
                                            ? $documento_tipo->obligatorio
                                            : $documento_tipo['obligatorio'];
                                        $tipoDocProveedor = is_object($documento_tipo)
                                            ? $documento_tipo->tipo_documento_proveedor ?? null
                                            : $documento_tipo['tipo_documento_proveedor'] ?? null;
                                        $documentoTipoId = is_object($documento_tipo)
                                            ? $documento_tipo->id
                                            : $documento_tipo['id'];
                                    @endphp
                                    <div class="bg-white shadow-sm rounded-lg px-4 py-4 border">
                                        <div class="flex justify-between items-center mb-2">
                                            <h3 class="font-semibold text-gray-700">
                                                {{ $nombre }}
                                                @if ($obligatorio)
                                                    <span class="text-red-600 text-xs">(Obligatorio)</span>
                                                @endif
                                                <p class="font-light text-gray-500 text-sm">{{ $descripcion }}</p>
                                            </h3>
                                            @if (
                                                ($invitacion->intencion == 1 || $invitacion->intencion == 3) &&
                                                    ($concurso->estado->id == 2 && \Carbon\Carbon::parse($concurso->fecha_cierre)->isFuture()))
                                                @livewire('concursos.subir-archivo', ['concurso' => $concurso, 'invitacion' => $invitacion, 'documento' => $documento_tipo])
                                            @endif
                                        </div>

                                        @if ($tipoDocProveedor)
                                            @php
                                                $nombreTipoProveedor = is_object($tipoDocProveedor)
                                                    ? $tipoDocProveedor->nombre
                                                    : $tipoDocProveedor['nombre'];
                                                $tipoProveedorId = is_object($tipoDocProveedor)
                                                    ? $tipoDocProveedor->id
                                                    : $tipoDocProveedor['id'];

                                                // ✅ Buscar documento del proveedor
                                                $proveedorDocumento = null;
                                                $documentosProveedor = $user->proveedor->documentos ?? [];
                                                if (is_object($documentosProveedor)) {
                                                    $documentosProveedor = (array) $documentosProveedor;
                                                }

                                                foreach ($documentosProveedor as $docProv) {
                                                    $docTipoId = is_object($docProv)
                                                        ? $docProv->documento_tipo_id
                                                        : $docProv['documento_tipo_id'];
                                                    if ($docTipoId == $tipoProveedorId) {
                                                        $proveedorDocumento = $docProv;
                                                        break;
                                                    }
                                                }
                                            @endphp
                                            <div class="text-sm text-gray-600 mb-2">
                                                Asociado a: {{ $nombreTipoProveedor }}

                                                @if ($proveedorDocumento)
                                                    @php
                                                        $vencimiento = is_object($proveedorDocumento)
                                                            ? $proveedorDocumento->vencimiento ?? null
                                                            : $proveedorDocumento['vencimiento'] ?? null;
                                                    @endphp
                                                    @if ($vencimiento)
                                                        @if (\Carbon\Carbon::parse($vencimiento)->isPast())
                                                            <span
                                                                class="bg-red-400 text-white rounded px-1 ml-1">Vencido</span>
                                                        @else
                                                            <span
                                                                class="bg-green-400 text-white rounded px-1 ml-1">Válido</span>
                                                        @endif
                                                    @endif
                                                @endif
                                            </div>
                                        @endif

                                        @if ($invitacion->intencion > 0)
                                            <div class="mt-2">
                                                @php
                                                    // ✅ Contar documentos de este tipo
                                                    $documentosInvitacion = $invitacion->documentos ?? [];
                                                    if (is_object($documentosInvitacion)) {
                                                        $documentosInvitacion = (array) $documentosInvitacion;
                                                    }

                                                    $documentosDelTipo = array_filter($documentosInvitacion, function (
                                                        $doc,
                                                    ) use ($documentoTipoId) {
                                                        $docTipoId = is_object($doc)
                                                            ? $doc->documento_tipo_id
                                                            : $doc['documento_tipo_id'];
                                                        return $docTipoId == $documentoTipoId;
                                                    });

                                                    $hayDocumentos = count($documentosDelTipo) > 0;
                                                    $hayDocProveedor = $tipoDocProveedor && $proveedorDocumento;
                                                @endphp

                                                @if (!$hayDocumentos && !$hayDocProveedor)
                                                    <span
                                                        class="rounded py-1 px-3 bg-red-500 text-white font-bold text-sm">No
                                                        cargado</span>
                                                @endif

                                                @foreach ($documentosDelTipo as $documento)
                                                    @php
                                                        $createdAt = is_object($documento)
                                                            ? $documento->created_at
                                                            : $documento['created_at'];
                                                        $fileStorage = is_object($documento)
                                                            ? $documento->file_storage
                                                            : $documento['file_storage'];
                                                    @endphp
                                                    <div class="flex justify-between items-center border-t py-2 mt-2">
                                                        <div class="text-sm text-gray-600">
                                                            Cargado
                                                            {{ \Carbon\Carbon::parse($createdAt)->format('d-m-Y H:i') }}
                                                        </div>
                                                        <div class="flex items-center space-x-2">
                                                            <form action="{{ route('file.download') }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="disk"
                                                                    value="concursos">
                                                                <input type="hidden" name="fileName"
                                                                    value="{{ $fileStorage }}">
                                                                <button type="submit"
                                                                    class="rounded py-1 px-3 bg-green-500 text-white font-bold text-sm">
                                                                    Descargar
                                                                </button>
                                                            </form>
                                                            @livewire(
                                                                'concursos.eliminar-archivo',
                                                                [
                                                                    'documento' => $documento,
                                                                    'concurso' => $concurso,
                                                                    'invitacion' => $invitacion,
                                                                ],
                                                                key(is_object($documento) ? $documento->id : $documento['id'])
                                                            )
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                                @if ($invitacion->intencion > 0)
                                    <div class="bg-white shadow-sm rounded-lg p-4 border">
                                        <div class="flex justify-between items-center mb-2">
                                            <h3 class="font-semibold text-gray-700">
                                                Otros Documentos
                                                <p class="font-light text-gray-500 text-sm">Cualquier otro tipo de
                                                    documento</p>
                                            </h3>
                                            @if (
                                                ($invitacion->intencion == 1 || $invitacion->intencion == 3) &&
                                                    ($concurso->estado->id == 2 && \Carbon\Carbon::parse($concurso->fecha_cierre)->isFuture()))
                                                @livewire('concursos.subir-archivo', ['concurso' => $concurso, 'invitacion' => $invitacion])
                                            @endif
                                        </div>
                                        <div class="mt-2">
                                            @php
                                                $documentosSinTipo = array_filter($documentosInvitacion, function (
                                                    $doc,
                                                ) {
                                                    $docTipoId = is_object($doc)
                                                        ? $doc->documento_tipo_id ?? null
                                                        : $doc['documento_tipo_id'] ?? null;
                                                    $createdAt = is_object($doc)
                                                        ? $doc->created_at
                                                        : $doc['created_at'];
                                                    return !$docTipoId &&
                                                        \Carbon\Carbon::parse($createdAt)->lte(
                                                            \Carbon\Carbon::parse($concurso->fecha_cierre),
                                                        );
                                                });
                                            @endphp
                                            @foreach ($documentosSinTipo as $documento)
                                                @php
                                                    $createdAt = is_object($documento)
                                                        ? $documento->created_at
                                                        : $documento['created_at'];
                                                    $fileStorage = is_object($documento)
                                                        ? $documento->file_storage
                                                        : $documento['file_storage'];
                                                @endphp
                                                <div class="flex justify-between items-center border-t py-2 mt-2">
                                                    <div class="text-sm text-gray-600">
                                                        Cargado
                                                        {{ \Carbon\Carbon::parse($createdAt)->format('d-m-Y H:i') }}
                                                    </div>
                                                    <div class="flex items-center space-x-2">
                                                        <form action="{{ route('file.download') }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="disk" value="concursos">
                                                            <input type="hidden" name="fileName"
                                                                value="{{ $fileStorage }}">
                                                            <button type="submit"
                                                                class="rounded py-1 px-3 bg-green-500 text-white font-bold text-sm">
                                                                Descargar
                                                            </button>
                                                        </form>
                                                        @livewire(
                                                            'concursos.eliminar-archivo',
                                                            [
                                                                'documento' => $documento,
                                                                'concurso' => $concurso,
                                                                'invitacion' => $invitacion,
                                                            ],
                                                            key(is_object($documento) ? $documento->id : $documento['id'])
                                                        )
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if ($invitacion->intencion == 3 && $concurso->estado->id > 2)
                                    <div class="bg-white shadow-sm rounded-lg p-4 border mt-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <h3 class="font-semibold text-gray-700">
                                                Documentos Post-Apertura
                                                <p class="font-light text-gray-500 text-sm">Documentos cargados en
                                                    etapa de análisis</p>
                                            </h3>
                                            @if ($invitacion->concurso->estado->id == 3 && ($concurso->permite_carga ?? false))
                                                @livewire('concursos.subir-archivo', ['concurso' => $concurso, 'invitacion' => $invitacion])
                                            @endif
                                        </div>
                                        <div class="mt-2">
                                            @php
                                                $documentosPostConcurso = array_filter($documentosInvitacion, function (
                                                    $doc,
                                                ) use ($concurso) {
                                                    $docTipoId = is_object($doc)
                                                        ? $doc->documento_tipo_id ?? null
                                                        : $doc['documento_tipo_id'] ?? null;
                                                    $createdAt = is_object($doc)
                                                        ? $doc->created_at
                                                        : $doc['created_at'];
                                                    return !$docTipoId &&
                                                        \Carbon\Carbon::parse($createdAt)->gt(
                                                            \Carbon\Carbon::parse($concurso->fecha_cierre),
                                                        );
                                                });
                                            @endphp
                                            @foreach ($documentosPostConcurso as $documento)
                                                @php
                                                    $createdAt = is_object($documento)
                                                        ? $documento->created_at
                                                        : $documento['created_at'];
                                                    $fileStorage = is_object($documento)
                                                        ? $documento->file_storage
                                                        : $documento['file_storage'];
                                                    $userIdCreated = is_object($documento)
                                                        ? $documento->user_id_created ?? null
                                                        : $documento['user_id_created'] ?? null;
                                                @endphp
                                                <div class="flex justify-between items-center border-t py-2 mt-2">
                                                    <div class="text-sm text-gray-600">
                                                        Cargado
                                                        {{ \Carbon\Carbon::parse($createdAt)->format('d-m-Y H:i') }}
                                                        @if ($userIdCreated)
                                                            <span
                                                                class="bg-yellow-100 text-yellow-800 text-xs font-medium ml-2 px-2 py-0.5 rounded">BAESA</span>
                                                        @endif
                                                    </div>
                                                    <div class="flex items-center space-x-2">
                                                        <form action="{{ route('file.download') }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="disk" value="concursos">
                                                            <input type="hidden" name="fileName"
                                                                value="{{ $fileStorage }}">
                                                            <button type="submit"
                                                                class="rounded py-1 px-3 bg-green-500 text-white font-bold text-sm">
                                                                Descargar
                                                            </button>
                                                        </form>
                                                        @livewire(
                                                            'concursos.eliminar-archivo',
                                                            [
                                                                'documento' => $documento,
                                                                'concurso' => $concurso,
                                                                'invitacion' => $invitacion,
                                                            ],
                                                            key(is_object($documento) ? $documento->id : $documento['id'])
                                                        )
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-4">
                        @livewire('concursos.action-modal', ['concurso' => $concurso, 'invitacion' => $invitacion])
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
