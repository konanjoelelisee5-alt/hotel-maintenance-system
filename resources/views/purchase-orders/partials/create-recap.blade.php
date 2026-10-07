{{-- Récapitulatif du nouveau bon de commande (état Alpine de purchase-orders.create) :
     à la dernière étape sur téléphone et tablette, fixé à droite sur ordinateur. --}}
<section class="bg-white border border-line rounded-xl overflow-hidden">
    <header class="flex items-center gap-3 px-5 py-3.5 border-b border-line-soft">
        <span class="w-8 h-8 rounded-[8px] border border-line bg-paper text-gold flex items-center justify-center flex-shrink-0"><x-hk.icon name="clipboard" :size="16" /></span>
        <h3 class="m-0 flex-1 text-[14.5px] font-semibold text-navy">Récapitulatif</h3>
    </header>
    <dl class="m-0 px-5 py-1 text-[13.5px]">
        <div class="flex items-start gap-3 py-3 border-b border-line-soft">
            <span class="flex flex-col gap-0.5 min-w-0 flex-1">
                <dt class="text-[11.5px] font-semibold uppercase tracking-wide text-ink-grey">Fournisseur</dt>
                <dd class="m-0" :class="supplierName ? 'text-navy font-medium' : 'text-ink-faint'" x-text="supplierName || 'À compléter'"></dd>
            </span>
            <button type="button" x-show="step > 1" @click="goTo(1)" class="h-8 px-2 -mr-2 rounded-md text-[12.5px] font-semibold text-navy hover:bg-paper flex-shrink-0">Modifier</button>
        </div>
        <div class="flex items-start gap-3 py-3 border-b border-line-soft">
            <span class="flex flex-col gap-0.5 min-w-0 flex-1">
                <dt class="text-[11.5px] font-semibold uppercase tracking-wide text-ink-grey">Articles</dt>
                <dd class="m-0 text-navy font-medium" x-text="items.length + ' article(s), ' + items.filter(i => i.part_id).length + ' relié(s) au stock'"></dd>
            </span>
            <button type="button" x-show="step > 2" @click="goTo(2)" class="h-8 px-2 -mr-2 rounded-md text-[12.5px] font-semibold text-navy hover:bg-paper flex-shrink-0">Modifier</button>
        </div>
        <div class="py-3">
            <dt class="text-[11.5px] font-semibold uppercase tracking-wide text-ink-grey">Total de la commande</dt>
            <dd class="m-0 mt-0.5 font-mono text-[24px] font-semibold text-navy" x-text="fcfa(total)"></dd>
        </div>
    </dl>
</section>
