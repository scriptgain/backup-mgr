<x-layouts.app title="Edit User">
    <x-page-header title="Edit User" icon="users" :subtitle="$user->email" />
    <x-card>
        <form method="POST" action="{{ route('settings.users.update', $user) }}" class="space-y-5">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="Name" for="name" required :error="$errors->first('name')">
                    <x-input id="name" name="name" :value="old('name', $user->name)" required />
                </x-field>
                <x-field label="Email" for="email" required :error="$errors->first('email')">
                    <x-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="off" />
                </x-field>
                <x-field label="Role" for="role" required>
                    <x-select id="role" name="role">
                        <option value="user" @selected(old('role', $user->role) === 'user')>User</option>
                        <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                    </x-select>
                </x-field>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="New Password" for="password" hint="Leave blank to keep the current password." :error="$errors->first('password')">
                    <x-input id="password" name="password" type="password" autocomplete="new-password" />
                </x-field>
                <x-field label="Confirm Password" for="password_confirmation">
                    <x-input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
                </x-field>
            </div>
            <div class="flex items-center justify-end gap-2 pt-1">
                <x-button variant="secondary" href="{{ route('settings.users.index') }}">Cancel</x-button>
                <x-button type="submit" icon="check">Save Changes</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
