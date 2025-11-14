<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Franchisee Logos
        </x-slot>
        <x-slot name="description">
            Upload up to 10 logo files for this franchisee
        </x-slot>
        
        <form wire:submit="save">
            {{ $this->form }}
            
            <div class="mt-4">
                <x-filament::button type="submit">
                    Save Logos
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>

