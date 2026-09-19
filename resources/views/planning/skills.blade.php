<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Compétences de') }} {{ $technician->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm rounded-lg">

                @if (session('success'))
                    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
                @endif

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