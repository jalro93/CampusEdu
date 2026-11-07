<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $course->title }}</h2>
            @can('update', $course)
                <div class="flex items-center gap-3">
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

    <div class="py-12">
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

            <a href="{{ route('courses.index') }}" class="mt-4 inline-block text-sm text-gray-600 hover:text-gray-900 underline">← Volver a mis cursos</a>
        </div>
    </div>
</x-app-layout>
