{{-- Signalements gardés dans le téléphone faute de réseau (resources/js/hk-outbox.js) :
     renvoyés au retour du réseau, puis toutes les 30 s tant qu'il en reste. --}}
<div x-data="{
        items: [], errors: {}, busy: false,
        async load() { this.items = window.hkOutbox?.supported ? await window.hkOutbox.all() : []; },
        async flush() {
            if (this.busy || ! this.items.length) return;
            this.busy = true;
            for (const entry of this.items) {
                const result = await window.hkOutbox.send(entry);
                if (result.ok) {
                    await window.hkOutbox.remove(entry.id);
                    window.dispatchEvent(new CustomEvent('hk-toast', { detail: 'Signalement envoyé : ' + (entry.label || 'en attente') }));
                } else if (! result.retry) {
                    this.errors[entry.id] = result.error;
                }
            }
            this.busy = false;
            await this.load();
        },
        async drop(id) { await window.hkOutbox.remove(id); await this.load(); },
     }"
     x-init="load().then(() => flush()); setInterval(() => flush(), 30000)"
     @online.window="flush()" @hk-outbox-changed.window="load()"
     x-show="items.length" x-cloak
     class="border border-amber/30 rounded-[22px] overflow-hidden" role="status">
    <div class="flex items-start gap-3 px-4 py-3 bg-warn-bg">
        <x-hk.icon name="cloud-off" :size="18" class="text-amber mt-0.5" />
        <div class="flex-1 min-w-0">
            <p class="m-0 text-[13.5px] font-semibold text-warn-ink" x-text="items.length > 1 ? items.length + ' signalements en attente d\'envoi' : 'Un signalement en attente d\'envoi'"></p>
            <p class="m-0 text-[12.5px] text-warn-ink/80">Gardé dans ce téléphone : il part tout seul dès que le réseau revient.</p>
        </div>
        <button type="button" @click="flush()" :disabled="busy" class="btn btn-sm btn-secondary flex-shrink-0" x-text="busy ? 'Envoi…' : 'Réessayer'"></button>
    </div>
    <template x-for="entry in items" :key="entry.id">
        <div class="flex items-center gap-3 px-4 py-2.5 border-t border-line-soft">
            <div class="flex-1 min-w-0">
                <p class="m-0 text-[13px] font-medium text-navy truncate" x-text="entry.label || 'Signalement'"></p>
                <p class="m-0 text-[12px]" :class="errors[entry.id] ? 'text-red' : 'text-ink-grey'"
                   x-text="errors[entry.id] || ('Gardé à ' + new Date(entry.savedAt).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }))"></p>
            </div>
            <button type="button" @click="drop(entry.id)" class="btn btn-sm btn-ghost !text-red flex-shrink-0">Abandonner</button>
        </div>
    </template>
</div>
