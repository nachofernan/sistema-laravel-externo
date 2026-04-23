<form action="{{ route('concursos.file.upload', $concursoObj->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    
    <input type="hidden" name="documento_tipo_id" value="{{ $documento->id ?? '' }}">

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Archivo para: <span class="font-bold text-blue-600">{{ $documento->nombre ?? 'Documento Adicional' }}</span>
            </label>
            <input type="file" name="file" class="w-full border p-2 rounded mt-1" required accept=".pdf">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Comentarios</label>
            <textarea name="comentarios" rows="2" class="w-full border p-2 rounded mt-1" placeholder="Alguna nota sobre este archivo..."></textarea>
        </div>
    </div>

    <div class="mt-6 flex justify-end space-x-3">
        <button type="button" @click="openConcurso = false" class="px-4 py-2 bg-gray-200 rounded text-sm">Cancelar</button>
        <button type="submit" class="px-4 py-2 bg-cyan-500 text-white rounded text-sm hover:bg-cyan-600">
            Confirmar Subida
        </button>
    </div>
</form>
<div x-data="{ openConcurso: false }">
    <button type="button" @click="openConcurso = true" class="rounded py-1 px-3 text-blue-500 hover:underline text-sm">
        {{ $documento->id ? 'Cargar' : 'Cargar Adicional' }}
    </button>

    <div x-show="openConcurso" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-cloak>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6" @click.away="openConcurso = false">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h2 class="text-xl font-semibold text-gray-800">
                    Cargar: <span class="text-blue-600">{{ $documento->nombre ?? 'Documento Adicional' }}</span>
                </h2>
                <button @click="openConcurso = false" class="text-gray-500 hover:text-gray-700">&times;</button>
            </div>

            <form action="{{ route('concursos.file.upload', $concurso->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="documento_tipo_id" value="{{ $documento->id ?? '' }}">

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Seleccionar archivo PDF</label>
                        <input type="file" name="file" class="w-full border p-2 rounded mt-1" required accept=".pdf">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Comentarios (opcional)</label>
                        <textarea name="comentarios" rows="3" class="w-full border p-2 rounded mt-1" placeholder="Información adicional..."></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="openConcurso = false" class="px-4 py-2 bg-gray-200 rounded text-sm">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-cyan-500 text-white rounded text-sm hover:bg-cyan-600">
                        Subir Archivo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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