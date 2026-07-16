<?php

return [
    // Base directory (on the node that runs the backup) where auto-created
    // default filesystem repositories are placed, one subfolder per host.
    // kopia creates the directory; the agent runs as root and chowns it to the
    // owner of the parent so file managers can see it.
    'repo_base' => env('BACKUP_REPO_BASE', '/var/backups'),

    // Base directory that holds every file Share's folder (served publicly at
    // /s/{slug} and /d/{token}). One subfolder per share.
    'shares_base' => env('BACKUP_SHARES_BASE', storage_path('app/shares')),

    // Dev convenience: when a request's real client IP (read from Cloudflare's
    // CF-Connecting-IP header) starts with this prefix, the login page shows a
    // one-click sign-in button for the configured email. Blank disables it.
    // Use an IP prefix (e.g. an IPv6 /64 like "2600:8800:2184:f00:") so it
    // survives the client's rotating low-order bits.
    'autofill_ip' => env('DEV_AUTOFILL_IP', ''),
    'autofill_email' => env('DEV_AUTOFILL_EMAIL', ''),
];
