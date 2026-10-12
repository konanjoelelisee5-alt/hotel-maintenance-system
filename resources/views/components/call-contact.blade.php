@props(['person', 'variant' => 'round'])

{{-- Contacter quelqu'un (astreinte) avec les moyens de celui qui appelle :
       - téléphone : « Appeler » ouvre directement le composeur (crédit / unités),
         « WhatsApp » appelle par Internet (data) ;
       - ordinateur (souris) : il ne peut pas téléphoner, « Appeler » affiche donc la fiche
         du contact (numéro en grand, Copier, WhatsApp) pour composer depuis son téléphone.
     variant : « round » (pastilles de la carte d'astreinte) ou « button » (bouton compact). --}}
@php
    $tel = $person->dialablePhone();
    $whatsapp = $person->whatsappPhone();
    $round = 'w-12 h-12 rounded-full bg-white flex items-center justify-center shadow-[0_8px_18px_-12px_rgba(23,25,31,.5)] hover:-translate-y-0.5 transition-transform';
@endphp

@if ($tel)
    <span class="relative inline-flex items-center gap-3"
          x-data="{
              open: false,
              copied: false,
              call(event) {
                  if (! window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
                  event.preventDefault();
                  this.open = ! this.open;
              },
              async copy() {
                  try {
                      await navigator.clipboard.writeText(@js($person->phone));
                      this.copied = true;
                      setTimeout(() => this.copied = false, 2000);
                  } catch (e) {
                      window.getSelection().selectAllChildren(this.$refs.number);
                  }
              },
          }"
          @keydown.escape.window="open = false" @click.outside="open = false">
        @if ($variant === 'round')
            <a href="tel:{{ $tel }}" @click="call($event)" class="{{ $round }}" title="Appeler {{ $person->name }}" aria-label="Appeler {{ $person->name }}" data-call>
                <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>
            </a>
            <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="{{ $round }} text-green" title="WhatsApp (appel par Internet)" aria-label="Contacter {{ $person->name }} sur WhatsApp">
                <x-nav-icon name="whatsapp" class="w-5 h-5" />
            </a>
            {{-- SMS : seulement sur téléphone, un ordinateur ne sait pas en envoyer. --}}
            <a href="sms:{{ $tel }}" class="{{ $round }} [@media(hover:hover)_and_(pointer:fine)]:hidden" title="Envoyer un SMS" aria-label="Envoyer un SMS à {{ $person->name }}">
                <x-nav-icon name="comment" class="w-5 h-5" />
            </a>
        @else
            <a href="tel:{{ $tel }}" @click="call($event)" class="btn btn-sm btn-secondary" aria-label="Appeler {{ $person->name }}" data-call>Appeler</a>
        @endif

        {{-- Fiche du contact (ordinateur). --}}
        <span x-show="open" x-cloak x-transition.opacity
              class="absolute z-30 top-full mt-2 {{ $variant === 'round' ? 'left-1/2 -translate-x-1/2' : 'right-0' }} w-64 rounded-2xl bg-white border border-line shadow-[0_18px_40px_-18px_rgba(23,25,31,.45)] p-4 text-left"
              role="dialog" aria-label="Contact de {{ $person->name }}">
            <span class="block text-[12px] font-semibold uppercase tracking-wide text-ink-grey">Appeler depuis votre téléphone</span>
            <span class="block mt-1 text-[14px] font-bold text-ink-body truncate">{{ $person->name }}</span>
            <span x-ref="number" class="block mt-2 text-[22px] font-extrabold tabular tracking-tight select-all">{{ $person->phone }}</span>
            <span class="mt-3 flex gap-2">
                <button type="button" @click="copy()" class="btn btn-sm btn-secondary flex-1">
                    <span x-show="! copied">Copier</span>
                    <span x-show="copied" x-cloak>Copié</span>
                </button>
                <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="btn btn-sm btn-secondary flex-1">WhatsApp</a>
            </span>
        </span>
    </span>
@endif
