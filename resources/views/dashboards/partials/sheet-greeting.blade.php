{{-- « Bonjour, prénom » de la maquette, puis la date et une phrase sur ce qui attend ($summary). --}}
<h1 class="m-0 text-[28px] tab:text-[32px] font-bold tracking-[-0.025em] leading-tight">Bonjour, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}</h1>
<p class="m-0 mt-2 text-[15px] text-ink-muted">
    <span class="font-semibold text-ink-body first-letter:uppercase inline-block">{{ now()->locale('fr')->isoFormat('dddd D MMMM') }}</span> · {{ $summary }}
</p>
