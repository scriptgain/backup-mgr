<?php

return [
    // Base directory (on the node that runs the backup) where auto-created
    // default filesystem repositories are placed, one subfolder per host.
    // kopia creates the directory; the agent runs as root and chowns it to the
    // owner of the parent so file managers can see it.
    'repo_base' => env('BACKUP_REPO_BASE', '/var/backups'),

    // Base directory (on the Director's gateway) where "ingest" connections drop
    // pushed files, one subfolder per connection. The gateway agent's receive
    // (SFTP) server is rooted here per connection; a scheduled job snapshots it.
    'ingest_base' => env('BACKUP_INGEST_BASE', '/var/backups/ingest'),

    // Passive data-port range the gateway's FTP receive server allocates from
    // for PASV transfers. Open this range (plus each FTP connection's control
    // port) inbound on the gateway's firewall.
    'ingest_ftp_pasv_min' => (int) env('BACKUP_INGEST_FTP_PASV_MIN', 30000),
    'ingest_ftp_pasv_max' => (int) env('BACKUP_INGEST_FTP_PASV_MAX', 30100),

    // Dev convenience: when a request's real client IP (read from Cloudflare's
    // CF-Connecting-IP header) starts with this prefix, the login page shows a
    // one-click sign-in button for the configured email. Blank disables it.
    // Use an IP prefix (e.g. an IPv6 /64 like "2600:8800:2184:f00:") so it
    // survives the client's rotating low-order bits.
    'autofill_ip' => env('DEV_AUTOFILL_IP', ''),
    'autofill_email' => env('DEV_AUTOFILL_EMAIL', ''),
];
