<x-layouts.app title="New User">
    <x-page-header title="New User" icon="users" subtitle="Create a login. Users manage their own Directors; admins manage everything." />
    <x-card>
        <form method="POST" action="{{ route('settings.users.store') }}" class="space-y-5">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="Name" for="name" required :error="$errors->first('name')">
                    <x-input id="name" name="name" :value="old('name')" required autofocus />
                </x-field>
                <x-field label="Email" for="email" required :error="$errors->first('email')">
                    <x-input id="email" name="email" type="email" :value="old('email')" required autocomplete="off" />
                </x-field>
                <x-field label="Role" for="role" required>
                    <x-select id="role" name="role">
                        <option value="user" @selected(old('role') === 'user')>User</option>
                        <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                    </x-select>
                </x-field>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="Password" for="password" required :error="$errors->first('password')">
                    <x-input id="password" name="password" type="password" autocomplete="new-password" required />
                </x-field>
                <x-field label="Confirm Password" for="password_confirmation" required>
                    <x-input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required />
                </x-field>
            </div>
            <div class="flex items-center justify-end gap-2 pt-1">
                <x-button variant="secondary" href="{{ route('settings.users.index') }}">Cancel</x-button>
                <x-button type="submit" icon="plus">Create User</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
