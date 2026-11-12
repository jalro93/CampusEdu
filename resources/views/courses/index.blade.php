<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mis cursos</h2>
            <a href="{{ route('courses.create') }}">
                <x-primary-button type="button">Nuevo curso</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @forelse ($courses as $course)
                <a href="{{ route('courses.show', $course) }}" class="block mb-3 p-5 bg-white shadow-sm sm:rounded-lg hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-gray-900">{{ $course->title }}</span>
                        <span class="text-xs uppercase tracking-wide px-2 py-1 rounded-full {{ $course->pivot->role === 'profesor' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-700' }}">
                            {{ $course->pivot->role }}
                        </span>
                    </div>
                    @if ($course->description)
                        <p class="mt-1 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($course->description, 140) }}</p>
                    @endif
                </a>
            @empty
                <div class="p-6 bg-white shadow-sm sm:rounded-lg text-gray-600">
                    Todavía no participas en ningún curso. Puedes crear uno o <a href="{{ route('catalog.index') }}" class="text-indigo-600 hover:text-indigo-900 underline">solicitar matrícula en el catálogo</a>.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
