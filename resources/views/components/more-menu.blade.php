@props(['label' => "Plus d'actions", 'title' => null])

{{-- Menu « Plus » (⋮) : actions rares d'un écran, à côté de l'action principale.
     Les actions dangereuses (item « danger ») se placent en dernier, après le
     séparateur (more-menu.separator). Échap ou un clic ailleurs le referme ; les flèches
     parcourent les entrées. Sur téléphone il s'ouvre en feuille depuis le bas, sur tablette et ordinateur en liste déroulante. --}}
<div x-data="{
         open: false,
         move(step) {
             const items = [...this.$refs.menu.querySelectorAll('[role=menuitem]')];
             const i = items.indexOf(document.activeElement);
             items[(i + step + items.length) % items.length]?.focus();
         },
     }" class="relative flex-shrink-0"
     @keydown.escape.window="if (open) { open = false; $refs.trigger.focus() }"
     @click.outside="open = false">
    <button type="button" x-ref="trigger" @click="open = ! open" :aria-expanded="open.toString()" aria-haspopup="menu"
            {{ $attributes->merge(['class' => 'btn btn-secondary w-10 px-0']) }} title="{{ $label }}">
        <x-nav-icon name="more" />
        <span class="sr-only">{{ $label }}</span>
    </button>

    <div x-show="open" x-cloak class="tab:hidden fixed inset-0 z-[55] bg-navy-dark/40" @click="open = false" aria-hidden="true"></div>
    <div x-show="open" x-cloak x-ref="menu" role="menu" aria-label="{{ $label }}"
         x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
         @click="if ($event.target.closest('a')) open = false"
         x-init="$watch('open', v => v && $nextTick(() => $el.querySelector('[role=menuitem]')?.focus()))"
         class="fixed inset-x-0 bottom-0 z-[56] rounded-t-2xl pb-[max(12px,env(safe-area-inset-bottom))]
                tab:absolute tab:inset-x-auto tab:bottom-auto tab:right-0 tab:top-full tab:mt-1.5 tab:w-[250px] tab:rounded-xl tab:pb-1.5
                bg-white border border-line shadow-[0_18px_40px_-14px_rgba(11,27,44,.45)] pt-1.5">
        @if ($title)
            <div class="px-4 pt-2 pb-2.5 text-[11.5px] font-semibold uppercase tracking-wide text-ink-grey">{{ $title }}</div>
        @endif
        {{ $slot }}
    </div>
</div>
