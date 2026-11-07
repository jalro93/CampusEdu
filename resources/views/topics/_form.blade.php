@csrf
<div>
    <x-input-label for="title" value="Título" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $topic->title ?? '')" required autofocus maxlength="255" />
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div class="mt-6 flex items-center gap-4">
    <x-primary-button>{{ $submit }}</x-primary-button>
    <a href="{{ $cancel }}" class="text-sm text-gray-600 hover:text-gray-900 underline">Cancelar</a>
</div>
