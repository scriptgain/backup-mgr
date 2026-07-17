<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Automated remote database backups. Dumps the panel database with mysqldump,
 * gzips it, and ships it to a configured remote: Local dir, FTP, SFTP, Rsync,
 * or Dropbox. Retention prunes old copies best-effort. Configured under
 * Settings > Backup & Restore; run on a schedule by the db-backup:run command.
 *
 * Auth notes: FTP uses user/pass; SFTP and Rsync use a supplied private key
 * (no sshpass on target hosts); Dropbox uses an access token.
 */
class RemoteDatabaseBackup
{
    public const TRANSPORTS = ['local', 's3', 'storagemgr', 'ftp', 'sftp', 'rsync', 'dropbox'];

    public static function enabled(): bool
    {
        return Setting::get('dbbackup_enabled') === '1';
    }

    public function run(): array
    {
        $db = config('database.connections.' . config('database.default'));
        if (($db['driver'] ?? '') !== 'mysql' || ! function_exists('exec')) {
            return $this->done(false, 'Remote backup needs MySQL and shell access on this host.');
        }

        $file = tempnam(sys_get_temp_dir(), 'dbbk') . '.sql.gz';
        if (! $this->dump($db, $file)) {
            @unlink($file);

            return $this->done(false, 'mysqldump failed.');
        }

        $name = Str::slug(config('app.name') ?: 'panel') . '-' . now()->format('Ymd-His') . '.sql.gz';
        $transport = Setting::get('dbbackup_transport') ?: 'local';
        $retention = max(1, (int) (Setting::get('dbbackup_retention') ?: 7));

        try {
            [$ok, $msg] = $this->upload($transport, $file, $name, $retention);
        } catch (\Throwable $e) {
            [$ok, $msg] = [false, $e->getMessage()];
        }
        @unlink($file);

        return $this->done($ok, $ok
            ? "Uploaded {$name} via {$transport}."
            : "Backup failed ({$transport}): {$msg}");
    }

    private function dump(array $db, string $file): bool
    {
        $cmd = sprintf(
            'MYSQL_PWD=%s mysqldump --single-transaction --quick --no-tablespaces -h%s -P%s -u%s %s | gzip > %s',
            escapeshellarg((string) $db['password']),
            escapeshellarg((string) $db['host']),
            escapeshellarg((string) ($db['port'] ?? 3306)),
            escapeshellarg((string) $db['username']),
            escapeshellarg((string) $db['database']),
            escapeshellarg($file)
        );
        exec($cmd . ' 2>/dev/null', $out, $code);

        return $code === 0 && is_file($file) && filesize($file) > 0;
    }

    private function upload(string $transport, string $file, string $name, int $retention): array
    {
        return match ($transport) {
            'local' => $this->toLocal($file, $name, $retention),
            's3' => $this->toS3($file, $name, $retention, 's3'),
            'storagemgr' => $this->toS3($file, $name, $retention, 'storagemgr'),
            'ftp' => $this->toFtp($file, $name, $retention),
            'sftp' => $this->toSftp($file, $name, $retention),
            'rsync' => $this->toRsync($file, $name),
            'dropbox' => $this->toDropbox($file, $name, $retention),
            default => [false, 'Unknown transport.'],
        };
    }

    // --- Local -------------------------------------------------------------
    private function toLocal(string $file, string $name, int $retention): array
    {
        $dir = rtrim((string) Setting::get('dbbackup_local_path'), '/');
        if (! $dir) {
            return [false, 'No local path set.'];
        }
        if (! is_dir($dir) && ! @mkdir($dir, 0700, true)) {
            return [false, "Cannot create {$dir}."];
        }
        if (! @copy($file, "{$dir}/{$name}")) {
            return [false, "Cannot write to {$dir}."];
        }
        $files = glob("{$dir}/" . Str::slug(config('app.name') ?: 'panel') . '-*.sql.gz') ?: [];
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        foreach (array_slice($files, $retention) as $old) {
            @unlink($old);
        }

        return [true, ''];
    }

    // --- FTP (ext-ftp, user/pass) -----------------------------------------
    private function toFtp(string $file, string $name, int $retention): array
    {
        $conn = @ftp_connect((string) Setting::get('dbbackup_ftp_host'), (int) (Setting::get('dbbackup_ftp_port') ?: 21), 15);
        if (! $conn) {
            return [false, 'FTP connect failed.'];
        }
        if (! @ftp_login($conn, (string) Setting::get('dbbackup_ftp_user'), (string) Setting::get('dbbackup_ftp_pass'))) {
            ftp_close($conn);

            return [false, 'FTP login failed.'];
        }
        ftp_pasv($conn, Setting::get('dbbackup_ftp_passive') !== '0');
        $dir = trim((string) Setting::get('dbbackup_ftp_path'), '/');
        $remote = ($dir ? $dir . '/' : '') . $name;
        if (! @ftp_put($conn, $remote, $file, FTP_BINARY)) {
            ftp_close($conn);

            return [false, 'FTP upload failed (check the path).'];
        }
        $list = @ftp_nlist($conn, $dir ?: '.') ?: [];
        $mine = array_values(array_filter($list, fn ($f) => str_contains($f, '.sql.gz')));
        rsort($mine); // timestamped names sort chronologically
        foreach (array_slice($mine, $retention) as $old) {
            @ftp_delete($conn, $old);
        }
        ftp_close($conn);

        return [true, ''];
    }

