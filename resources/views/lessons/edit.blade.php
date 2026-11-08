<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar lección · {{ $topic->title }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('courses.topics.lessons.update', [$course, $topic, $lesson]) }}">
                    @method('PATCH')
                    @include('lessons._form', ['submit' => 'Guardar cambios', 'cancel' => route('courses.topics.lessons.show', [$course, $topic, $lesson])])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
