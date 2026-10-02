@php $report = $workOrder->interventionReport; @endphp

<x-panel id="report" title="Rapport d'intervention" icon="report">
    <x-slot:badge>
        @if ($report?->is_signed)
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#E6F3EC] text-green text-[11.5px] font-semibold"><x-nav-icon name="check" class="w-3 h-3" /> Signé</span>
        @elseif ($report)
            <span class="px-2 py-0.5 rounded-full bg-[#FBF1DF] text-[#7A5A16] text-[11.5px] font-semibold">Brouillon</span>
        @endif
    </x-slot:badge>

    @if ($report?->is_signed)
        <div class="flex gap-2.5 mb-4 px-3.5 py-3 rounded-[10px] bg-[#E6F3EC] text-[13px] text-success">
            <x-nav-icon name="check" class="w-4 h-4 flex-shrink-0 mt-0.5 text-green" />
            <span>Rapport signé par <strong>{{ $report->signed_by_name }}</strong> le {{ $report->signed_at->format('d/m/Y à H\hi') }}
                — intervention réalisée par {{ $report->technician?->name ?? '—' }}. Document verrouillé.</span>
        </div>
    @endif

    {{-- Lecture seule : rapport signé (verrouillé), ou superviseur qui le consulte
         (ce bloc ne lui est montré que si un rapport existe, cf. show.blade.php). --}}
    @if ($report && ($report->is_signed || auth()->user()->cannot('perform', $workOrder)))
        <dl class="m-0 flex flex-col gap-3.5 text-[13.5px]">
            @foreach (['Travail effectué' => $report->work_performed, 'Pièces / matériel utilisés' => $report->parts_used, 'Recommandations' => $report->recommendations] as $label => $text)
                @if (filled($text))
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-ink-grey">{{ $label }}</dt>
                        <dd class="m-0 mt-1 text-[#3d3a33] leading-relaxed whitespace-pre-line">{{ $text }}</dd>
                    </div>
                @endif
            @endforeach
            @unless ($report->is_signed)
                <p class="m-0 text-[12px] text-ink-grey">Brouillon de {{ $report->technician?->name ?? "l'intervenant" }}, pas encore signé.</p>
            @endunless
        </dl>
    @else
        <form method="POST" action="{{ route('work-orders.report.store', $workOrder) }}" class="flex flex-col gap-4">
            @csrf

            <div>
                <label for="work_performed">Travail effectué</label>
                <textarea id="work_performed" name="work_performed" rows="4" required placeholder="Ce qui a été constaté et réparé…">{{ old('work_performed', $report?->work_performed) }}</textarea>
                <x-input-error :messages="$errors->get('work_performed')" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="parts_used">Pièces / matériel utilisés</label>
                    <textarea id="parts_used" name="parts_used" rows="2">{{ old('parts_used', $report?->parts_used) }}</textarea>
                    <x-input-error :messages="$errors->get('parts_used')" />
                </div>
                <div>
                    <label for="recommendations">Recommandations</label>
                    <textarea id="recommendations" name="recommendations" rows="2">{{ old('recommendations', $report?->recommendations) }}</textarea>
                    <x-input-error :messages="$errors->get('recommendations')" />
                </div>
            </div>

            <div>
                <label for="signed_by_name">Nom du signataire (client / responsable)</label>
                <input id="signed_by_name" name="signed_by_name" type="text" value="{{ old('signed_by_name', $report?->signed_by_name) }}">
                <x-input-error :messages="$errors->get('signed_by_name')" />
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[12px] font-semibold uppercase tracking-wide text-[#6C6658]">Signature électronique</span>
                    <button type="button" id="clear-signature" class="btn btn-sm btn-ghost">Effacer</button>
                </div>
                <div class="rounded-[10px] border-2 border-dashed border-line bg-paper">
                    <canvas id="signature-pad" class="w-full touch-none" height="150"></canvas>
                </div>
                <p class="m-0 mt-1.5 text-[12px] text-ink-grey">Faites signer au doigt ou à la souris. Sans signature, le rapport reste un brouillon.</p>
                <input type="hidden" name="signature" id="signature-input">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary"><x-nav-icon name="report" /> Enregistrer le rapport</button>
            </div>
        </form>
    @endif
</x-panel>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('signature-pad');
            if (! canvas) return;

            const ctx = canvas.getContext('2d');
            canvas.width = canvas.offsetWidth;

            let drawing = false;
            // Un canvas vide produit quand même une image : sans ce drapeau, un simple
            // brouillon était envoyé comme "signé" et l'OT passait en résolu.
            let hasSignature = false;

            // Onglet « Intervention » masqué au chargement : la largeur du canvas valait 0.
            // On la reprend quand l'onglet s'affiche (événement émis par show.blade.php).
            window.addEventListener('work-order-tab', function () {
                if (! hasSignature && canvas.offsetWidth && canvas.width !== canvas.offsetWidth) {
                    canvas.width = canvas.offsetWidth;
                }
            });

            function getPosition(event) {
                const rect = canvas.getBoundingClientRect();
                const clientX = event.touches ? event.touches[0].clientX : event.clientX;
                const clientY = event.touches ? event.touches[0].clientY : event.clientY;
                return { x: clientX - rect.left, y: clientY - rect.top };
            }

            function startDrawing(event) {
                drawing = true;
                const pos = getPosition(event);
                ctx.beginPath();
                ctx.moveTo(pos.x, pos.y);
                event.preventDefault();
            }

            function draw(event) {
                if (! drawing) return;
                const pos = getPosition(event);
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();
                hasSignature = true;
                event.preventDefault();
            }

            function stopDrawing() {
                drawing = false;
            }

            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseleave', stopDrawing);

            canvas.addEventListener('touchstart', startDrawing);
            canvas.addEventListener('touchmove', draw);
            canvas.addEventListener('touchend', stopDrawing);

            document.getElementById('clear-signature').addEventListener('click', function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hasSignature = false;
            });

            canvas.closest('form').addEventListener('submit', function () {
                document.getElementById('signature-input').value = hasSignature ? canvas.toDataURL('image/png') : '';
            });
        });
    </script>
@endpush
