<x-app-layout crumb="Opérations / Planning" page-title="Planning de l'équipe">
    @include('planning.partials.agenda', ['technicians' => $technicians])
</x-app-layout>
