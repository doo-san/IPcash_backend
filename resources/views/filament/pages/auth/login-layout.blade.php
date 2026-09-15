@php
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div class="ip-login">
        <div class="ip-login-card">
            <img src="{{ asset('images/ipcash-icon.svg') }}" alt="IPCash" class="ip-login-mark">
            {{ $slot }}
        </div>
    </div>
</x-filament-panels::layout.base>
