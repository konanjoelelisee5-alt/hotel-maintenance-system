{{-- Fiche OT en lecture seule (Housekeeping), avec les panneaux des fiches de l'admin.
     Actions du demandeur, toutes sans modifier l'OT : retirer un signalement fait par
     erreur (15 min, sans technicien), ajouter une précision (texte, vocal, photo),
     confirmer la réparation ; la gouvernante demande aussi le blocage de la chambre.
     Variables : $workOrder, $repeatCount (WorkOrderController::housekeepingScreen). --}}
@php
    $hk = \App\Support\Housekeeping::class;
    $user = auth()->user();
    $status = $hk::status($workOrder->status);
    $category = $hk::category($workOrder);
    $urgent = $workOrder->priority?->code === 'urgente';
    $fromHk = fn ($u) => $u?->role === \App\Enums\UserRole::Housekeeping;
    // Ce que le service a envoyé (signalement puis précisions) / ce que la maintenance a joint.
    $hkFiles = $workOrder->attachments->filter(fn ($a) => $fromHk($a->uploader));
    $interventionPhotos = $workOrder->attachments->reject(fn ($a) => $fromHk($a->uploader))->filter(fn ($a) => str_starts_with($a->mime_type, 'image/'));
    $originalCutoff = $workOrder->created_at->copy()->addMinutes(2);
    $originalFiles = $hkFiles->filter(fn ($a) => $a->created_at->lte($originalCutoff));
    // Précisions : commentaires du service + fichiers joints ensuite, regroupés par envoi.
    $complements = $workOrder->comments->filter(fn ($c) => $fromHk($c->user))
        ->map(fn ($c) => ['at' => $c->created_at, 'who' => $c->user, 'text' => \Illuminate\Support\Str::after($c->content, 'Précision du demandeur : '), 'files' => collect()])
        ->keyBy(fn ($c) => $c['who']->id.'|'.$c['at']->format('Y-m-d H:i'));
    foreach ($hkFiles->reject(fn ($a) => $a->created_at->lte($originalCutoff)) as $file) {
        $key = $file->uploaded_by.'|'.$file->created_at->format('Y-m-d H:i');
        $entry = $complements->get($key, ['at' => $file->created_at, 'who' => $file->uploader, 'text' => null, 'files' => collect()]);
        $entry['files']->push($file);
        $complements->put($key, $entry);
    }
    $complements = $complements->sortBy('at')->values();
    // Textes posés par le signalement rapide quand l'agent n'a rien écrit : pas une description.
    $note = in_array($workOrder->description, ['Signalement rapide sans description.', "Message vocal joint : écouter l'enregistrement dans les pièces jointes."], true)
        ? null : $workOrder->description;
    $steps = $hk::timeline($workOrder);
    $cancel = $workOrder->status === 'annule' ? $workOrder->statusHistories->sortByDesc('id')->first() : null;
    $withdrawn = $cancel && str_starts_with((string) $cancel->note, 'Retiré par le demandeur');
    $banner = match (true) {
        $withdrawn => 'Retiré par '.($cancel->changed_by === $user->id ? 'vous' : ($cancel->changedBy?->name ?? 'le demandeur')),
        $cancel !== null => 'Annulé par la maintenance',
        default => [
            'pending' => 'En attente d\'un technicien',
            'progress' => match ($workOrder->status) { 'en_attente' => 'En cours · en attente (pièce, accès…)', 'rejete' => 'Réparation reprise après contrôle', default => 'Réparation en cours' },
            'done' => 'Réparé',
        ][$status['key']] ?? $status['label'],
    };
    $accent = ['pending' => '#B3261E', 'progress' => '#9A5F0C', 'done' => '#1E7A55'][$status['key']] ?? '#8B909A';
    $report = $workOrder->interventionReport;
    $withdrawLeft = $user->can('withdraw', $workOrder)
        ? max(1, (int) ceil(now()->diffInMinutes($workOrder->created_at->copy()->addMinutes($hk::WITHDRAW_WINDOW_MINUTES), false)))
        : null;
@endphp

