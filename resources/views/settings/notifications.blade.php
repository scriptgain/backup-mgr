@php
    $enabled = \App\Models\Setting::get('notifications_enabled') === '1';
    $g = fn ($k) => \App\Models\Setting::get($k);
@endphp
<x-layouts.app title="Notifications">
    <x-page-header title="Notifications" icon="bell" subtitle="Get emailed when a backup fails.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" href="{{ route('settings.index') }}">Settings</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('settings.notifications.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card title="Alerts">
            <div class="space-y-5">
                <x-toggle name="notifications_enabled" :checked="$enabled" label="Email on Backup Failure" description="Send an email whenever a run fails." />
                <x-field label="Notify Email" for="notify_email" hint="Where alerts are sent." :error="$errors->first('notify_email')">
                    <x-input id="notify_email" name="notify_email" type="email" :value="$g('notify_email')" placeholder="you@example.com" />
                </x-field>
            </div>
        </x-card>

        <x-card title="SMTP" subtitle="Outgoing mail server (e.g. SendGrid).">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-field label="Host" for="smtp_host" :error="$errors->first('smtp_host')">
                    <x-input id="smtp_host" name="smtp_host" :value="$g('smtp_host')" placeholder="smtp.sendgrid.net" />
                </x-field>
                <x-field label="Port" for="smtp_port" :error="$errors->first('smtp_port')">
                    <x-input id="smtp_port" name="smtp_port" type="number" :value="$g('smtp_port') ?: 587" />
                </x-field>
                <x-field label="Username" for="smtp_username" :error="$errors->first('smtp_username')">
                    <x-input id="smtp_username" name="smtp_username" :value="$g('smtp_username')" autocomplete="off" placeholder="apikey" />
                </x-field>
                <x-field label="Password / API Key" for="smtp_password" hint="Leave blank to keep the stored value.">
                    <x-input id="smtp_password" name="smtp_password" type="password" autocomplete="new-password" data-lpignore="true" />
                </x-field>
                <x-field label="From Address" for="mail_from" :error="$errors->first('mail_from')">
                    <x-input id="mail_from" name="mail_from" type="email" :value="$g('mail_from')" placeholder="backups@yourdomain.com" />
                </x-field>
            </div>
        </x-card>

        <div class="flex items-center justify-end gap-2">
            <x-button variant="secondary" href="{{ route('settings.index') }}">Cancel</x-button>
            <x-button type="submit" icon="check">Save</x-button>
        </div>
    </form>

    <form method="POST" action="{{ route('settings.notifications.test') }}" class="mt-4">@csrf
        <x-button type="submit" variant="secondary" icon="bell">Send Test Email</x-button>
    </form>
</x-layouts.app>
