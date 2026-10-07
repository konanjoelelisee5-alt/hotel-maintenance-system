{{-- État vide d'une section de la page « Chambres bloquées ». --}}
<div class="flex items-center gap-3 px-5 py-5">
    <span class="w-8 h-8 flex-shrink-0 rounded-full bg-ok-bg text-green flex items-center justify-center">
        <x-nav-icon :name="$icon" class="w-4 h-4" />
    </span>
    <p class="m-0 text-[13px] text-ink-grey">{{ $text }}</p>
</div>
