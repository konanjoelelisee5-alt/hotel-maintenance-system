@props(['last', 'submitLabel', 'submitIcon' => 'send', 'submitClass' => 'btn-success'])

{{-- Boutons d'un parcours en étapes (état Alpine : step, stepValid, canSend, btnState,
     hint, next(), back()). Collés en bas sur téléphone et tablette, sous l'étape sur
     ordinateur. « Continuer » reste grisé tant que l'étape n'est pas complète, et la
     raison s'affiche dessous : rien ne peut être oublié. --}}
<div data-thumb-bar class="fixed desk:static bottom-0 inset-x-0 z-30 px-4 tab:px-6 desk:px-0 pt-3 pb-[calc(12px+env(safe-area-inset-bottom))] desk:py-0 bg-white desk:bg-transparent border-t border-line desk:border-0 shadow-[0_-8px_24px_-16px_rgba(14,33,54,.35)] desk:shadow-none">
    <div class="max-w-[760px] flex gap-2.5">
        <button type="button" x-show="step > 1" @click="back" class="btn btn-secondary btn-lg !h-12">
            <x-hk.icon name="arrow-left" :size="16" /> Retour
        </button>
        <button type="button" x-show="step < {{ $last }}" @click="next" :disabled="!stepValid" class="btn btn-primary btn-lg !h-12 flex-1">
            Continuer <x-hk.icon name="arrow-right" :size="16" />
        </button>
        <button type="submit" x-show="step === {{ $last }}" x-cloak :disabled="!canSend" :data-state="btnState || null" class="btn {{ $submitClass }} btn-lg !h-12 flex-1">
            <x-hk.icon :name="$submitIcon" :size="16" x-show="btnState !== 'success'" />
            <x-hk.icon name="check" :size="16" x-show="btnState === 'success'" x-cloak />
            <span x-text="btnState === 'success' ? 'Envoyé' : @js($submitLabel)">{{ $submitLabel }}</span>
        </button>
    </div>
    <p x-show="!stepValid && step < {{ $last }}" class="m-0 mt-1.5 text-center desk:text-left text-[12px] text-ink-grey" x-text="hint"></p>
</div>
