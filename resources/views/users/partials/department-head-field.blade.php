{{-- Case "Responsable de service", affichée seulement pour les rôles qui ont un responsable.
     Attend d'être placée dans un conteneur Alpine exposant `role` (lié au select du rôle). --}}
<div x-show="['housekeeping', 'reception'].includes(role)">
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_department_head" value="1" @checked($checked) class="rounded border-line">
        Responsable de service
    </label>
    <p class="text-xs text-ink-muted mt-1">
        Voit les ordres de travail signalés par toute son équipe (lecture seule), sans accès au paramétrage.
    </p>
</div>
