<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

/**
 * Posts panel alerts to configured outbound channels (Slack, Discord,
 * Telegram, generic webhook). Fail-soft: a channel error never throws to the
 * caller. Configured under Settings > Integrations.
 */
class IntegrationNotifier
{
    public const CHANNELS = ['slack', 'discord', 'telegram', 'webhook'];

    /** Send a message to every enabled channel. Returns the number delivered. */
    public static function notify(string $title, string $body = ''): int
    {
        $sent = 0;
        foreach (self::CHANNELS as $ch) {
            if (Setting::get("integrations_{$ch}_enabled") === '1' && self::send($ch, $title, $body)) {
                $sent++;
            }
        }

        return $sent;
    }

    /** Send to one channel regardless of its enabled flag (used by the test button). */
    public static function send(string $channel, string $title, string $body = ''): bool
    {
        $text = trim($title . "\n" . $body);
        try {
            return match ($channel) {
                'slack' => self::post(Setting::get('integrations_slack_url'), ['text' => $text]),
                'discord' => self::post(Setting::get('integrations_discord_url'), ['content' => $text]),
                'webhook' => self::post(Setting::get('integrations_webhook_url'), [
                    'title' => $title, 'body' => $body, 'text' => $text, 'product' => config('brand.name'),
                ]),
                'telegram' => self::telegram($text),
                default => false,
            };
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function post(?string $url, array $payload): bool
    {
        if (! $url) {
            return false;
        }

        return Http::timeout(8)->asJson()->post($url, $payload)->successful();
    }

    private static function telegram(string $text): bool
    {
        $token = Setting::get('integrations_telegram_token');
        $chat = Setting::get('integrations_telegram_chat_id');
        if (! $token || ! $chat) {
            return false;
        }

        return Http::timeout(8)->asJson()
            ->post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chat, 'text' => $text])
            ->successful();
    }
}
