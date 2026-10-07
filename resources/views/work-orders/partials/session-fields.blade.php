{{-- Début / fin d'une période de travail (saisie après coup ou correction). --}}
<label class="flex flex-col gap-1 text-[12px] font-semibold text-ink-body">Début
    <input type="datetime-local" name="started_at" value="{{ $start }}" max="{{ now()->format('Y-m-d\TH:i') }}" required>
</label>
<label class="flex flex-col gap-1 text-[12px] font-semibold text-ink-body">Fin
    <input type="datetime-local" name="ended_at" value="{{ $end }}" max="{{ now()->format('Y-m-d\TH:i') }}" required>
</label>
