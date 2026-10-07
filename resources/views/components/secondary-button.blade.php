{{-- Compatibilité Breeze : même rendu que <x-button variant="secondary">. --}}
@props(['href' => null])

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class('btn btn-secondary') }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class('btn btn-secondary') }}>
        {{ $slot }}
    </button>
@endif
