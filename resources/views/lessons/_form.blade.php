@csrf
<div>
    <x-input-label for="title" value="Título" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $lesson->title ?? '')" required autofocus maxlength="255" />
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="content" value="Contenido" />
    <textarea id="content" name="content" rows="14" maxlength="20000"
        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-mono text-sm">{{ old('content', $lesson->content ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('content')" class="mt-2" />
    <p class="mt-2 text-xs text-gray-500">
        Admite formato Markdown: <code># Título</code>, <code>## Subtítulo</code>, <code>**negrita**</code>, <code>*cursiva*</code>,
        listas con <code>-</code> o <code>1.</code>, enlaces <code>[texto](https://...)</code> y bloques de código con tres acentos graves.
        El HTML escrito a mano no se muestra.
    </p>
</div>

<div class="mt-6 flex items-center gap-4">
    <x-primary-button>{{ $submit }}</x-primary-button>
    <a href="{{ $cancel }}" class="text-sm text-gray-600 hover:text-gray-900 underline">Cancelar</a>
</div>
