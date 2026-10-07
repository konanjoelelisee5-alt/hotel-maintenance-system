{{-- « Voir en tant que » (accès ouvert seulement, App\Support\OpenAccess) : bouton flottant
     pour se connecter comme un compte de chaque rôle et parcourir ses écrans.
     Téléphone : au-dessus de la barre du bas ; tablette et ordinateur : en bas à droite. --}}
@if (\App\Support\OpenAccess::enabled())
    @php $current = auth()->user(); @endphp
    <div x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false"
         class="print:hidden fixed right-3 bottom-[calc(140px+env(safe-area-inset-bottom))] tab:right-5 tab:bottom-5 z-[70] flex flex-col items-end gap-2">
        <div x-show="open" x-cloak x-transition.opacity
             class="w-[260px] max-h-[70vh] overflow-y-auto bg-white rounded-xl border border-line shadow-[0_18px_40px_-12px_rgba(11,27,44,.45)] p-1.5">
            <p class="m-0 px-2.5 pt-1.5 pb-2 text-[11px] font-semibold uppercase tracking-wide text-ink-grey">Voir en tant que</p>
            @foreach (\App\Support\OpenAccess::profiles() as $profile)
                @php $isCurrent = $profile['user']->is($current); @endphp
                <form method="POST" action="{{ route('open-access.switch', $profile['user']) }}">
                    @csrf
                    <button type="submit" @disabled($isCurrent)
                            class="w-full flex flex-col items-start gap-0.5 px-2.5 py-2 rounded-lg text-left {{ $isCurrent ? 'bg-paper' : 'hover:bg-paper' }}">
                        <span class="text-[13px] font-semibold text-navy">{{ $profile['label'] }}{{ $isCurrent ? ' · actuel' : '' }}</span>
                        <span class="text-[11.5px] text-ink-grey truncate max-w-full">{{ $profile['user']->name }}</span>
                    </button>
                </form>
            @endforeach
        </div>
        <button type="button" @click="open = ! open" :aria-expanded="open"
                class="h-10 pl-3 pr-3.5 rounded-full bg-gold text-navy text-[12.5px] font-semibold flex items-center gap-2 shadow-[0_10px_24px_-10px_rgba(11,27,44,.6)]">
            <x-nav-icon name="swap" class="w-4 h-4" />
            {{ $current->role_label }}
        </button>
    </div>
@endif
