<x-panel title="Demandes de correction" icon="alert" tone="danger" flush>
    <ul class="m-0 p-0 list-none divide-y divide-line-soft">
        @foreach ($workOrder->correctionRequests as $correction)
            <li class="flex items-start justify-between gap-3 px-5 py-3.5 text-[13px]">
                <div class="min-w-0">
                    <p class="m-0 text-[#3d3a33] leading-relaxed">{{ $correction->description }}</p>
                    <p class="m-0 mt-1 text-[12px] text-ink-grey">
                        Demandée par {{ $correction->requester?->name ?? '—' }} le {{ $correction->created_at->format('d/m/Y à H\hi') }}
                    </p>
                </div>
                @if ($correction->status === 'ouverte')
                    @if (in_array(auth()->user()->role, [\App\Enums\UserRole::Admin, \App\Enums\UserRole::Manager], true))
                        <form method="POST" action="{{ route('correction-requests.resolve', $correction) }}" class="flex-shrink-0">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-secondary"><x-nav-icon name="check" /> Marquer traitée</button>
                        </form>
                    @else
                        <span class="flex-shrink-0 px-2 py-0.5 rounded-full bg-[#FBF1DF] text-[#7A5A16] text-[11.5px] font-semibold">À traiter</span>
                    @endif
                @else
                    <span class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#E6F3EC] text-green text-[11.5px] font-semibold">
                        <x-nav-icon name="check" class="w-3 h-3" /> Traitée
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
</x-panel>
