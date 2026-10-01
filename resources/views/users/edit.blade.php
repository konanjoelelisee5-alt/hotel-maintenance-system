<x-form-page :title="'Modifier '.$user->name" :card="false" crumb="Administration · Utilisateurs" icon="user"
             :subtitle="$user->role_label.' · '.$user->email">
    @php($inModal = request()->hasHeader(\App\Http\Middleware\HandleModalRequests::HEADER))

    @if ($user->id !== auth()->id() && $inModal)
        @include('users.partials.password-reset', ['compact' => true])
    @endif

    <div class="{{ $inModal ? '' : 'bg-white p-6 shadow-sm rounded-lg' }}">
        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="name" value="Nom complet" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" value="E-mail" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div x-data="{ role: @js(old('role', $user->role->value)), alerts: @js(old('_token') ? (bool) old('receives_maintenance_alerts') : $user->receives_maintenance_alerts) }" class="space-y-4">
            <div>
                <x-input-label for="role" value="Rôle" />
                <select id="role" name="role" x-model="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required
                    @if ($user->id === auth()->id()) disabled @endif>
                    <option value="admin" @selected(old('role', $user->role->value) === 'admin')>Administrateur</option>
                    <option value="manager" @selected(old('role', $user->role->value) === 'manager')>Manager</option>
                    <option value="technicien" @selected(old('role', $user->role->value) === 'technicien')>Technicien</option>
                    <option value="housekeeping" @selected(old('role', $user->role->value) === 'housekeeping')>Housekeeping</option>
                    <option value="reception" @selected(old('role', $user->role->value) === 'reception')>Réception</option>
                </select>
                @if ($user->id === auth()->id())
                    <input type="hidden" name="role" value="{{ $user->role->value }}">
                    <p class="text-xs text-gray-500 mt-1">Vous ne pouvez pas modifier votre propre rôle.</p>
                @endif
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>

            @include('users.partials.department-head-field', ['checked' => old('is_department_head', $user->is_department_head)])
            @include('users.partials.alert-fields', ['phone' => old('phone', $user->phone)])
            </div>

            <div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked($user->is_active)
                        @if ($user->id === auth()->id()) disabled @endif
                        class="rounded border-gray-300">
                    Compte actif
                </label>
                @if ($user->id === auth()->id())
                    <input type="hidden" name="is_active" value="1">
                @endif
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('users.index') }}" data-modal-close class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Enregistrer</button>
            </div>
        </form>
    </div>

    @if ($user->id !== auth()->id() && ! $inModal)
        @include('users.partials.password-reset', ['compact' => false])
    @endif
</x-form-page>
