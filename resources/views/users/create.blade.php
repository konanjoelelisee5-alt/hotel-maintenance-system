<x-form-page title="Nouvel utilisateur" crumb="Administration" icon="users"
             subtitle="Compte d'un employé : son rôle définit ce qu'il voit et peut faire.">
    <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="name" value="Nom complet" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div x-data="{ role: @js(old('role', '')), alerts: @js(old('_token') ? (bool) old('receives_maintenance_alerts') : true) }" class="space-y-4">
        <div>
            <x-input-label for="role" value="Rôle" />
            <select id="role" name="role" x-model="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                <option value="">-- Sélectionner --</option>
                <option value="admin" @selected(old('role') === 'admin')>Administrateur</option>
                <option value="manager" @selected(old('role') === 'manager')>Manager</option>
                <option value="technicien" @selected(old('role') === 'technicien')>Technicien</option>
                <option value="housekeeping" @selected(old('role') === 'housekeeping')>Housekeeping</option>
                <option value="reception" @selected(old('role') === 'reception')>Réception</option>
            </select>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </div>

        @include('users.partials.department-head-field', ['checked' => old('is_department_head')])
        @include('users.partials.alert-fields', ['phone' => old('phone')])
        </div>

        <div>
            <x-input-label for="password" value="Mot de passe provisoire" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
            <p class="text-xs text-gray-500 mt-1">L'employé devra le remplacer par un mot de passe personnel à sa première connexion.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirmer le mot de passe" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('users.index') }}" data-modal-close class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Créer</button>
        </div>
    </form>
</x-form-page>
