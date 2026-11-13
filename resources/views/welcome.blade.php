<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'CampusEdu') }} · Plataforma de gestión académica</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100">
        <div class="min-h-screen flex flex-col items-center justify-center px-6 text-center">
            <h1 class="text-4xl sm:text-5xl font-bold text-indigo-700">CampusEdu</h1>
            <p class="mt-4 max-w-xl text-lg text-gray-600">
                Plataforma de gestión académica: los profesores organizan sus cursos en temas y lecciones
                con vídeo y recursos, y los alumnos solicitan su matrícula y consultan los contenidos.
            </p>

            <div class="mt-8 flex items-center gap-4">
                <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-md bg-gray-800 text-white text-sm font-semibold uppercase tracking-widest hover:bg-gray-700">Iniciar sesión</a>
                <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-md bg-white border border-gray-300 text-gray-700 text-sm font-semibold uppercase tracking-widest hover:bg-gray-50">Registrarse</a>
            </div>

            <p class="mt-10 text-xs text-gray-500">Proyecto de Fin de Grado · Desarrollo de Aplicaciones Web</p>
        </div>
    </body>
</html>
