<x-layouts.app :title="$title">
    <x-page-header :title="$title" :icon="$icon" subtitle="Coming together in Phase 2.">
        <x-slot:actions>
            <x-button variant="secondary" size="sm" icon="book" href="#">Docs</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <x-empty-state :icon="$icon" :title="$title . ' Land Here'" :description="$blurb">
            <x-slot:action>
                <x-button icon="plus">Get Started</x-button>
            </x-slot:action>
        </x-empty-state>
    </x-card>
</x-layouts.app>
