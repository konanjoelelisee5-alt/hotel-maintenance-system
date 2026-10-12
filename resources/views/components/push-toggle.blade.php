{{-- « Recevoir les notifications sur cet appareil » (push), en haut de la page
     Notifications. Logique : resources/js/push.js (pushToggle). Chaque appareil s'active
     à part : téléphone, ordinateur du bureau… --}}
@if (config('webpush.vapid.public_key'))
    <div x-data="pushToggle" class="bg-white border border-line rounded-xl px-4 py-3.5 flex items-center gap-3.5">
        <span class="w-10 h-10 flex-shrink-0 rounded-full flex items-center justify-center"
              :class="state === 'on' ? 'bg-green/10 text-green' : 'bg-line-soft text-ink-grey'">
            <x-nav-icon name="bell" class="w-5 h-5" />
        </span>
        <div class="flex-1 min-w-0">
            <div class="text-[14px] font-semibold">Notifications sur cet appareil</div>
            <div class="text-[12.5px] text-ink-muted leading-snug">
                <span x-show="state === 'on'" x-cloak>Activées : la cloche vous prévient même application fermée.</span>
                <span x-show="state === 'off'" x-cloak>Soyez prévenu même application fermée, comme un SMS.</span>
                <span x-show="state === 'denied'" x-cloak>Bloquées par le navigateur : autorisez-les dans ses réglages (cadenas à côté de l'adresse), puis rechargez la page.</span>
                <span x-show="state === 'install'" x-cloak>Sur iPhone, installez d'abord l'application : bouton Partager, puis « Sur l'écran d'accueil ». Ouvrez-la depuis son icône.</span>
                <span x-show="state === 'unsupported'" x-cloak>Pas disponible ici : il faut une adresse sécurisée (https) et un navigateur récent.</span>
                <span x-show="failed" x-cloak class="block text-red">L'activation a échoué. Vérifiez la connexion et réessayez.</span>
            </div>
        </div>
        <button type="button" x-show="state === 'off'" x-cloak @click="enable()" :disabled="busy" class="btn btn-sm btn-primary flex-shrink-0">Activer</button>
        <button type="button" x-show="state === 'on'" x-cloak @click="disable()" :disabled="busy" class="btn btn-sm btn-secondary flex-shrink-0">Couper</button>
    </div>
@endif