    // --- SFTP (openssh, key-based) ----------------------------------------
    private function toSftp(string $file, string $name, int $retention): array
    {
        $host = (string) Setting::get('dbbackup_sftp_host');
        $user = (string) Setting::get('dbbackup_sftp_user');
        $port = (int) (Setting::get('dbbackup_sftp_port') ?: 22);
        $dir = rtrim((string) Setting::get('dbbackup_sftp_path'), '/');
        if (! $host || ! $user) {
            return [false, 'SFTP host/user missing.'];
        }
        [$keyFile, $err] = $this->keyFile('dbbackup_sftp_key');
        if ($err) {
            return [false, $err];
        }
        $batch = tempnam(sys_get_temp_dir(), 'sftpb');
        $rm = "-command=ls -t {$dir}/*.sql.gz"; // (informational; prune handled below)
        file_put_contents($batch, "put {$file} {$dir}/{$name}\n");
        $ssh = "-i {$keyFile} -P {$port} -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o BatchMode=yes";
        exec('sftp ' . $ssh . ' -b ' . escapeshellarg($batch) . ' ' . escapeshellarg("{$user}@{$host}") . ' 2>&1', $out, $code);
        @unlink($batch);
        if ($code === 0) {
            // Best-effort retention: keep newest N via a remote shell over ssh.
            $prune = sprintf('cd %s && ls -1t *.sql.gz 2>/dev/null | tail -n +%d | xargs -r rm -f', escapeshellarg($dir), $retention + 1);
            exec('ssh ' . str_replace('-P ', '-p ', $ssh) . ' ' . escapeshellarg("{$user}@{$host}") . ' ' . escapeshellarg($prune) . ' 2>/dev/null');
        }
        @unlink($keyFile);

        return $code === 0 ? [true, ''] : [false, 'SFTP failed: ' . trim(implode(' ', array_slice($out, -2)))];
    }

    // --- Rsync (over ssh, key-based) --------------------------------------
    private function toRsync(string $file, string $name): array
    {
        $host = (string) Setting::get('dbbackup_rsync_host');
        $user = (string) Setting::get('dbbackup_rsync_user');
        $dir = rtrim((string) Setting::get('dbbackup_rsync_path'), '/');
        $port = (int) (Setting::get('dbbackup_rsync_port') ?: 22);
        if (! $host || ! $user) {
            return [false, 'Rsync host/user missing.'];
        }
        [$keyFile, $err] = $this->keyFile('dbbackup_rsync_key');
        if ($err) {
            return [false, $err];
        }
        $ssh = "ssh -i {$keyFile} -p {$port} -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o BatchMode=yes";
        $cmd = sprintf('rsync -e %s %s %s 2>&1', escapeshellarg($ssh), escapeshellarg($file),
            escapeshellarg("{$user}@{$host}:{$dir}/{$name}"));
        exec($cmd, $out, $code);
        @unlink($keyFile);

        return $code === 0 ? [true, ''] : [false, 'Rsync failed: ' . trim(implode(' ', array_slice($out, -2)))];
    }

    // --- Dropbox (HTTP API, access token) ---------------------------------
    private function toDropbox(string $file, string $name, int $retention): array
    {
        $token = (string) Setting::get('dbbackup_dropbox_token');
        if (! $token) {
            return [false, 'No Dropbox access token.'];
        }
        $dir = '/' . trim((string) Setting::get('dbbackup_dropbox_path'), '/');
        $dest = rtrim($dir, '/') . '/' . $name;
        $resp = Http::withToken($token)
            ->withBody(file_get_contents($file), 'application/octet-stream')
            ->withHeaders(['Dropbox-API-Arg' => json_encode(['path' => $dest, 'mode' => 'add', 'autorename' => true])])
            ->post('https://content.dropboxapi.com/2/files/upload');
        if (! $resp->successful()) {
            return [false, 'Dropbox upload failed: HTTP ' . $resp->status()];
        }
        // Retention: list the folder, delete oldest beyond N.
        $list = Http::withToken($token)->asJson()->post('https://api.dropboxapi.com/2/files/list_folder', ['path' => rtrim($dir, '/') ?: '']);
        if ($list->successful()) {
            $entries = collect($list->json('entries', []))
                ->filter(fn ($e) => str_ends_with($e['name'] ?? '', '.sql.gz'))
                ->sortByDesc('name')->values();
            foreach ($entries->slice($retention) as $e) {
                Http::withToken($token)->asJson()->post('https://api.dropboxapi.com/2/files/delete_v2', ['path' => $e['path_lower']]);
            }
        }

        return [true, ''];
    }

