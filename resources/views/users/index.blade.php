@php
    $search = request('search');
    $tabItems = collect($tabs)->map(fn ($label, $key) => [
        'key' => $key,
        'label' => $label,
        'count' => $counts[$key],
        'href' => route('users.index', array_filter(['role' => $key, 'search' => $search], 'filled')),
    ])->values()->all();
@endphp

<x-app-layout :crumb="'Paramètres'" :page-title="'Utilisateurs & rôles'" :back-route="route('settings.index')">
    <x-slot:primaryAction>
        <a href="{{ route('users.create') }}" data-modal class="btn btn-primary">+ Nouvel utilisateur</a>
    </x-slot:primaryAction>

    {{-- Mot de passe provisoire : affiché une seule fois, juste après sa création. --}}
    @if (session('temporary_password'))
        <div class="flex flex-col gap-2 p-4 rounded-xl border border-gold/40 bg-warn-bg text-[#4A3B12]" role="status">
            <div class="text-[13.5px]">
                Mot de passe provisoire de <strong>{{ session('temporary_password')['name'] }}</strong> :
                <code class="ml-1 px-2 py-0.5 rounded-md bg-white border border-gold/30 font-mono text-[15px] select-all">{{ session('temporary_password')['password'] }}</code>
            </div>
            <p class="m-0 text-[12.5px]">Notez-le maintenant : il ne sera plus jamais affiché. Transmettez-le en main propre, pas par un canal partagé.</p>
        </div>
    @endif

    <section class="bg-white border border-line rounded-xl min-w-0">
        <div class="flex items-end gap-4 flex-wrap px-4 sm:px-6 pt-5 border-b border-line">
            <div class="flex flex-col gap-0.5 pb-3.5">
                <h2 class="m-0 text-[17px] font-semibold text-navy">{{ $tabs[$role] === 'Tous' ? 'Tous les comptes' : $tabs[$role] }}</h2>
                <div class="text-[12.5px] text-ink-muted">{{ $users->total() }} compte(s) · actifs en premier</div>
            </div>
            <x-tabs :items="$tabItems" :active="$role" label="Rôles" class="sm:ml-auto max-w-full" />
        </div>

        <form method="GET" action="{{ route('users.index') }}" class="flex items-center gap-2.5 flex-wrap px-4 sm:px-6 py-3.5 bg-paper/60 border-b border-line-soft">
            <input type="hidden" name="role" value="{{ $role }}">
            <label class="flex items-center gap-2 h-[40px] w-full sm:w-[320px] px-3 rounded-[9px] border border-line bg-white text-[13px] focus-within:border-navy">
                <svg class="w-4 h-4 text-ink-grey flex-shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="m14 14 4 4" stroke-linecap="round"/></svg>
                <span class="sr-only">Rechercher</span>
                <input type="search" name="search" value="{{ $search }}" placeholder="Nom ou e-mail…" class="flex-1 min-w-0 border-0 p-0 bg-transparent text-[13.5px] placeholder:text-ink-grey focus:ring-0">
            </label>
            @if (filled($search))
                <a href="{{ route('users.index', array_filter(['role' => $role], 'filled')) }}" class="text-[12.5px] font-semibold text-ink-muted hover:text-navy">Effacer la recherche</a>
            @endif
        </form>

        @if ($users->isEmpty())
            <div class="px-5 py-12 flex flex-col items-center gap-2 text-center">
                <div class="w-[42px] h-[42px] rounded-full bg-line-soft flex items-center justify-center text-ink-grey"><x-nav-icon name="users" class="w-5 h-5" /></div>
                <div class="text-[14px] font-semibold">Aucun compte ne correspond</div>
                <div class="text-[12.5px] text-ink-muted">Changez d'onglet ou effacez la recherche.</div>
            </div>
        @else
            <ul class="m-0 p-0 list-none divide-y divide-line-soft">
                @foreach ($users as $user)
                    <li class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 sm:px-6 py-3.5 {{ $user->is_active ? '' : 'bg-paper/50' }}">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <span class="w-10 h-10 rounded-full flex items-center justify-center text-[12.5px] font-bold flex-shrink-0 {{ $user->is_active ? 'bg-gold text-navy' : 'bg-line text-ink-muted' }}">{{ $user->initialsOrGenerated() }}</span>
                            <span class="flex flex-col gap-0.5 min-w-0">
                                <span class="flex items-center gap-2 flex-wrap">
                                    <span class="text-[14.5px] font-semibold text-navy">{{ $user->name }}</span>
                                    @unless ($user->is_active)
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ \App\Support\Swatch::pill('muted') }}">Désactivé</span>
                                    @endunless
                                    @if ($user->must_change_password && $user->is_active)
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ \App\Support\Swatch::pill('amber') }}">Mot de passe provisoire</span>
                                    @endif
                                    @if ($user->id === auth()->id())
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ \App\Support\Swatch::pill('blue') }}">Vous</span>
                                    @endif
                                </span>
                                <span class="text-[12.5px] text-ink-muted truncate">{{ $user->email }}</span>
                            </span>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-3 sm:w-[340px] flex-shrink-0 pl-[52px] sm:pl-0">
                            <span class="text-[13px] text-ink-body">{{ $user->role_label }}</span>
                            <span class="flex items-center gap-2">
                                <a href="{{ route('users.edit', $user) }}" data-modal class="btn btn-sm btn-secondary">Modifier</a>
                                @if ($user->is_active && $user->id !== auth()->id())
                                    <x-more-menu class="!h-8 !w-8" :label="'Plus d\'actions pour '.$user->name">
                                        {{-- Écran intermédiaire : ses OT en cours doivent d'abord être confiés à quelqu'un. --}}
                                        <x-more-menu.item :href="route('users.deactivate', $user)" icon="ban" danger>Désactiver le compte…</x-more-menu.item>
                                    </x-more-menu>
                                @endif
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($users->hasPages())
                <div class="px-4 sm:px-6 py-3.5 border-t border-line bg-paper/60 rounded-b-xl">{{ $users->links() }}</div>
            @endif
        @endif
    </section>
</x-app-layout>
