{{-- Bas des menus (sidebar et panneau du téléphone : sheet-nav ; menu ⋮ : sheet-more-menu) :
     « Version 0.9.0-beta.1 [Test] · Nouveautés », lien vers le journal des versions, avec un
     point bleu tant qu'une version qui concerne la personne n'a pas été lue. --}}
@use('App\Support\AppVersion')
@php $unseen = AppVersion::hasUnseen(auth()->user()); @endphp

<a href="{{ route('release-notes.index') }}"
   {{ $attributes->class('flex items-center gap-2 min-h-11 text-[12px] text-ink-grey hover:text-ink-deep transition-colors') }}>
    <span class="font-mono tabular truncate">v{{ AppVersion::current() }}</span>
    @if (AppVersion::isPreRelease())
        <span class="flex-shrink-0 px-1.5 py-px rounded-md text-[10.5px] font-bold uppercase tracking-wide bg-warn-bg text-warn-ink">Test</span>
    @endif
    <span class="ml-auto flex-shrink-0 inline-flex items-center gap-1.5 font-medium {{ $unseen ? 'text-ink-deep' : '' }}">
        Nouveautés
        @if ($unseen)
            <span class="w-2 h-2 rounded-full bg-blue" aria-hidden="true"></span>
            <span class="sr-only">(non lues)</span>
        @endif
    </span>
</a>
