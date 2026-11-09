<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ $course->title }} · {{ $topic->title }}</p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $lesson->title }}</h2>
            </div>
            @can('update', $lesson)
                <div class="flex items-center gap-3">
                    <a href="{{ route('courses.topics.lessons.edit', [$course, $topic, $lesson]) }}">
                        <x-secondary-button type="button">Editar</x-secondary-button>
                    </a>
                    <form method="POST" action="{{ route('courses.topics.lessons.destroy', [$course, $topic, $lesson]) }}"
                          onsubmit="return confirm('¿Eliminar esta lección? Esta acción no se puede deshacer.')">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>Eliminar</x-danger-button>
                    </form>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                @if ($embed = $lesson->videoEmbedUrl())
                    <div class="aspect-video mb-6">
                        <iframe src="{{ $embed }}" title="Vídeo de la lección" class="w-full h-full rounded-md" loading="lazy" allowfullscreen
                                allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture"
                                referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    </div>
                @endif

                @if (filled($lesson->content))
                    <div class="contenido-leccion text-gray-800">{{ $lesson->renderedContent() }}</div>
                @elseif (! $embed)
                    <p class="text-gray-500">Esta lección todavía no tiene contenido.</p>
                @endif
            </div>

            <div class="mt-6 p-6 bg-white shadow-sm sm:rounded-lg" x-data="{ tipo: '{{ old('type', 'enlace') }}' }">
                <h3 class="font-semibold text-gray-800">Recursos</h3>

                @forelse ($lesson->resources as $resource)
                    <div class="mt-3 flex items-center justify-between gap-4 text-sm">
                        @if ($resource->isLink())
                            <a href="{{ $resource->url }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-800 underline">{{ $resource->name }} <span class="text-gray-500">(enlace)</span></a>
                        @else
                            <a href="{{ route('courses.topics.lessons.resources.show', [$course, $topic, $lesson, $resource]) }}" class="text-indigo-600 hover:text-indigo-800 underline">{{ $resource->name }} <span class="text-gray-500">(archivo)</span></a>
                        @endif
                        @can('delete', $resource)
                            <form method="POST" action="{{ route('courses.topics.lessons.resources.destroy', [$course, $topic, $lesson, $resource]) }}"
                                  onsubmit="return confirm('¿Eliminar este recurso?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 underline">Eliminar</button>
                            </form>
                        @endcan
                    </div>
                @empty
                    <p class="mt-3 text-sm text-gray-500">Esta lección todavía no tiene recursos.</p>
                @endforelse

                @can('create', [\App\Models\Resource::class, $lesson])
                    <form method="POST" enctype="multipart/form-data" class="mt-6 pt-6 border-t border-gray-200"
                          action="{{ route('courses.topics.lessons.resources.store', [$course, $topic, $lesson]) }}">
                        @csrf
                        <p class="text-sm font-medium text-gray-700">Añadir recurso</p>

                        <div class="mt-3">
                            <x-input-label for="resource_name" value="Nombre" />
                            <x-text-input id="resource_name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required maxlength="255" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="mt-3">
                            <x-input-label for="resource_type" value="Tipo" />
                            <select id="resource_type" name="type" x-model="tipo" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="enlace">Enlace externo</option>
                                <option value="archivo">Archivo</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>

                        <div class="mt-3" x-show="tipo === 'enlace'">
                            <x-input-label for="resource_url" value="Dirección del enlace" />
                            <x-text-input id="resource_url" name="url" type="url" class="mt-1 block w-full" :value="old('url')" maxlength="255" placeholder="https://..." />
                            <x-input-error :messages="$errors->get('url')" class="mt-2" />
                        </div>

                        <div class="mt-3" x-show="tipo === 'archivo'">
                            <x-input-label for="resource_file" value="Archivo" />
                            <input id="resource_file" name="file" type="file" class="mt-1 block w-full text-sm text-gray-700" />
                            <x-input-error :messages="$errors->get('file')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500">Máximo 10 MB. Formatos: PDF, Word, Excel, PowerPoint, OpenDocument, TXT, ZIP, PNG y JPG.</p>
                        </div>

                        <div class="mt-4">
                            <x-primary-button>Añadir recurso</x-primary-button>
                        </div>
                    </form>
                @endcan
            </div>

            <a href="{{ route('courses.show', $course) }}" class="mt-4 inline-block text-sm text-gray-600 hover:text-gray-900 underline">← Volver al curso</a>
        </div>
    </div>
</x-app-layout>
