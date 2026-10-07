{{-- Bandeau « pas de connexion Internet », affiché par resources/js/ui.js (événements
     online / offline). [&[hidden]]:hidden : sinon la classe « flex » l'emporte sur hidden. --}}
<div id="offline-banner" hidden role="alert"
     class="fixed top-0 inset-x-0 z-[75] flex [&[hidden]]:hidden items-center justify-center gap-2 px-4 py-2 pt-[calc(8px+env(safe-area-inset-top))]
            bg-warn-ink text-white text-[13px] font-medium print:hidden">
    <x-hk.icon name="cloud-off" :size="16" />
    Pas de connexion Internet : vérifiez le wifi ou les données mobiles.
</div>
