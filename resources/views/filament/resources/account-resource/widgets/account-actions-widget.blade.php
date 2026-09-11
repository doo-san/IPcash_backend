<x-filament-widgets::widget>
    <x-filament::section heading="Sécurité du compte">
        <div class="flex flex-wrap gap-3">
            @if ($this->unlockAction->isVisible())
                {{ $this->unlockAction }}
            @endif

            @if ($this->resetPinAction->isVisible())
                {{ $this->resetPinAction }}
            @endif

            @if ($this->blockTemporarilyAction->isVisible())
                {{ $this->blockTemporarilyAction }}
            @endif

            @if ($this->blockPermanentlyAction->isVisible())
                {{ $this->blockPermanentlyAction }}
            @endif

            @if ($this->unblockAction->isVisible())
                {{ $this->unblockAction }}
            @endif

            {{-- À côté des boutons de blocage, comme demandé — suppression
                 volontairement dans le même groupe plutôt qu'isolée. --}}
            {{ $this->deleteAction }}
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
