{{-- Carte du bouton profil (téléphone, Housekeeping et réception) : ces rôles n'ont pas de menu
     sur téléphone, leurs pages sont dans la barre du bas et sur l'accueil. Ici : le profil, la
     déconnexion (« Changer de réceptionniste » au comptoir partagé), la version et les Nouveautés.
     Même dessin que le menu ⋮ (sheet-more-menu) : carte en haut à droite, sans voile. --}}
@php
    $user = auth()->user();
    $row = 'w-full flex items-center gap-4 min-h-12 px-4 text-left text-[15px] font-medium text-ink-deep';
@endphp
<div x-show="profileOpen" x-cloak class="tab:hidden fixed inset-0 z-[75]" role="dialog" aria-modal="true" aria-label="Mon profil"
     x-init="window.matchMedia('(min-width: 700px)').addEventListener('change', (e) => { if (e.matches) profileOpen = false })">
    <div class="absolute inset-0" @click="profileOpen = false"></div>
    <div id="profile-menu" x-show="profileOpen"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-2 top-[max(8px,env(safe-area-inset-top))] w-[min(272px,calc(100vw-16px))] origin-top-right
                bg-white rounded-[20px] border border-line shadow-[0_18px_44px_-12px_rgba(23,25,31,.35)] py-1.5">

        <a href="{{ route('profile.edit') }}" @click="profileOpen = false" class="{{ $row }} py-2 active:bg-paper">
            <span class="w-9 h-9 flex-shrink-0 rounded-full bg-ink-deep text-white text-[12px] font-bold flex items-center justify-center" aria-hidden="true">{{ $user->initialsOrGenerated() }}</span>
            <span class="min-w-0 flex flex-col leading-tight">
                <span class="truncate font-semibold">{{ $user->name }}</span>
                <span class="text-[12px] text-ink-grey">{{ $user->role?->label() }} · Mon profil</span>
            </span>
        </a>

        <div class="h-px bg-line my-1"></div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="{{ $row }} active:bg-danger-bg/60">
                @if ($user->role === \App\Enums\UserRole::Reception)
                    <x-nav-icon name="swap" class="w-5 h-5 flex-shrink-0 text-ink-grey" />
                    Changer de réceptionniste
                @else
                    <svg viewBox="0 0 24 24" class="w-5 h-5 flex-shrink-0 text-ink-grey" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 16l-4-4 4-4M6 12h10"/></svg>
                    Se déconnecter
                @endif
            </button>
        </form>
        <div class="h-px bg-line my-1"></div>
        <x-app-version class="px-4" x-on:click="profileOpen = false" />
    </div>
</div>
