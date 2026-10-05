{{-- Profil du Housekeeping : identité, rôle, langue, déconnexion. Les formulaires Breeze
     (informations, mot de passe) restent accessibles : un mot de passe provisoire doit
     pouvoir être changé ici (redirection forcée vers profile.edit). --}}
@php
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
                <p class="m-0 mt-0.5 text-[13px] text-[#6C6658] truncate">{{ $user->email }}</p>
            </div>
            <span class="hidden min-[420px]:inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-[#EAF0F6] text-[#26496B] whitespace-nowrap">
                {{ $user->isDepartmentHead() ? 'Gouvernante' : 'Agent' }} · Housekeeping
            </span>
        </section>

        <section class="bg-white border border-line rounded-xl overflow-hidden divide-y divide-line-soft">
            <div class="{{ $row }}">
                <span class="{{ $tile }}"><x-hk.icon name="id-card" :size="16" /></span>
                <span class="flex-1 text-[13.5px] font-medium text-navy">Rôle</span>
                <span class="text-[13.5px] text-[#6C6658]">{{ $user->role_label }}</span>
            </div>
            <div class="{{ $row }}">
                <span class="{{ $tile }}"><x-hk.icon name="globe" :size="16" /></span>
                <span class="flex-1 text-[13.5px] font-medium text-navy">Langue</span>
                <span class="text-[13.5px] text-[#6C6658]">Français</span>
            </div>

            <div x-data="{ open: @js($openInfo) }">
                <button type="button" @click="open = !open" :aria-expanded="open" class="{{ $row }} hover:bg-paper">
                    <span class="{{ $tile }}"><x-hk.icon name="user" :size="16" /></span>
                    <span class="flex-1 text-[13.5px] font-medium text-navy">Mes informations</span>
                    <x-hk.icon name="chevron-down" :size="16" class="text-ink-grey transition-transform" ::class="open && 'rotate-180'" />
                </button>
                <div x-show="open" @if (! $openInfo) x-cloak @endif class="px-5 pb-5 pt-1">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div x-data="{ open: @js($openPassword) }">
                <button type="button" @click="open = !open" :aria-expanded="open" class="{{ $row }} hover:bg-paper">
                    <span class="{{ $tile }}"><x-hk.icon name="lock" :size="16" /></span>
                    <span class="flex-1 text-[13.5px] font-medium text-navy">Mot de passe</span>
                    @if ($mustChange)
                        <span class="px-2.5 py-1 rounded-full bg-[#FBF1DF] text-[#7A5A16] text-xs font-semibold">À changer</span>
                    @endif
                    <x-hk.icon name="chevron-down" :size="16" class="text-ink-grey transition-transform" ::class="open && 'rotate-180'" />
                </button>
                <div x-show="open" @if (! $openPassword) x-cloak @endif class="px-5 pb-5 pt-1">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-danger btn-lg w-full"><x-hk.icon name="log-out" :size="16" /> Se déconnecter</button>
        </form>
    </div>
</x-app-layout>
