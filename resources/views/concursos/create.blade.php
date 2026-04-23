<x-app-layout>
    <div class="w-full xl:w-10/12 mb-12 xl:mb-0 px-4 mx-auto mt-4">
        <div class="relative flex flex-col min-w-0 break-words bg-white w-full mb-6 shadow-lg rounded ">
            <form action="{{ route('concursos.concursos.store') }}" method="POST">
                {{ csrf_field() }}
                <div class="grid grid-cols-12 px-5 py-3 border-b pb-3 mb-2">
                    <div class="col-span-8 pt-1">
                        <div class="titulo-show">
                            Crear Nuevo Concurso
                        </div>
                    </div>
                    <div class="col-span-4 text-right text-sm">
                        <button type="submit" class="boton-celeste">Guardar</button>
                        <a href="{{ route('concursos.concursos.index') }}">
                            <button type="button" class="bg-gray-200 boton-celeste">Volver</button>
                        </a>
                    </div>
                </div>
                <div class="block w-full overflow-x-auto pb-5 px-5">
                    <div class="grid grid-cols-2 gap-5">
                        <div class="col">
                            <div class="subtitulo-show">
                                Datos del Concurso
                            </div>
                            <div class="grid-datos-show">
                                <div class="atributo-edit">
                                    Nombre
                                </div>
                                <div class="valor-edit">
                                    <input type="text" name="nombre" value="{{ old('nombre') }}" class="input-full" placeholder="Nombre" required>
                                    <div>@error('nombre') {{ $message }} @enderror</div>
                                </div>
                                <div class="atributo-edit">
                                    Numero
                                </div>
                                <div class="valor-edit">
                                    <input type="text" name="numero" value="{{ old('numero') }}" class="input-full" placeholder="Numero" required>
                                    <div>@error('numero') {{ $message }} @enderror</div>
                                </div>
                                <div class="atributo-edit">
                                    Descripción
                                </div>
                                <div class="valor-edit">
                                    <input type="text" name="descripcion" value="{{ old('descripcion') }}" class="input-full" placeholder="Descripción" required>
                                    <div>@error('descripcion') {{ $message }} @enderror</div>
                                </div>
                                <div class="atributo-edit">
                                    Fecha Inicio
                                </div>
                                <div class="valor-edit">
                                    <input type="datetime-local" name="fecha_inicio" value="{{ old('fecha_inicio') }}" class="input-full" placeholder="Fecha Inicio" required>
                                    <div>@error('fecha_inicio') {{ $message }} @enderror</div>
                                </div>
                                <div class="atributo-edit">
                                    Fecha Cierre
                                </div>
                                <div class="valor-edit">
                                    <input type="datetime-local" name="fecha_cierre" value="{{ old('fecha_cierre') }}" class="input-full" placeholder="Fecha Cierre" required>
                                    <div>@error('fecha_cierre') {{ $message }} @enderror</div>
                                </div>
                                <div class="atributo-edit">
                                    Encargado 
                                </div>
                                <div class="valor-edit">
                                    <select name="encargado_user_id" class="input-full">
                                        <option value="">Sin Encargado</option>
                                        @foreach ($users as $user)
                                            <option value="{{$user->id}}">{{ $user->legajo }} - {{ $user->realname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="atributo-edit">
                                    Técnico 
                                </div>
                                <div class="valor-edit">
                                    <select name="tecnico_user_id" class="input-full">
                                        <option value="">Sin Encargado</option>
                                        @foreach ($users as $user)
                                            <option value="{{$user->id}}">{{ $user->legajo }} - {{ $user->realname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>