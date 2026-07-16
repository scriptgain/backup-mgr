<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Firewall helpers: parse and match the access-limit allowlist. Supports plain
 * IPv4/IPv6 addresses and CIDR ranges (e.g. 203.0.113.0/24, 2001:db8::/32).
 */
class Firewall
{
    /** The configured allowlist as an array of trimmed, non-empty entries. */
    public static function allowlist(): array
    {
        return self::parse((string) Setting::get('ip_allowlist', ''));
    }

    /** Split a textarea value (newline or comma separated) into unique entries. */
    public static function parse(string $raw): array
    {
        $parts = preg_split('/[\r\n,]+/', $raw) ?: [];
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, fn ($p) => $p !== '');

        return array_values(array_unique($parts));
    }

    /** True if the entry is a valid IP address or CIDR range. */
    public static function validEntry(string $entry): bool
    {
        $entry = trim($entry);
        if ($entry === '') {
            return false;
        }
        if (! str_contains($entry, '/')) {
            return filter_var($entry, FILTER_VALIDATE_IP) !== false;
        }
        [$subnet, $bits] = explode('/', $entry, 2);
        if (! ctype_digit($bits)) {
            return false;
        }
        $bin = @inet_pton($subnet);
        if ($bin === false) {
            return false;
        }

        return (int) $bits >= 0 && (int) $bits <= strlen($bin) * 8;
    }

    /** True if $ip is covered by any entry in the list. */
    public static function ipAllowed(?string $ip, array $list): bool
    {
        if ($ip === null || $ip === '') {
            return false;
        }
        foreach ($list as $entry) {
            if (self::ipMatches($ip, $entry)) {
                return true;
            }
        }

        return false;
    }

    /** Match a single IP against a plain address or CIDR range. */
    public static function ipMatches(string $ip, string $entry): bool
    {
        $entry = trim($entry);
        if ($entry === '') {
            return false;
        }
        if (! str_contains($entry, '/')) {
            return $ip === $entry;
        }
        [$subnet, $bits] = explode('/', $entry, 2);
        $bits = (int) $bits;

        $ipBin = @inet_pton($ip);
        $subBin = @inet_pton($subnet);
        if ($ipBin === false || $subBin === false || strlen($ipBin) !== strlen($subBin)) {
            return false;
        }
        $maxBits = strlen($ipBin) * 8;
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }
        if ($bits === 0) {
            return true;
        }

        $whole = intdiv($bits, 8);
        $remainder = $bits % 8;
        if ($whole > 0 && strncmp($ipBin, $subBin, $whole) !== 0) {
            return false;
        }
        if ($remainder === 0) {
            return true;
        }
        $mask = chr((0xff << (8 - $remainder)) & 0xff);

        return (ord($ipBin[$whole]) & ord($mask)) === (ord($subBin[$whole]) & ord($mask));
    }
}
