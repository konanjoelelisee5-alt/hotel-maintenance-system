<x-app-layout :crumb="'Référentiels / Lieux'" :page-title="'Modifier '.$room->label" :back-route="route('rooms.show', $room)">
    <div>
        <div class="w-full max-w-2xl">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('rooms.update', $room) }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    @include('rooms._form')

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('rooms.show', $room) }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
