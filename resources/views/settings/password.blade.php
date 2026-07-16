<x-layouts.app title="Change Password">
    <x-page-header title="Change Password" icon="shield" subtitle="Update your account password.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" href="{{ route('settings.index') }}">Settings</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="POST" action="{{ route('settings.password.update') }}" class="space-y-5 max-w-xl">
            @csrf
            @method('PUT')
            <x-field label="Current Password" for="current_password" required :error="$errors->first('current_password')">
                <x-input id="current_password" name="current_password" type="password" autocomplete="current-password" required />
            </x-field>
            <x-field label="New Password" for="password" required hint="At least 8 characters." :error="$errors->first('password')">
                <x-input id="password" name="password" type="password" autocomplete="new-password" required />
            </x-field>
            <x-field label="Confirm New Password" for="password_confirmation" required>
                <x-input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required />
            </x-field>
            <div class="flex items-center justify-end gap-2 pt-1">
                <x-button variant="secondary" href="{{ route('settings.index') }}">Cancel</x-button>
                <x-button type="submit" icon="check">Update Password</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
