{{-- Compatibilité Breeze : même rendu que <x-button variant="danger-solid">. --}}
<button {{ $attributes->merge(['type' => 'submit'])->class('btn btn-danger-solid') }}>
    {{ $slot }}
</button>
