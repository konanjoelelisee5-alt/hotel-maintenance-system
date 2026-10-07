@props(['steps'])

{{-- Progression d'un parcours en étapes (état Alpine : step, à partir de 1). Même
     présentation partout : barre « Étape 2/4 » sur téléphone, étapes numérotées à
     partir de la tablette (cochées une fois passées). Parcours : signalement HK et
     comptoir, bon de commande. --}}
@php $count = count($steps); @endphp

<div class="tab:hidden flex flex-col gap-2">
    <div class="flex items-center justify-between text-[12.5px]">
        <span class="font-semibold text-navy" x-text="@js(array_values($steps))[step - 1]">{{ $steps[0] }}</span>
        <span class="font-mono text-ink-grey">Étape <span x-text="step">1</span>/{{ $count }}</span>
    </div>
    <div class="h-1 rounded-full bg-line overflow-hidden"><div class="h-full bg-navy transition-all" :style="'width:' + step * {{ 100 / $count }} + '%'"></div></div>
</div>
<ol class="hidden tab:flex m-0 p-0 list-none items-center gap-2 bg-white border border-line rounded-xl px-5 py-3.5" aria-label="Étapes">
    @foreach (array_values($steps) as $i => $name)
        <li class="flex items-center gap-2.5 {{ $loop->last ? '' : 'flex-1' }}">
            <span class="w-7 h-7 rounded-full flex items-center justify-center text-[12px] font-semibold font-mono flex-shrink-0 transition"
                  :class="step > {{ $i + 1 }} ? 'bg-navy text-white' : (step === {{ $i + 1 }} ? 'bg-white text-navy ring-2 ring-gold' : 'bg-line-soft text-ink-grey')">
                <span x-show="step <= {{ $i + 1 }}">{{ $i + 1 }}</span>
                <span x-show="step > {{ $i + 1 }}" x-cloak><x-hk.icon name="check" :size="14" /></span>
            </span>
            <span class="text-[13px] whitespace-nowrap" :class="step === {{ $i + 1 }} ? 'font-semibold text-navy' : 'font-medium text-ink-grey'">{{ $name }}</span>
            @unless ($loop->last)<span class="flex-1 h-px bg-line ml-1"></span>@endunless
        </li>
    @endforeach
</ol>
