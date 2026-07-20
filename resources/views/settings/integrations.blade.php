@php
    $g = fn ($k) => \App\Models\Setting::get($k);
    $en = fn ($k) => \App\Models\Setting::get($k) === '1';
@endphp
<x-layouts.app title="Integrations">
    <x-page-header title="Integrations" icon="bolt" subtitle="Post alerts to chat channels and webhooks.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" href="{{ route('settings.index') }}">Settings</x-button>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-800 ring-1 ring-brand-200">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('settings.integrations.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card title="Slack" subtitle="Post to a Slack channel via an incoming webhook.">
            <div class="space-y-5">
                <x-toggle name="integrations_slack_enabled" :checked="$en('integrations_slack_enabled')" label="Enable Slack" description="Send alerts to a Slack incoming webhook URL." />
                <x-field label="Webhook URL" for="integrations_slack_url" hint="Slack → Apps → Incoming Webhooks." :error="$errors->first('integrations_slack_url')">
                    <x-input id="integrations_slack_url" name="integrations_slack_url" :value="$g('integrations_slack_url')" placeholder="https://hooks.slack.com/services/..." />
                </x-field>
                <div><x-button type="button" variant="secondary" size="sm" icon="bolt" x-on:click="window.sendIntegrationTest('slack')">Send Test</x-button></div>
            </div>
        </x-card>

        <x-card title="Discord" subtitle="Post to a Discord channel via a webhook.">
            <div class="space-y-5">
                <x-toggle name="integrations_discord_enabled" :checked="$en('integrations_discord_enabled')" label="Enable Discord" description="Send alerts to a Discord channel webhook URL." />
                <x-field label="Webhook URL" for="integrations_discord_url" hint="Channel Settings → Integrations → Webhooks." :error="$errors->first('integrations_discord_url')">
                    <x-input id="integrations_discord_url" name="integrations_discord_url" :value="$g('integrations_discord_url')" placeholder="https://discord.com/api/webhooks/..." />
                </x-field>
                <div><x-button type="button" variant="secondary" size="sm" icon="bolt" x-on:click="window.sendIntegrationTest('discord')">Send Test</x-button></div>
            </div>
        </x-card>

        <x-card title="Telegram" subtitle="Message a Telegram chat via a bot.">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <x-toggle name="integrations_telegram_enabled" :checked="$en('integrations_telegram_enabled')" label="Enable Telegram" description="Send alerts through a Telegram bot to a chat." />
                </div>
                <x-field label="Bot Token" for="integrations_telegram_token" hint="Leave blank to keep the stored value.">
                    <x-input id="integrations_telegram_token" name="integrations_telegram_token" type="password" autocomplete="new-password" data-lpignore="true" placeholder="123456:ABC-DEF..." />
                </x-field>
                <x-field label="Chat ID" for="integrations_telegram_chat_id" :error="$errors->first('integrations_telegram_chat_id')">
                    <x-input id="integrations_telegram_chat_id" name="integrations_telegram_chat_id" :value="$g('integrations_telegram_chat_id')" placeholder="-1001234567890" />
                </x-field>
                <div class="sm:col-span-2"><x-button type="button" variant="secondary" size="sm" icon="bolt" x-on:click="window.sendIntegrationTest('telegram')">Send Test</x-button></div>
            </div>
        </x-card>

        <x-card title="Generic Webhook" subtitle="POST a JSON payload to any URL.">
            <div class="space-y-5">
                <x-toggle name="integrations_webhook_enabled" :checked="$en('integrations_webhook_enabled')" label="Enable Webhook" description="POST {title, body, text, product} to your endpoint." />
                <x-field label="Endpoint URL" for="integrations_webhook_url" :error="$errors->first('integrations_webhook_url')">
                    <x-input id="integrations_webhook_url" name="integrations_webhook_url" :value="$g('integrations_webhook_url')" placeholder="https://example.com/hooks/panel" />
                </x-field>
                <div><x-button type="button" variant="secondary" size="sm" icon="bolt" x-on:click="window.sendIntegrationTest('webhook')">Send Test</x-button></div>
            </div>
        </x-card>

        <div class="flex items-center justify-end gap-2">
            <x-button variant="secondary" href="{{ route('settings.index') }}">Cancel</x-button>
            <x-button type="submit" icon="check">Save</x-button>
        </div>
    </form>

    {{-- Tests use the currently SAVED values; save before testing. Separate form so it never submits the settings form. --}}
    <form method="POST" action="{{ route('settings.integrations.test') }}" id="integration-test-form" class="hidden">
        @csrf
        <input type="hidden" name="channel" id="integration-test-channel">
    </form>
    <script>
        window.sendIntegrationTest = function (ch) {
            document.getElementById('integration-test-channel').value = ch;
            document.getElementById('integration-test-form').submit();
        };
    </script>
</x-layouts.app>
