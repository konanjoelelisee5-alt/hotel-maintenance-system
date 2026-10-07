{{-- Téléphone + réception des alertes d'astreinte, pour les admins et managers.
     Attend d'être placée dans un conteneur Alpine exposant `role` et `alerts`. --}}
<div>
    <x-input-label for="phone" value="Téléphone" />
    <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="$phone" placeholder="+225 07 07 12 34 56" />
    <p class="text-xs text-ink-muted mt-1" x-show="['admin', 'manager'].includes(role) && alerts">
        Obligatoire : c'est le numéro qui reçoit les alertes d'astreinte (SMS, WhatsApp ou appel).
    </p>
    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
</div>

<div x-show="['admin', 'manager'].includes(role)">
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="receives_maintenance_alerts" value="1" x-model="alerts" class="rounded border-line">
        Reçoit les alertes de maintenance (astreinte, escalades)
    </label>
    <p class="text-xs text-ink-muted mt-1">
        À cocher pour le chef de maintenance et les managers ; à décocher pour un admin
        qui ne pilote pas la maintenance (responsable informatique, par exemple).
    </p>
</div>
