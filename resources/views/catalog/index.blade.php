<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Catálogo de cursos</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <p class="mb-4 text-sm text-gray-600">Solicita tu matrícula en un curso. Podrás acceder a su contenido cuando el profesor la apruebe.</p>

            @forelse ($courses as $course)
                @php($estado = $statuses[$course->id] ?? null)
                <div class="mb-3 p-5 bg-white shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <span class="font-medium text-gray-900">{{ $course->title }}</span>
                            @if ($course->users->isNotEmpty())
                                <p class="text-xs text-gray-500">Profesorado: {{ $course->users->pluck('name')->join(', ') }}</p>
                            @endif
                        </div>

                        @if ($estado === \App\Models\EnrollmentRequest::PENDING)
                            <span class="text-xs uppercase tracking-wide px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">Solicitud pendiente</span>
                        @else
                            <div class="flex items-center gap-3">
                                @if ($estado === \App\Models\EnrollmentRequest::REJECTED)
                                    <span class="text-xs uppercase tracking-wide px-2 py-1 rounded-full bg-red-100 text-red-800">Solicitud rechazada</span>
                                @endif
                                <form method="POST" action="{{ route('courses.enrollment-requests.store', $course) }}">
                                    @csrf
                                    <x-primary-button>{{ $estado === \App\Models\EnrollmentRequest::REJECTED ? 'Volver a solicitar' : 'Solicitar matrícula' }}</x-primary-button>
                                </form>
                            </div>
                        @endif
                    </div>
                    @if ($course->description)
                        <p class="mt-2 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($course->description, 200) }}</p>
                    @endif
                </div>
            @empty
                <div class="p-6 bg-white shadow-sm sm:rounded-lg text-gray-600">No hay cursos disponibles en este momento.</div>
            @endforelse

            <div class="mt-4">{{ $courses->links() }}</div>
        </div>
    </div>
</x-app-layout>