    // --- S3 / S3-compatible (StorageMGR, Backblaze B2, Wasabi, MinIO, AWS) --
    // Signed with an inline AWS SigV4 signer (no SDK on target installs).
    // $p selects the setting prefix ('s3' or 'storagemgr').
    private function toS3(string $file, string $name, int $retention, string $p): array
    {
        $endpoint = trim((string) Setting::get("dbbackup_{$p}_endpoint"));
        $region = (string) (Setting::get("dbbackup_{$p}_region") ?: 'us-east-1');
        $bucket = (string) Setting::get("dbbackup_{$p}_bucket");
        $ak = (string) Setting::get("dbbackup_{$p}_key");
        $sk = (string) Setting::get("dbbackup_{$p}_secret");
        $prefix = trim((string) Setting::get("dbbackup_{$p}_path"), '/');
        if (! $endpoint || ! $bucket || ! $ak || ! $sk) {
            return [false, 'Endpoint, bucket, and credentials are required.'];
        }

        $host = (string) preg_replace('#^https?://#', '', rtrim($endpoint, '/'));
        $keyPath = ($prefix ? $prefix . '/' : '') . $name;
        $resp = $this->s3Request('PUT', $host, $region, $ak, $sk, "/{$bucket}/{$keyPath}", '', (string) file_get_contents($file));
        if (! $resp->successful()) {
            return [false, 'Upload returned HTTP ' . $resp->status()];
        }

        // Retention: list under the prefix and delete oldest beyond N (best-effort).
        try {
            $q = 'list-type=2&prefix=' . rawurlencode($prefix ? $prefix . '/' : '');
            $list = $this->s3Request('GET', $host, $region, $ak, $sk, "/{$bucket}", $q, '');
            if ($list->successful() && ($xml = @simplexml_load_string($list->body()))) {
                $keys = [];
                foreach ($xml->Contents ?? [] as $c) {
                    $k = (string) $c->Key;
                    if (str_ends_with($k, '.sql.gz')) {
                        $keys[] = $k;
                    }
                }
                sort($keys); // timestamped names sort chronologically
                foreach (array_slice($keys, 0, max(0, count($keys) - $retention)) as $old) {
                    $this->s3Request('DELETE', $host, $region, $ak, $sk, "/{$bucket}/{$old}", '', '');
                }
            }
        } catch (\Throwable $e) {
            // prune is best-effort
        }

        return [true, ''];
    }

    private function s3Request(string $method, string $host, string $region, string $ak, string $sk, string $path, string $query, string $body)
    {
        $encPath = '/' . implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/'))));
        $payloadHash = hash('sha256', $body);
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $canonicalHeaders = "host:{$host}\nx-amz-content-sha256:{$payloadHash}\nx-amz-date:{$amzDate}\n";
        $signedHeaders = 'host;x-amz-content-sha256;x-amz-date';
        $canonicalRequest = implode("\n", [$method, $encPath, $query, $canonicalHeaders, $signedHeaders, $payloadHash]);
        $scope = "{$dateStamp}/{$region}/s3/aws4_request";
        $stringToSign = implode("\n", ['AWS4-HMAC-SHA256', $amzDate, $scope, hash('sha256', $canonicalRequest)]);
        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $sk, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);
        $auth = "AWS4-HMAC-SHA256 Credential={$ak}/{$scope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        $url = "https://{$host}{$encPath}" . ($query !== '' ? "?{$query}" : '');
        $req = Http::timeout(30)->withHeaders([
            'Authorization' => $auth,
            'x-amz-date' => $amzDate,
            'x-amz-content-sha256' => $payloadHash,
        ]);

        return match ($method) {
            'PUT' => $req->withBody($body, 'application/octet-stream')->put($url),
            'DELETE' => $req->delete($url),
            default => $req->get($url),
        };
    }

    private function keyFile(string $settingKey): array
    {
        $key = (string) Setting::get($settingKey);
        if (trim($key) === '') {
            return [null, 'No private key configured.'];
        }
        $path = tempnam(sys_get_temp_dir(), 'sshkey');
        file_put_contents($path, rtrim($key) . "\n");
        chmod($path, 0600);

        return [$path, null];
    }

    private function done(bool $ok, string $message): array
    {
        Setting::put('dbbackup_last_run_at', now()->toIso8601String());
        Setting::put('dbbackup_last_result', ($ok ? 'ok' : 'error') . ': ' . $message);

        return ['ok' => $ok, 'message' => $message];
    }
}
