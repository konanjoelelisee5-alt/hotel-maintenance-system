<div id="report" class="bg-white rounded-xl border border-line">
    <div class="px-5 py-4 border-b border-line-soft">
        <h3 class="font-semibold text-navy-900 text-sm">Rapport d'intervention</h3>
    </div>

    <div class="p-5">
        @if ($workOrder->interventionReport?->is_signed)
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-md text-sm text-emerald-800">
                ✓ Rapport signé par {{ $workOrder->interventionReport->signed_by_name }}
                le {{ $workOrder->interventionReport->signed_at->format('d/m/Y à H:i') }}
            </div>
        @endif

        <form method="POST" action="{{ route('work-orders.report.store', $workOrder) }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="work_performed" value="Travail effectué" />
                <textarea id="work_performed" name="work_performed" rows="4" required
                    class="mt-1 block w-full border-slate-300 rounded-md shadow-sm text-sm">{{ old('work_performed', $workOrder->interventionReport?->work_performed) }}</textarea>
                <x-input-error :messages="$errors->get('work_performed')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="parts_used" value="Pièces / matériel utilisés" />
                    <textarea id="parts_used" name="parts_used" rows="2"
                        class="mt-1 block w-full border-slate-300 rounded-md shadow-sm text-sm">{{ old('parts_used', $workOrder->interventionReport?->parts_used) }}</textarea>
                    <x-input-error :messages="$errors->get('parts_used')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="recommendations" value="Recommandations" />
                    <textarea id="recommendations" name="recommendations" rows="2"
                        class="mt-1 block w-full border-slate-300 rounded-md shadow-sm text-sm">{{ old('recommendations', $workOrder->interventionReport?->recommendations) }}</textarea>
                    <x-input-error :messages="$errors->get('recommendations')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="signed_by_name" value="Nom du signataire (client / responsable)" />
                <x-text-input id="signed_by_name" name="signed_by_name" type="text" class="mt-1 block w-full"
                    :value="old('signed_by_name', $workOrder->interventionReport?->signed_by_name)" />
                <x-input-error :messages="$errors->get('signed_by_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label value="Signature électronique" />
                <div class="mt-1 border border-slate-300 rounded-md bg-paper">
                    <canvas id="signature-pad" class="w-full touch-none" height="150"></canvas>
                </div>
                <button type="button" id="clear-signature" class="mt-2 text-xs text-ink-grey hover:underline">
                    Effacer la signature
                </button>
                <input type="hidden" name="signature" id="signature-input">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-navy-800 text-white text-sm font-medium rounded-md hover:bg-navy-900">
                    Enregistrer le rapport
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('signature-pad');
            if (! canvas) return;

            const ctx = canvas.getContext('2d');
            canvas.width = canvas.offsetWidth;

            let drawing = false;

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
            });

            canvas.closest('form').addEventListener('submit', function () {
                document.getElementById('signature-input').value = canvas.toDataURL('image/png');
            });
        });
    </script>
@endpush
