<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo curso</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('courses.store') }}">
                    @include('courses._form', ['submit' => 'Crear curso', 'cancel' => route('courses.index')])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
