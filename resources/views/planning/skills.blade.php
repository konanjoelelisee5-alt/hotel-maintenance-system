<x-app-layout :crumb="'Exploitation / Planning'" :page-title="'Compétences de '.$technician->name" :back-route="route('planning.technician', $technician)">
    <div>
        <div class="w-full max-w-2xl">
            <div class="bg-white p-6 shadow-sm rounded-lg">


                <form method="POST" action="{{ route('planning.skills.update', $technician) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach ($skills as $skill)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="skills[]" value="{{ $skill->id }}"
                                    @checked($technician->skills->contains($skill->id))
                                    class="rounded border-gray-300">
                                {{ $skill->name }}
                            </label>
                        @endforeach
                    </div>

                    <x-input-error :messages="$errors->get('skills')" class="mt-2" />

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('planning.technician', $technician) }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">
                            Annuler
                        </a>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                            Enregistrer
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>