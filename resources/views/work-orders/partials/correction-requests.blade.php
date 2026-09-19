<div class="bg-white rounded-xl border border-red-200">
    <div class="px-5 py-4 border-b border-red-100">
        <h3 class="font-semibold text-navy-900 text-sm">Demandes de correction</h3>
    </div>

    <div class="p-5">
        @foreach ($workOrder->correctionRequests as $correction)
            <div class="{{ ! $loop->first ? 'border-t border-line-soft pt-3 mt-3' : '' }} text-sm">
                <div class="flex justify-between items-start gap-3">
                    <p class="text-slate-700">{{ $correction->description }}</p>
                    @if ($correction->status === 'ouverte')
                        @if (in_array(auth()->user()->role, [\App\Enums\UserRole::Admin, \App\Enums\UserRole::Manager], true))
                            <form method="POST" action="{{ route('correction-requests.resolve', $correction) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-xs text-navy-700 hover:underline whitespace-nowrap">
                                    Marquer traitée
                                </button>
                            </form>
                        @else
                            <span class="text-xs text-gold-700 whitespace-nowrap font-medium">À traiter</span>
                        @endif
                    @else
                        <span class="text-xs text-emerald-600 whitespace-nowrap">✓ Traitée</span>
                    @endif
                </div>
                <p class="text-xs text-ink-grey mt-1">
                    Demandée par {{ $correction->requester?->name ?? '—' }} le {{ $correction->created_at->format('d/m/Y H:i') }}
                </p>
            </div>
        @endforeach
    </div>
</div>
