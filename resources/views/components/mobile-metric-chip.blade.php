@props(['label', 'value', 'sub' => null, 'color' => 'blue', 'href' => null, 'active' => false])

@php
    $classes = 'flex-shrink-0 w-[132px] flex flex-col gap-1.5 items-start px-[14px] py-[13px] rounded-xl border '
        . ($active ? 'border-navy bg-[#EAF0F6]' : 'border-line bg-paper hover:bg-[#F3EFE6]');
@endphp

<a @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
    <span class="flex items-center gap-1.5 text-[12px] text-[#26496B] font-medium whitespace-nowrap">
        <span class="w-1.5 h-1.5 rounded-full {{ \App\Support\Swatch::bg($color) }} flex-shrink-0"></span>
        {{ $label }}
    </span>
    <span class="text-[24px] font-semibold tracking-tight leading-none text-navy">{{ $value }}</span>
    @if ($sub)
        <span class="text-[11px] text-[#9A9384] truncate">{{ $sub }}</span>
    @endif
</a>
