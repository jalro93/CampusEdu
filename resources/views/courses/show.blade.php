<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight break-words">{{ $course->title }}</h2>
            @can('update', $course)
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('courses.edit', $course) }}">
                        <x-secondary-button type="button">Editar</x-secondary-button>
                    </a>
                    <form method="POST" action="{{ route('courses.destroy', $course) }}"
                          onsubmit="return confirm('¿Eliminar este curso? Esta acción no se puede deshacer.')">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>Eliminar</x-danger-button>
                    </form>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <p class="text-xs uppercase tracking-wide text-gray-500">Tu rol: {{ $role }}</p>

                @if ($course->description)
                    <p class="mt-4 text-gray-800 whitespace-pre-line">{{ $course->description }}</p>
                @else
                    <p class="mt-4 text-gray-500">Este curso todavía no tiene descripción.</p>
                @endif

                <h3 class="mt-6 font-medium text-gray-900">Profesorado</h3>
                <ul class="mt-1 text-sm text-gray-700">
                    @foreach ($teachers as $teacher)
                        <li>{{ $teacher->name }}</li>
                    @endforeach
                </ul>
            </div>

            @if ($pendingRequests->isNotEmpty())
                <div class="mt-6 p-6 bg-white shadow-sm sm:rounded-lg">
                    <h3 class="font-medium text-gray-900">Solicitudes de matrícula pendientes</h3>
                    @foreach ($pendingRequests as $solicitud)
                        <div class="mt-3 flex flex-col gap-3 border-t pt-3 text-sm sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                            <span class="min-w-0 break-words text-gray-800">{{ $solicitud->user->name }} <span class="text-gray-500">({{ $solicitud->user->email }})</span></span>
                            <div class="flex flex-wrap items-center gap-3">
                                <form method="POST" action="{{ route('courses.enrollment-requests.approve', [$course, $solicitud]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-primary-button>Aprobar</x-primary-button>
                                </form>
                                <form method="POST" action="{{ route('courses.enrollment-requests.reject', [$course, $solicitud]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-secondary-button type="submit">Rechazar</x-secondary-button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($progress)
                <div class="mt-6 p-6 bg-white shadow-sm sm:rounded-lg">
                    <h3 class="font-medium text-gray-900">Tu progreso</h3>
                    <x-progress-bar class="mt-3" :percent="$progress['percent']" :completed="$progress['completed']" :total="$progress['total']" />
                </div>
            @endif

            <div class="mt-6 p-6 bg-white shadow-sm sm:rounded-lg">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="font-medium text-gray-900">Temas</h3>
                    @can('create', [\App\Models\Topic::class, $course])
                        <a href="{{ route('courses.topics.create', $course) }}">
                            <x-secondary-button type="button">Nuevo tema</x-secondary-button>
                        </a>
                    @endcan
                </div>

                @forelse ($topics as $topic)
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-t pt-3">
                        <span class="min-w-0 break-words text-gray-800">{{ $loop->iteration }}. {{ $topic->title }}</span>
                        @can('update', $topic)
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                                @unless ($loop->first)
                                    <form method="POST" action="{{ route('courses.topics.move', [$course, $topic, 'up']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-gray-600 hover:text-gray-900" title="Subir" aria-label="Subir tema">↑</button>
                                    </form>
                                @endunless
                                @unless ($loop->last)
                                    <form method="POST" action="{{ route('courses.topics.move', [$course, $topic, 'down']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-gray-600 hover:text-gray-900" title="Bajar" aria-label="Bajar tema">↓</button>
                                    </form>
                                @endunless
                                <a href="{{ route('courses.topics.edit', [$course, $topic]) }}" class="text-indigo-600 hover:text-indigo-900 underline">Editar</a>
                                <form method="POST" action="{{ route('courses.topics.destroy', [$course, $topic]) }}"
                                      onsubmit="return confirm('¿Eliminar este tema?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 underline">Eliminar</button>
                                </form>
                            </div>
                        @endcan
                    </div>
                    <ul class="mt-2 ms-4 space-y-1 text-sm break-words sm:ms-6">
                        @foreach ($topic->lessons as $lesson)
                            <li>
                                @if (in_array($lesson->id, $completedIds, true))
                                    <span class="text-green-600" title="Completada" aria-label="Lección completada">✓</span>
                                @endif
                                <a href="{{ route('courses.topics.lessons.show', [$course, $topic, $lesson]) }}" class="text-indigo-600 hover:text-indigo-900 underline">{{ $lesson->title }}</a>
                            </li>
                        @endforeach
                        @can('create', [\App\Models\Lesson::class, $topic])
                            <li>
                                <a href="{{ route('courses.topics.lessons.create', [$course, $topic]) }}" class="text-gray-600 hover:text-gray-900 underline">+ Añadir lección</a>
                            </li>
                        @endcan
                    </ul>
                @empty
                    <p class="mt-3 text-sm text-gray-500">Este curso todavía no tiene temas.</p>
                @endforelse
            </div>

            <a href="{{ route('courses.index') }}" class="mx-4 mt-4 inline-block text-sm text-gray-600 hover:text-gray-900 underline sm:mx-0">← Volver a mis cursos</a>
        </div>
    </div>
</x-app-layout>
