<x-app-layout :crumb="'Patrimoine / Lieux'" :page-title="'Modifier '.$room->label" :back-route="route('rooms.show', $room)">
    <div>
        <div class="w-full max-w-2xl">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('rooms.update', $room) }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    @include('rooms._form')

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('rooms.show', $room) }}" class="btn btn-ghost">Annuler</a>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
