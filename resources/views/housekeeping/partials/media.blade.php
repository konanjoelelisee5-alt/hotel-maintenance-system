{{-- Messages vocaux et photos d'un envoi du service (signalement ou précision). $files : pièces jointes. --}}
@php
    $audios = $files->filter(fn ($a) => str_starts_with($a->mime_type, 'audio/'));
    $images = $files->filter(fn ($a) => str_starts_with($a->mime_type, 'image/'));
@endphp

@foreach ($audios as $audio)
    <audio controls preload="metadata" src="{{ $audio->url }}" class="w-full h-10"></audio>
@endforeach
@if ($images->isNotEmpty())
    <div class="grid {{ $images->count() > 1 ? 'grid-cols-2' : 'grid-cols-1' }} gap-2">
        @foreach ($images as $image)
            <a href="{{ $image->url }}" target="_blank" class="block rounded-[10px] overflow-hidden border border-line bg-paper">
                <img src="{{ $image->url }}" alt="Photo du signalement" class="w-full max-h-[280px] object-cover">
            </a>
        @endforeach
    </div>
@endif
