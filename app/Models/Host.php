<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Host extends Model
{
    use \App\Models\Concerns\Auditable;
    protected $fillable = [
        'director_id', 'user_id', 'name', 'connection_type', 'hostname', 'ip_address', 'port', 'username',
        'auth_type', 'secret', 'private_key', 'ftp_accounts', 'ingest_protocol', 'ingest_folder',
        'disks', 'default_schedule_template_id',
        'os', 'arch', 'agent_version', 'status', 'notes',
    ];

    protected $hidden = ['secret', 'private_key', 'ftp_accounts', 'api_key', 'enrollment_token'];

    protected function casts(): array
    {
        return [
            'disks' => 'array',
            'secret' => 'encrypted',
            'private_key' => 'encrypted',
            'ftp_accounts' => 'encrypted:array',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * FTP accounts for a multiftp host, shaped for the agent's job payload
     * (decrypted). Empty for every other host type.
     */
    public function ftpAccountsForAgent(): array
    {
        $out = [];
        foreach ((array) ($this->ftp_accounts ?? []) as $a) {
            if (empty($a['host']) || empty($a['username'])) {
                continue;
            }
            $out[] = [
                'label'    => $a['label'] ?? $a['username'],
                'host'     => $a['host'],
                'port'     => (string) ($a['port'] ?? '21'),
                'user'     => $a['username'],
                'password' => $a['password'] ?? '',
                'path'     => $a['path'] ?? '',
            ];
        }

        return $out;
    }

    /**
     * The default listener port for this ingest host (falls back to the
     * protocol default when none is set).
     */
    public function ingestPort(): int
    {
        if ($this->port) {
            return (int) $this->port;
        }

        return match ($this->ingest_protocol) {
            'ftp' => 21,
            's3' => 9443, // HTTPS S3-compatible endpoint
            default => 2022, // sftp — 22 is taken by the host's real sshd
        };
    }

    /**
     * The S3 bucket name for an s3 ingest connection: the basename of the drop
     * folder (kept simple — the drop folder *is* the bucket root).
     */
    public function ingestBucket(): string
    {
        return $this->ingest_folder ? basename($this->ingest_folder) : '';
    }

    /** The paste-ready S3 endpoint URL for an s3 ingest connection. */
    public function ingestS3Endpoint(): string
    {
        $host = $this->ip_address
            ?: ($this->director?->hostname ?: (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost'));

        return 'https://' . $host . ':' . $this->ingestPort();
    }

    /**
     * Ingest connection config for the gateway agent's receive servers, shaped
     * for the heartbeat payload (decrypted). SFTP, FTP and S3 are all served.
     */
    public function ingestConfigForAgent(): ?array
    {
        if ($this->connection_type !== 'ingest') {
            return null;
        }
        if (! in_array($this->ingest_protocol, ['sftp', 'ftp', 's3'], true)) {
            return null;
        }
        if (! $this->username || ! $this->ingest_folder) {
            return null;
        }

        $cfg = [
            'id'       => (string) $this->id,
            'protocol' => $this->ingest_protocol,
            'username' => $this->username, // sftp/ftp user OR s3 access-key
            'password' => (string) ($this->secret ?? ''), // decrypted by the model cast
            'folder'   => $this->ingest_folder,
            'port'     => $this->ingestPort(),
        ];

        // FTP needs passive-mode plumbing: an advertised public IP/host for the
        // PASV reply and a data-port range, plus explicit-FTPS availability.
        if ($this->ingest_protocol === 'ftp') {
            $cfg['tls'] = true;
            $cfg['public_host'] = $this->ip_address
                ?: ($this->director?->hostname ?: (parse_url((string) config('app.url'), PHP_URL_HOST) ?: ''));
            $cfg['pasv_min'] = (int) config('backup.ingest_ftp_pasv_min', 30000);
            $cfg['pasv_max'] = (int) config('backup.ingest_ftp_pasv_max', 30100);
        }

        // S3 is an HTTPS S3-compatible endpoint; the bucket = drop-folder basename.
        if ($this->ingest_protocol === 's3') {
            $cfg['tls'] = true;
            $cfg['bucket'] = $this->ingestBucket();
        }

        return $cfg;
    }

    /**
     * Live status derived from check-ins. Agent hosts that stopped polling for
     * longer than the configured window read "offline" even if the stored
     * status still says "online". Agentless hosts have no agent to check in, so
     * their stored status is returned as-is.
     */
    public function getEffectiveStatusAttribute(): string
    {
        if ($this->connection_type !== 'agent') {
            return $this->status ?: 'pending';
        }
        if (! $this->last_seen_at) {
            return $this->status === 'online' ? 'online' : 'pending';
        }
        $window = max(1, (int) config('backup.offline_after_minutes', 5));
        if ($this->last_seen_at->lt(now()->subMinutes($window))) {
            return 'offline';
        }

        return 'online';
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(Director::class);
    }

    /** Direct owner, if assigned. Falls back to the director's owner when null. */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The effective owner id: the host's own owner, else the director's. */
    public function getOwnerIdAttribute(): ?int
    {
        return $this->user_id ?? $this->director?->user_id;
    }

    /**
     * Limit to hosts a user may see: admins see all; others see hosts assigned
     * directly to them, plus unassigned hosts under a director they own.
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if ($user && ! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('hosts.user_id', $user->id)
                    ->orWhere(function ($q2) use ($user) {
                        $q2->whereNull('hosts.user_id')
                            ->whereHas('director', fn ($d) => $d->where('user_id', $user->id));
                    });
            });
        }

        return $query;
    }

    /** True when the given user may view/manage this host. */
    public function isVisibleTo(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->isAdmin() || $this->user_id === $user->id) {
            return true;
        }

        return $this->user_id === null && $this->director?->user_id === $user->id;
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(BackupJob::class);
    }
}
