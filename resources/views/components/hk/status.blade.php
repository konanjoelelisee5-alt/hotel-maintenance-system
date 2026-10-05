{{-- Statut regroupé des écrans HK (App\Support\Housekeeping) : En attente, En cours, Réparé.
     Même gabarit que les badges de l'admin (x-work-order-status-badge). --}}
@props(['status', 'urgent' => false])

@php $s = \App\Support\Housekeeping::status($status); @endphp

<span {{ $attributes->class('inline-flex items-center gap-1.5 flex-wrap') }}>
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap {{ $s['text'] }} {{ $s['bg'] }}">
        <span class="w-1.5 h-1.5 rounded-full {{ $s['dot'] }}"></span>{{ $s['label'] }}
    </span>
    @if ($urgent)
        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap bg-hk-pending-bg text-hk-pending ring-1 ring-inset ring-hk-pending/25">
            <x-hk.icon name="alert-triangle" :size="13" /> Urgent
        </span>
    @endif
</span>
