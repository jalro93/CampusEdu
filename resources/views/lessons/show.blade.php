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
                @if (filled($lesson->content))
                    <div class="contenido-leccion text-gray-800">{{ $lesson->renderedContent() }}</div>
                @else
                    <p class="text-gray-500">Esta lección todavía no tiene contenido.</p>
                @endif
            </div>

            <a href="{{ route('courses.show', $course) }}" class="mt-4 inline-block text-sm text-gray-600 hover:text-gray-900 underline">← Volver al curso</a>
        </div>
    </div>
</x-app-layout>
