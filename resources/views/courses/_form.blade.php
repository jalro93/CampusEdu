@csrf
<div>
    <x-input-label for="title" value="Título" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $course->title ?? '')" required autofocus maxlength="255" />
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="description" value="Descripción" />
    <textarea id="description" name="description" rows="6" maxlength="5000"
        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $course->description ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="mt-6 flex items-center gap-4">
    <x-primary-button>{{ $submit }}</x-primary-button>
    <a href="{{ $cancel }}" class="text-sm text-gray-600 hover:text-gray-900 underline">Cancelar</a>
</div>
