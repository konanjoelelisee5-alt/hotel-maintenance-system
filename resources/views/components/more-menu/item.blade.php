@props([
    'href' => null,      // lien simple (ni href ni action : bouton, ex. x-on:click)
    'action' => null,    // ou formulaire : URL d'envoi…
    'method' => 'POST',  // …et méthode (POST, PATCH, DELETE)
    'icon' => null,
    'danger' => false,   // action destructive : rouge, à placer en dernier, après le séparateur
    'confirm' => null,   // message de la fenêtre de confirmation (cf. resources/js/confirm.js)
    'confirmTitle' => null,
    'confirmLabel' => null,
    'modal' => false,    // ouvre le lien dans une fenêtre (data-modal)
])

@php
    $classes = 'w-full flex items-center gap-3 px-4 py-3 lg:py-2.5 text-left text-[14px] lg:text-[13.5px] font-medium '
        .($danger ? 'text-red hover:bg-[#FDECEA] focus:bg-[#FDECEA]' : 'text-[#26302B] hover:bg-paper focus:bg-paper')
        .' focus:outline-none';
@endphp

@if ($action)
    <form method="POST" action="{{ $action }}"
          @if ($confirm)
              data-confirm="{{ $confirm }}"
              @if ($confirmTitle) data-confirm-title="{{ $confirmTitle }}" @endif
              @if ($confirmLabel) data-confirm-label="{{ $confirmLabel }}" @endif
              @if ($danger) data-confirm-tone="danger" @endif
          @endif>
        @csrf
        @if (strtoupper($method) !== 'POST')
            @method($method)
        @endif
        <button type="submit" role="menuitem" {{ $attributes->merge(['class' => $classes]) }}>
            @if ($icon)
                <x-nav-icon :name="$icon" class="w-[18px] h-[18px] flex-shrink-0 {{ $danger ? '' : 'text-ink-grey' }}" />
            @endif
            <span>{{ $slot }}</span>
        </button>
    </form>
@elseif (! $href)
    <button type="button" role="menuitem" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <x-nav-icon :name="$icon" class="w-[18px] h-[18px] flex-shrink-0 {{ $danger ? '' : 'text-ink-grey' }}" />
        @endif
        <span>{{ $slot }}</span>
    </button>
@else
    <a href="{{ $href }}" role="menuitem" @if ($modal) data-modal @endif {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <x-nav-icon :name="$icon" class="w-[18px] h-[18px] flex-shrink-0 {{ $danger ? '' : 'text-ink-grey' }}" />
        @endif
        <span>{{ $slot }}</span>
    </a>
@endif
