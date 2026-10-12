{{-- Page « Nouveautés » : le journal des versions (resources/changelog.php), seulement ce qui
     concerne le rôle de la personne, du plus récent au plus ancien. Les versions qu'elle
     n'avait pas encore lues ($seen) portent la mention « Nouveau pour vous ».
     Données : ReleaseNotesController::index(). --}}
@use('App\Support\AppVersion')
@use('App\Support\Swatch')
@php
    $isNew = fn (array $release) => version_compare($release['version'], $seen ?? '0', '>');
@endphp

<x-app-layout crumb="Mon activité" page-title="Nouveautés">
    <div class="flex flex-col gap-5 max-w-3xl">
        <p class="m-0 text-[14px] text-ink-muted leading-relaxed">
            Vous utilisez la <strong class="text-ink-deep">version {{ AppVersion::current() }}</strong>.
            @if (AppVersion::isPreRelease())
                C'est une version de <strong class="text-ink-deep">test</strong> : si quelque chose ne marche pas, signalez-le en donnant ce numéro.
            @endif
        </p>

        @forelse ($releases as $release)
            <section class="bg-white border {{ $isNew($release) ? 'border-blue/40' : 'border-line' }} rounded-xl overflow-hidden"
                     aria-labelledby="version-{{ $loop->index }}">
                <header class="px-5 py-4 border-b border-line-soft flex flex-wrap items-center gap-x-3 gap-y-1">
                    <h2 id="version-{{ $loop->index }}" class="m-0 text-[16px] font-bold text-ink-deep">
                        Version <span class="font-mono text-[15px]">{{ $release['version'] }}</span>
                    </h2>
                    @if (AppVersion::isPreRelease($release['version']))
                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wide {{ Swatch::pill('amber') }}">Test</span>
                    @endif
                    @if ($isNew($release))
                        <span class="px-2 py-0.5 rounded-full text-[11.5px] font-semibold {{ Swatch::pill('blue') }}">Nouveau pour vous</span>
                    @endif
                    <span class="w-full sm:w-auto sm:ml-auto text-[12.5px] text-ink-grey">
                        {{ AppVersion::date($release)->locale('fr')->isoFormat('D MMMM YYYY') }}
                    </span>
                    @if (! empty($release['title']))
                        <p class="m-0 w-full text-[13.5px] text-ink-muted">{{ $release['title'] }}</p>
                    @endif
                </header>
                <ul class="m-0 p-0 list-none divide-y divide-line-soft">
                    @foreach ($release['changes'] as $change)
                        @php [$typeLabel, $typeColor] = AppVersion::TYPES[$change['type']]; @endphp
                        <li class="px-5 py-3.5 flex flex-col sm:flex-row gap-1.5 sm:gap-4">
                            <span class="flex-shrink-0 self-start sm:w-[92px]">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[11.5px] font-semibold {{ Swatch::pill($typeColor) }}">{{ $typeLabel }}</span>
                            </span>
                            <span class="text-[14px] leading-relaxed text-ink-strong">{{ $change['text'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <div class="bg-white border border-line rounded-xl px-5 py-14 flex flex-col items-center gap-2 text-center">
                <div class="text-[14px] font-semibold">Rien de nouveau pour vous</div>
                <div class="text-[12.5px] text-ink-grey">Les changements qui vous concernent apparaîtront ici.</div>
            </div>
        @endforelse
    </div>
</x-app-layout>
