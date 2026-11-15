@props(['percent', 'completed', 'total'])

<div {{ $attributes }}>
    <div class="flex items-center justify-between text-xs text-gray-600">
        <span>{{ $percent }} % completado</span>
        <span>{{ $completed }} de {{ $total }} {{ (int) $total === 1 ?'lección' : 'lecciones' }}</span>
    </div>
    <div class="mt-1 h-2 w-full rounded-full bg-gray-200" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progreso en el curso">
        <div class="h-2 rounded-full bg-indigo-600" style="width: {{ $percent }}%"></div>
    </div>
</div>
