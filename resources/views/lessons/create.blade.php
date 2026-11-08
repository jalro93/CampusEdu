<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nueva lección · {{ $topic->title }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('courses.topics.lessons.store', [$course, $topic]) }}">
                    @include('lessons._form', ['submit' => 'Crear lección', 'cancel' => route('courses.show', $course)])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
