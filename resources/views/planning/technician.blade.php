@php $isMe = auth()->id() === $technician->id; @endphp

<x-app-layout :crumb="$isMe ? 'Mon espace / Planning' : 'Planning / Technicien'"
              :page-title="$isMe ? 'Mon planning' : 'Planning de '.$technician->name">
    @if (in_array(auth()->user()->role, [\App\Enums\UserRole::Admin, \App\Enums\UserRole::Manager], true))
        <x-slot:primaryAction>
            <a href="{{ route('planning.skills.edit', $technician) }}" class="flex-shrink-0 inline-flex items-center h-[38px] px-4 rounded-[9px] border border-line bg-white text-[13px] font-semibold text-navy whitespace-nowrap hover:bg-paper">Compétences</a>
            <a href="{{ route('planning.availabilities.index', $technician) }}" class="flex-shrink-0 inline-flex items-center h-[38px] px-4 rounded-[9px] bg-navy text-white text-[13px] font-semibold whitespace-nowrap hover:bg-navy-light">Disponibilités</a>
        </x-slot:primaryAction>
    @endif

    @include('planning.partials.agenda', ['technicianId' => $technician->id])
</x-app-layout>
