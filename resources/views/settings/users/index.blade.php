<x-layouts.app title="Users">
    <x-page-header title="Users & Admins" icon="users" subtitle="Who can sign in and what they can manage.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" href="{{ route('settings.index') }}">Settings</x-button>
            <x-button icon="plus" href="{{ route('settings.users.create') }}">New User</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card flush>
        <x-table flush>
            <thead>
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Directors</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @foreach ($users as $u)
                    <tr>
                        <td class="font-medium text-slate-900">{{ $u->name }} @if ($u->id === auth()->id())<x-badge color="info" class="ml-1">You</x-badge>@endif</td>
                        <td class="text-slate-500">{{ $u->email }}</td>
                        <td><x-badge :color="$u->isAdmin() ? 'success' : 'neutral'">{{ ucfirst($u->role) }}</x-badge></td>
                        <td class="tabular">{{ $u->directors_count }}</td>
                        <td class="text-right">
                            <div class="inline-flex items-center gap-2">
                                <x-icon-button :href="route('settings.users.edit', $u)" icon="edit" title="Edit" />
                                @if ($u->id !== auth()->id())
                                    <x-delete-button :name="'del-user-' . $u->id" :action="route('settings.users.destroy', $u)"
                                        title="Delete User?" message="Their directors and hosts are kept but become unassigned." />
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    </x-card>
</x-layouts.app>
