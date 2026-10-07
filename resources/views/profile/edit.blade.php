{{-- Profil, commun à tous les rôles : identité, rôle, langue, informations, mot de passe,
     déconnexion. Un mot de passe provisoire doit pouvoir être changé ici (redirection
     forcée vers profile.edit). --}}
@php
    $badge = $user->role === \App\Enums\UserRole::Housekeeping
        ? ($user->isDepartmentHead() ? 'Gouvernante' : 'Agent').' · Housekeeping'
        : $user->role_label;
    $unverified = ! $user->hasVerifiedEmail();
    $mustChange = (bool) $user->must_change_password;
    $openPassword = $mustChange || $errors->updatePassword->isNotEmpty() || session('status') === 'password-updated';
    $openInfo = $errors->has('name') || $errors->has('email') || session('status') === 'profile-updated';
    $row = 'w-full flex items-center gap-3 px-5 min-h-[56px] text-left';
    $tile = 'w-8 h-8 rounded-[8px] border border-line bg-paper text-gold flex items-center justify-center flex-shrink-0';
@endphp

<x-app-layout crumb="Mon compte" page-title="Mon profil">
    <div class="w-full max-w-[680px] flex flex-col gap-5">
        <section class="bg-white border border-line rounded-xl px-5 py-5 flex items-center gap-4">
            <span class="w-14 h-14 rounded-[12px] bg-gold text-navy text-[19px] font-bold flex items-center justify-center flex-shrink-0">{{ $user->initialsOrGenerated() }}</span>
            <div class="min-w-0 flex-1">
                <h2 class="m-0 text-[17px] font-semibold text-navy truncate">{{ $user->name }}</h2>
                <p class="m-0 mt-0.5 text-[13px] text-ink-muted truncate">{{ $user->email }}</p>
            </div>
            <span class="hidden min-[420px]:inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-info-bg text-blue whitespace-nowrap">
                {{ $badge }}
            </span>
        </section>

        @if ($unverified)
            <section class="flex flex-col min-[520px]:flex-row min-[520px]:items-center gap-3 px-5 py-4 rounded-xl bg-warn-bg text-warn-ink text-[13px]">
                <p class="m-0 flex-1">Votre adresse e-mail n'est pas encore vérifiée.</p>
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">Renvoyer le lien de vérification</button>
                </form>
            </section>
        @endif

        <section class="bg-white border border-line rounded-xl overflow-hidden divide-y divide-line-soft">
            <div class="{{ $row }}">
                <span class="{{ $tile }}"><x-hk.icon name="id-card" :size="16" /></span>
                <span class="flex-1 text-[13.5px] font-medium text-navy">Rôle</span>
                <span class="text-[13.5px] text-ink-muted">{{ $user->role_label }}</span>
            </div>
            <div class="{{ $row }}">
                <span class="{{ $tile }}"><x-hk.icon name="globe" :size="16" /></span>
                <span class="flex-1 text-[13.5px] font-medium text-navy">Langue</span>
                <span class="text-[13.5px] text-ink-muted">Français</span>
            </div>

            <div x-data="{ open: @js($openInfo) }">
                <button type="button" @click="open = !open" :aria-expanded="open" class="{{ $row }} hover:bg-paper">
                    <span class="{{ $tile }}"><x-hk.icon name="user" :size="16" /></span>
                    <span class="flex-1 text-[13.5px] font-medium text-navy">Mes informations</span>
                    <x-hk.icon name="chevron-down" :size="16" class="text-ink-grey transition-transform" ::class="open && 'rotate-180'" />
                </button>
                <div x-show="open" @if (! $openInfo) x-cloak @endif class="px-5 pb-5 pt-1">
                    {{-- Mêmes champs que le formulaire Breeze (profile.update), au style des fiches. --}}
                    <form method="POST" action="{{ route('profile.update') }}" class="ui-form flex flex-col gap-4">
                        @csrf
                        @method('patch')
                        <div>
                            <label for="name">Nom</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name" @if ($errors->has('name')) aria-invalid="true" @endif>
                            <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
                        </div>
                        <div>
                            <label for="email">Adresse e-mail</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username" @if ($errors->has('email')) aria-invalid="true" @endif>
                            <x-input-error class="mt-1.5" :messages="$errors->get('email')" />
                        </div>
                        <div>
                            <label for="profile_current_password">Mot de passe actuel</label>
                            <input id="profile_current_password" name="current_password" type="password" autocomplete="current-password" @if ($errors->has('current_password')) aria-invalid="true" @endif>
                            <p class="m-0 mt-1 text-[12px] text-ink-grey">Seulement pour changer d'adresse e-mail.</p>
                            <x-input-error class="mt-1.5" :messages="$errors->get('current_password')" />
                        </div>
                        <div><button type="submit" class="btn btn-primary">Enregistrer</button></div>
                    </form>
                </div>
            </div>

            <div x-data="{ open: @js($openPassword) }">
                <button type="button" @click="open = !open" :aria-expanded="open" class="{{ $row }} hover:bg-paper">
                    <span class="{{ $tile }}"><x-hk.icon name="lock" :size="16" /></span>
                    <span class="flex-1 text-[13.5px] font-medium text-navy">Mot de passe</span>
                    @if ($mustChange)
                        <span class="px-2.5 py-1 rounded-full bg-warn-bg text-warn-ink text-xs font-semibold">À changer</span>
                    @endif
                    <x-hk.icon name="chevron-down" :size="16" class="text-ink-grey transition-transform" ::class="open && 'rotate-180'" />
                </button>
                <div x-show="open" @if (! $openPassword) x-cloak @endif class="px-5 pb-5 pt-1">
                    {{-- Mêmes champs que le formulaire Breeze (password.update, erreurs « updatePassword »). --}}
                    @php $pwErrors = $errors->updatePassword; @endphp
                    <form method="POST" action="{{ route('password.update') }}" class="ui-form flex flex-col gap-4">
                        @csrf
                        @method('put')
                        @if ($mustChange)
                            <p class="m-0 px-3.5 py-2.5 rounded-lg bg-warn-bg text-warn-ink text-[13px]">Votre mot de passe est provisoire : choisissez-en un nouveau pour continuer.</p>
                        @endif
                        @foreach ([
                            ['update_password_current_password', 'current_password', 'Mot de passe actuel', 'current-password'],
                            ['update_password_password', 'password', 'Nouveau mot de passe', 'new-password'],
                            ['update_password_password_confirmation', 'password_confirmation', 'Confirmer le nouveau mot de passe', 'new-password'],
                        ] as [$id, $field, $label, $autocomplete])
                            <div>
                                <label for="{{ $id }}">{{ $label }}</label>
                                <input id="{{ $id }}" name="{{ $field }}" type="password" autocomplete="{{ $autocomplete }}" @if ($pwErrors->has($field)) aria-invalid="true" @endif>
                                <x-input-error class="mt-1.5" :messages="$pwErrors->get($field)" />
                            </div>
                        @endforeach
                        <div><button type="submit" class="btn btn-primary">Changer le mot de passe</button></div>
                    </form>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-danger btn-lg w-full"><x-hk.icon name="log-out" :size="16" /> Se déconnecter</button>
        </form>
    </div>
</x-app-layout>
