<x-layouts.app title="API Tokens">
    <x-page-header title="API Tokens" icon="key" subtitle="Full-access tokens for the Manager API. Treat them like passwords.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" href="{{ route('settings.index') }}">Settings</x-button>
        </x-slot:actions>
    </x-page-header>

    @if (session('token_plain'))
        <div class="mb-6">
            <x-alert type="success" title="New Token — Copy It Now">
                <p>This is shown only once.</p>
                <pre class="mt-2 rounded-lg bg-chrome text-slate-100 text-xs p-3 overflow-x-auto"><code>{{ session('token_plain') }}</code></pre>
                <p class="mt-2 text-xs">Use it as <span class="font-mono">Authorization: Bearer &lt;token&gt;</span> against <span class="font-mono">{{ config('app.url') }}/api/v1</span>.</p>
            </x-alert>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card title="Your Tokens" :flush="$tokens->isNotEmpty()">
                @if ($tokens->isEmpty())
                    <x-empty-state icon="key" title="No Tokens Yet" description="Create a token to use the Manager API." />
                @else
                    <x-table flush>
                        <thead><tr><th>Name</th><th>Last Used</th><th>Created</th><th class="text-right">Actions</th></tr></thead>
                        <tbody>
                            @foreach ($tokens as $t)
                                <tr>
                                    <td class="font-medium text-slate-900">{{ $t->name }}</td>
                                    <td class="text-slate-500">{{ $t->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                                    <td class="text-slate-500">{{ $t->created_at?->diffForHumans() }}</td>
                                    <td class="text-right">
                                        <x-delete-button :name="'del-tok-' . $t->id" :action="route('settings.tokens.destroy', $t)"
                                            title="Revoke Token?" message="Any integration using this token will stop working immediately." confirm="Revoke" label="Revoke" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-card>
        </div>
        <div>
            <x-card title="Create Token">
                <form method="POST" action="{{ route('settings.tokens.store') }}" class="space-y-4">
                    @csrf
                    <x-field label="Name" for="name" required hint="What is it for?" :error="$errors->first('name')">
                        <x-input id="name" name="name" :value="old('name')" placeholder="e.g. CI Deploy" />
                    </x-field>
                    <x-button type="submit" icon="plus" class="w-full">Create Token</x-button>
                </form>
            </x-card>
        </div>
    </div>
</x-layouts.app>
