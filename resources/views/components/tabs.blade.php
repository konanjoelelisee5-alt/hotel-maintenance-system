@props([
    'items',                 // [['key', 'label', 'count'?, 'href'?], …]
    'active' => null,        // onglet actif (onglets-liens)
    'model' => null,         // ou variable Alpine de l'onglet actif (onglets dans la page)
    'variant' => 'underline',// underline (bureau) | pills (téléphone)
    'label' => 'Onglets',
])

{{-- Onglets de l'application, deux formes :
     - liens (href) : chaque onglet est une vue filtrée (listes) ;
     - dans la page (model="tab") : bascule sans recharger, l'état vit dans x-data parent.
     L'onglet actif hors de l'écran est ramené en vue (rangées qui défilent sur téléphone). --}}
@php
    $pills = $variant === 'pills';
    $base = $pills
        ? 'flex-shrink-0 inline-flex items-center gap-2 h-10 px-4 rounded-full border text-[14px] whitespace-nowrap transition'
        : 'flex items-center gap-1.5 px-3.5 pb-3 pt-1 border-b-2 text-[13.5px] whitespace-nowrap transition';
    $on = $pills ? 'bg-navy border-navy text-white font-semibold' : 'border-navy text-navy font-semibold';
    $off = $pills ? 'bg-white border-line text-navy font-medium' : 'border-transparent text-[#6C6658] font-medium hover:text-navy';
    $countOn = $pills ? 'bg-white/15 text-white' : 'text-ink-grey';
    $countOff = $pills ? 'bg-line-soft text-[#4A4639]' : 'text-ink-grey';
    $countBase = $pills ? 'min-w-[22px] h-[22px] px-1.5 rounded-full text-[11.5px] font-mono flex items-center justify-center' : 'font-mono text-[11.5px]';
@endphp

<nav {{ $attributes->merge(['class' => 'flex overflow-x-auto [scrollbar-width:none] '.($pills ? 'gap-2' : 'gap-1 -mb-px')]) }}
     aria-label="{{ $label }}" @if ($model) role="tablist" @endif
     x-data x-init="$nextTick(() => {
         const a = $el.querySelector('[aria-current=page], [aria-selected=true]');
         if (! a) return;
         const left = a.getBoundingClientRect().left - $el.getBoundingClientRect().left + $el.scrollLeft;
         if (left + a.offsetWidth > $el.scrollLeft + $el.clientWidth) $el.scrollLeft = left - 16;
     })">
    @foreach ($items as $item)
        @if ($model)
            <button type="button" role="tab" id="tab-{{ $item['key'] }}" aria-controls="panel-{{ $item['key'] }}"
                    @click="{{ $model }} = '{{ $item['key'] }}'"
                    :aria-selected="({{ $model }} === '{{ $item['key'] }}').toString()"
                    :class="{{ $model }} === '{{ $item['key'] }}' ? '{{ $on }}' : '{{ $off }}'"
                    class="{{ $base }}">
                {{ $item['label'] }}
                @isset($item['count'])
                    <span class="{{ $countBase }}" :class="{{ $model }} === '{{ $item['key'] }}' ? '{{ $countOn }}' : '{{ $countOff }}'">{{ $item['count'] }}</span>
                @endisset
            </button>
        @else
            @php $isActive = $active === $item['key']; @endphp
            <a href="{{ $item['href'] }}" @if ($isActive) aria-current="page" @endif class="{{ $base }} {{ $isActive ? $on : $off }}">
                {{ $item['label'] }}
                @isset($item['count'])
                    <span class="{{ $countBase }} {{ $isActive ? $countOn : $countOff }}">{{ $item['count'] }}</span>
                @endisset
            </a>
        @endif
    @endforeach
</nav>