<article class="flex flex-col gap-5" aria-label="Fiche {{ $workOrder->code() }}">
    {{-- En-tête --}}
    <section class="bg-white border border-line rounded-xl overflow-hidden" style="box-shadow: inset 4px 0 0 {{ $accent }}">
        <div class="px-5 tab:px-6 py-5 flex flex-col gap-3">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-10 h-10 rounded-[10px] border border-line bg-paper text-gold flex items-center justify-center flex-shrink-0">
                        <x-hk.icon :name="$hk::categoryIcon($category)" :size="19" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="m-0 text-[18px] font-semibold text-navy leading-tight">{{ $workOrder->room?->label ?? 'Parties communes' }}</h2>
                        <p class="m-0 mt-0.5 text-[13px] text-[#6C6658]">
                            <span class="font-mono text-[#26496B]">{{ $workOrder->code() }}</span> · {{ $category ? $hk::categoryLabel($category) : $workOrder->title }}
                        </p>
                    </div>
                </div>
                <x-hk.status :status="$workOrder->status" :urgent="$urgent" :late="$workOrder->slaSummary()['late']" />
            </div>
            <div class="flex items-center justify-between gap-3 flex-wrap pt-3 border-t border-line-soft">
                <span class="text-[13.5px] font-semibold" style="color: {{ $accent }}">{{ $banner }}</span>
                <span class="inline-flex items-center gap-1.5 text-[12px] text-ink-grey"><x-hk.icon name="lock" :size="13" /> Lecture seule · mise à jour par la maintenance</span>
            </div>
            @if ($cancel?->note)
                <p class="m-0 px-3.5 py-2.5 rounded-[8px] bg-line-soft text-[13px] text-[#4A4639]">
                    {{ \Illuminate\Support\Str::after($cancel->note, ': ') }}
                    <span class="font-mono text-[11.5px] text-ink-grey">· {{ $cancel->created_at->format('d/m H:i') }}</span>
                </p>
            @endif
        </div>

        {{-- Actions du demandeur --}}
        @if ($withdrawLeft || $user->can('complement', $workOrder))
            {{-- x-data : $dispatch n'existe que dans un composant Alpine. --}}
            <div x-data class="px-5 tab:px-6 py-3 bg-paper/60 border-t border-line-soft flex flex-wrap items-center gap-2">
                @can('complement', $workOrder)
                    <button type="button" class="btn btn-secondary" @click="$dispatch('hk-complement-open')"><x-hk.icon name="plus" :size="16" /> Ajouter une précision</button>
                @endcan
                @if ($withdrawLeft)
                    <button type="button" class="btn btn-ghost !text-red" @click="$dispatch('hk-withdraw-open')"><x-hk.icon name="x" :size="16" /> Retirer ce signalement</button>
                    <span class="text-[12px] text-ink-grey">possible encore {{ $withdrawLeft }} min</span>
                @endif
            </div>
        @endif
    </section>

    {{-- Retirer : motif obligatoire, l'OT passe « annulé » sans être supprimé. --}}
    @if ($withdrawLeft)
        <section x-data="{ open: {{ $errors->has('reason') ? 'true' : 'false' }} }" @hk-withdraw-open.window="open = true" x-show="open" x-cloak
                 class="bg-white border border-red/30 rounded-xl overflow-hidden">
            <form method="POST" action="{{ route('quick-reports.withdraw', $workOrder) }}" class="ui-form flex flex-col gap-4 px-5 py-4">
                @csrf
                <div>
                    <h3 class="m-0 text-[14.5px] font-semibold text-navy">Retirer ce signalement ?</h3>
                    <p class="m-0 mt-0.5 text-[12.5px] text-[#6C6658]">La maintenance ne se déplacera pas. Le signalement reste visible dans votre historique, avec le motif.</p>
                </div>
                <fieldset class="m-0 p-0 border-0 grid grid-cols-1 min-[480px]:grid-cols-2 gap-2">
                    <legend class="sr-only">Motif</legend>
                    @foreach ($hk::WITHDRAW_REASONS as $key => $label)
                        <label class="!flex !normal-case !tracking-normal !mb-0 items-center gap-2.5 min-h-[44px] px-3 rounded-[10px] border border-line text-[13.5px] font-medium text-navy cursor-pointer has-[:checked]:border-navy has-[:checked]:bg-paper">
                            <input type="radio" name="reason" value="{{ $key }}" class="text-navy focus:ring-navy/30" @checked(old('reason') === $key)>
                            {{ $label }}
                        </label>
                    @endforeach
                </fieldset>
                <x-input-error :messages="$errors->get('reason')" />
                <input type="text" name="detail" maxlength="300" value="{{ old('detail') }}" placeholder="Précision (facultatif)" aria-label="Précision sur le motif"
                       class="w-full h-10 rounded-[10px] border-line text-[13.5px]">
                <div class="flex flex-wrap gap-2.5">
                    <button type="submit" class="btn btn-danger-solid">Retirer le signalement</button>
                    <button type="button" class="btn btn-ghost" @click="open = false">Garder</button>
                </div>
            </form>
        </section>
    @endif

    {{-- Ajouter une précision : texte, message vocal, photo (au moins l'un des trois). --}}
    @can('complement', $workOrder)
        @include('housekeeping.partials.complement-form')
    @endcan

    {{-- Panne qui revient dans la même chambre --}}
    @if ($repeatCount >= 2)
        <div class="flex items-start gap-3 px-4 py-3 rounded-xl border border-amber/30 bg-[#FBF1DF] text-[#7A5A16]">
            <x-hk.icon name="alert-triangle" :size="18" class="mt-0.5" />
            <p class="m-0 text-[13px] leading-relaxed">
                <strong>Panne récurrente :</strong> {{ $repeatCount }}ᵉ signalement « {{ $hk::categoryLabel($category) }} » dans cette chambre en {{ $hk::REPEAT_DAYS }} jours.
                @if ($user->isDepartmentHead()) Pensez à demander une remise en état complète. @else La gouvernante en est informée sur son tableau de bord. @endif
            </p>
        </div>
    @endif

    @include('work-orders.partials.requester-confirmation')

    {{-- Ce que le technicien a fait (rapport d'intervention), pour confirmer en connaissance de cause. --}}
    @if ($report)
        <x-panel title="Réparation effectuée" icon="wrench">
            <div class="flex flex-col gap-3 text-[13.5px]">
                <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-full bg-gold flex items-center justify-center text-[10.5px] font-bold text-navy flex-shrink-0">{{ $report->technician?->initialsOrGenerated() }}</span>
                    <span class="font-medium text-navy">{{ $report->technician?->name ?? 'Technicien' }}</span>
                    @if ($workOrder->completed_at)<span class="ml-auto font-mono text-[12px] text-ink-grey">{{ $workOrder->completed_at->format('d/m/Y H:i') }}</span>@endif
                </div>
                <p class="m-0 text-[#4A4639] leading-relaxed whitespace-pre-line">{{ $report->work_performed }}</p>
                @if ($report->recommendations)
                    <p class="m-0 px-3.5 py-2.5 rounded-[8px] bg-paper border-l-2 border-gold text-[13px] text-[#4A4639]"><span class="font-semibold text-navy">Conseil du technicien :</span> {{ $report->recommendations }}</p>
                @endif
                @if ($interventionPhotos->isNotEmpty())
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($interventionPhotos as $photo)
                            <a href="{{ $photo->url }}" target="_blank" class="block aspect-[4/3] rounded-[10px] overflow-hidden border border-line bg-paper">
                                <img src="{{ $photo->url }}" alt="Photo de l'intervention" class="w-full h-full object-cover">
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-panel>
    @endif

    <div class="grid gap-5 desk:grid-cols-2 items-start">
        <div class="flex flex-col gap-5 min-w-0">
            {{-- Signalement et précisions, dans l'ordre d'envoi --}}
            <x-panel title="Signalement et précisions" icon="mic">
                <ol class="m-0 p-0 list-none flex flex-col gap-4">
                    <li class="flex flex-col gap-2">
                        <div class="flex items-center justify-between gap-2 text-[12px]">
                            <span class="font-semibold text-navy">Signalement · {{ $workOrder->reporter?->name ?? '—' }}</span>
                            <span class="font-mono text-ink-grey">{{ $workOrder->created_at->format('d/m H:i') }}</span>
                        </div>
                        @if ($note)<p class="m-0 text-[13.5px] text-[#4A4639] leading-relaxed">{{ $note }}</p>@endif
                        @include('housekeeping.partials.media', ['files' => $originalFiles])
                        @if (! $note && $originalFiles->isEmpty())<p class="m-0 text-[13px] text-ink-grey">Ni message ni photo.</p>@endif
                    </li>
                    @foreach ($complements as $c)
                        <li class="flex flex-col gap-2 pt-4 border-t border-line-soft">
                            <div class="flex items-center justify-between gap-2 text-[12px]">
                                <span class="font-semibold text-navy">Précision · {{ $c['who']?->name ?? '—' }}</span>
                                <span class="font-mono text-ink-grey">{{ $c['at']->format('d/m H:i') }}</span>
                            </div>
                            @if ($c['text'] && ! str_ends_with($c['text'], 'ajouté(s).'))<p class="m-0 text-[13.5px] text-[#4A4639] leading-relaxed">{{ $c['text'] }}</p>@endif
                            @include('housekeeping.partials.media', ['files' => $c['files']])
                        </li>
                    @endforeach
                </ol>
            </x-panel>

            <x-panel title="Informations" icon="info">
                <dl class="m-0 text-[13.5px]">
                    @foreach (array_filter([
                        'Lieu' => $workOrder->room?->label ?? 'Parties communes',
                        'Problème' => $category ? $hk::categoryLabel($category) : $workOrder->title,
                        'Client' => $workOrder->room_occupancy?->label(),
                        'Signalé par' => $workOrder->reporter?->name,
                        'Le' => $workOrder->created_at->format('d/m/Y à H\hi'),
                        'Technicien' => $workOrder->assignee?->name ?? 'Pas encore affecté',
                        'À réparer avant' => $workOrder->due_date?->format('d/m à H\hi'),
                    ]) as $label => $value)
                        <div class="flex justify-between gap-4 py-2.5 border-b border-line-soft last:border-b-0">
                            <dt class="text-ink-grey">{{ $label }}</dt>
                            <dd class="m-0 text-navy font-medium text-right {{ in_array($label, ['Le', 'À réparer avant'], true) ? 'font-mono text-[12.5px]' : '' }}">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-panel>
        </div>

        <div class="flex flex-col gap-5 min-w-0">
            <x-panel title="Suivi" icon="clock">
                <ol class="m-0 p-0 list-none">
                    @foreach ($steps as $i => $s)
                        @php $current = $s['done'] && ! ($steps[$i + 1]['done'] ?? false) && $status['key'] !== 'done' && ! $cancel; @endphp
                        <li class="relative flex gap-3 pb-4 last:pb-0">
                            @unless ($loop->last)
                                <span class="absolute left-[11px] top-6 bottom-0 w-px {{ ($steps[$i + 1]['done'] ?? false) ? 'bg-green' : 'bg-line' }}" aria-hidden="true"></span>
                            @endunless
                            <span class="relative z-10 w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0
                                         {{ $s['done'] ? ($current ? 'bg-white border-2 border-gold text-gold' : 'bg-green text-white') : 'bg-white border border-line text-ink-grey' }}">
                                @if ($s['done'] && ! $current)<x-hk.icon name="check" :size="13" />@elseif ($current)<span class="w-2 h-2 rounded-full bg-gold"></span>@else<span class="font-mono text-[11px]">{{ $i + 1 }}</span>@endif
                            </span>
                            <div class="flex flex-col gap-0.5 min-w-0 -mt-px">
                                <span class="text-[13.5px] {{ $s['done'] ? 'font-semibold text-navy' : 'font-medium text-ink-grey' }}">{{ $s['label'] }}</span>
                                @if ($s['at'])<span class="font-mono text-[11.5px] text-ink-grey">{{ $s['at']->format('d/m/Y H:i') }}</span>@endif
                                @if ($s['meta'])<span class="text-[12.5px] text-[#6C6658]">{{ $s['meta'] }}</span>@endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </x-panel>

            @if ($workOrder->room && ! $workOrder->room->isCommonArea())
                @include('work-orders.partials.room-block-card')
            @endif
        </div>
    </div>
</article>
