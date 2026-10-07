<x-app-layout crumb="Administration" page-title="Paramètres">
    {{-- Accueil de l'espace « Paramètres » : ce que l'on configure une fois puis qu'on
         ajuste rarement. Chaque carte ouvre l'écran correspondant. Liste des écrans :
         App\Support\Navigation::settings(). --}}
    <p class="m-0 -mt-1 text-[13.5px] text-ink-muted max-w-2xl leading-relaxed">
        Comptes, règles et données de référence de l'application. Ces réglages s'appliquent
        à tout le service ; les changements sensibles sont inscrits au journal d'activité.
    </p>

    @foreach ($groups as $group => $items)
        <section class="flex flex-col gap-3" aria-labelledby="settings-{{ \Illuminate\Support\Str::slug($group) }}">
            <h2 id="settings-{{ \Illuminate\Support\Str::slug($group) }}" class="m-0 text-[12px] font-semibold uppercase tracking-wide text-ink-muted">{{ $group }}</h2>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($items as $item)
                    <a href="{{ route($item['route']) }}"
                       class="group flex items-start gap-3.5 p-4 rounded-xl bg-white border border-line hover:border-navy/40 hover:shadow-[0_6px_18px_-10px_rgba(11,27,44,.35)] transition">
                        <span class="w-10 h-10 flex-shrink-0 rounded-[10px] bg-paper border border-line flex items-center justify-center text-gold">
                            <x-nav-icon :name="$item['icon']" class="w-5 h-5" />
                        </span>
                        <span class="flex flex-col gap-1 min-w-0">
                            <span class="text-[14.5px] font-semibold text-navy group-hover:underline">{{ $item['label'] }}</span>
                            <span class="text-[12.5px] text-ink-muted leading-snug">{{ $item['description'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
</x-app-layout>
