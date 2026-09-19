@props(['variant' => 'primary', 'href' => null, 'type' => 'submit', 'icon' => null])

@php
    $base = 'w-full inline-flex items-center gap-2.5 rounded-lg font-semibold text-[14px] h-[50px] px-4 transition';
    $variantClasses = $variant === 'primary'
        ? 'bg-navy text-white hover:bg-navy-light justify-center'
        : 'bg-white text-navy border border-line hover:bg-paper';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "$base $variantClasses"]) }}>
        @if ($icon)<x-nav-icon :name="$icon" class="w-[18px] h-[18px] flex-shrink-0" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "$base $variantClasses"]) }}>
        @if ($icon)<x-nav-icon :name="$icon" class="w-[18px] h-[18px] flex-shrink-0" />@endif
        {{ $slot }}
    </button>
@endif
