<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-lg font-medium">Hola, {{ auth()->user()->name }}.</p>
                    <p class="mt-1 text-sm text-gray-600">Bienvenido a CampusEdu. ¿Qué quieres hacer?</p>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <a href="{{ route('courses.index') }}"><x-primary-button type="button">Mis cursos</x-primary-button></a>
                        <a href="{{ route('catalog.index') }}"><x-secondary-button type="button">Catálogo de cursos</x-secondary-button></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
