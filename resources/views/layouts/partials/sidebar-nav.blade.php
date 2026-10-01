{{-- Contenu de la navigation, partagé par la sidebar desktop et le panneau « Menu » mobile.
     Une entrée reste active sur ses sous-pages (users.index → users.edit, users.create...). --}}
@php $isActive = fn (array $item) => request()->routeIs(\Illuminate\Support\Str::beforeLast($item['route'], '.').'.*'); @endphp

<nav class="flex flex-col gap-0.5">
    <div class="text-[10.5px] tracking-wide text-[#6E8399] font-semibold uppercase px-2 pb-1.5">Opérations</div>
    @foreach ($nav['ops'] as $item)
        @php $active = $isActive($item); @endphp
        <a href="{{ route($item['route'], $item['params'] ?? []) }}" @if ($active) aria-current="page" @endif class="flex items-center justify-between gap-2 px-2.5 py-2 rounded-lg text-[13.5px] font-medium {{ $active ? 'bg-white/10 text-white' : 'text-[#DCE5EE] hover:bg-white/[.06]' }}">
            <span class="flex items-center gap-2.5">
                @if (! empty($item['icon']))
                    <x-nav-icon :name="$item['icon']" class="w-4 h-4 {{ $active ? 'text-gold' : 'text-[#8FA3B8]' }}" />
                @else
                    <span class="w-1.5 h-1.5 rounded-full {{ $active ? 'bg-gold' : 'bg-[#3F5D7A]' }}"></span>
                @endif
                {{ $item['label'] }}
            </span>
            @if (! empty($item['badge']))
                <span class="text-[11px] font-semibold text-gold">{{ $item['badge'] }}</span>
            @endif
        </a>
    @endforeach
</nav>

@if (! empty($nav['admin']))
    <nav class="flex flex-col gap-0.5">
        <div class="text-[10.5px] tracking-wide text-[#6E8399] font-semibold uppercase px-2 pb-1.5">{{ $nav['adminTitle'] }}</div>
        @foreach ($nav['admin'] as $item)
            @php $active = $isActive($item); @endphp
            <a href="{{ route($item['route'], $item['params'] ?? []) }}" @if ($active) aria-current="page" @endif class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[13px] font-medium {{ $active ? 'bg-white/10 text-white' : 'text-[#B9C7D6] hover:bg-white/[.06] hover:text-white' }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
@endif

<div class="mt-auto flex flex-col gap-2">
    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg bg-white/[.06] text-[#C3D0DE] text-[12.5px] font-medium hover:bg-white/[.12]">
        Mon profil
    </a>
    <div class="px-2.5 py-2.5 rounded-[9px] bg-white/[.06] text-[11px] text-[#9FB2C5] leading-relaxed">
        Connecté en tant que {{ auth()->user()->role_label }}
    </div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="w-full text-left px-2.5 py-2 rounded-lg text-[12.5px] font-medium text-[#E7B3AE] hover:bg-white/[.06]">Se déconnecter</button>
    </form>
</div>
