# BackupMGR

**Deduplicated, encrypted backup for a whole fleet of servers, with restores you
can actually test.** Self-hosted, by [ScriptGain](https://scriptgain.com).

**[Try the live demo →](https://backup-demo.scriptgain.com)** — no signup required.

## Who it's for

Sysadmins and MSPs backing up more than one machine: web servers, database hosts,
mail servers, branch offices, and the customer boxes you are responsible for at
2am. One control plane, every host, one place to answer "when did that last back
up and can we restore it".

## What it does

**Reach the hosts however you can**
Pull over **SSH/rsync**, **SFTP**, or **FTP**; push from an installed **agent**;
back up a local path; or pull from **S3**. A host that only exposes SSH is not a
special case.

**Store it efficiently and encrypted**
Repositories are deduplicated and encrypted, so a fleet of similar servers costs a
fraction of full copies, and what lands on disk is unreadable without the key.

**Keep only what you need**
Retention policies per repository, applied automatically. Schedule templates so a
new host inherits a sensible schedule instead of being configured from scratch.

**Prove the restore**
Restores are first-class objects with their own history — because a backup nobody
has restored from is a hypothesis, not a backup.

**Group the fleet the way you think about it**
Locations and hosts, with storage devices and repositories mapped to them.

**Know when it breaks**
Run history per host with successes, failures, and in-flight jobs, plus outbound
notifications to the channels you already watch.

**Run it like production**
Users and roles, two-factor authentication, an IP firewall with an escape hatch,
API tokens, a full audit log, its own database backups, host and SSL settings, and
in-place signed updates.

## Current state

**Version 1.5.3.** This is the oldest and most exercised product in the range — it
runs ScriptGain's own fleet backups nightly across multiple hosts, including
multi-path pulls and live servers where rsync legitimately reports partial-transfer
warnings mid-copy.

## Install

Point a fresh Debian or Ubuntu server at your domain and run, as root:

```
curl -fsSL https://install.scriptgain.com | sudo bash -s -- backup-mgr DOMAIN=backup.example.com SSL=1 EMAIL=you@example.com
```

Then open `https://your.domain/setup` to create the first account and enter your
licence key. Add a location, a repository, and your first host.

## Where things live

| Surface | Path |
| --- | --- |
| Console | `/` |
| First-run setup | `/setup` |
| Agent and API endpoints | `/api` |

## Running it

Hosts, repositories, schedules, retention, notifications, and every operator
setting are managed in the console rather than in files on the server.

Maintenance tasks from the command line:

| Command | What it does |
| --- | --- |
| `php artisan backup:bootstrap` | Seeds baseline settings and defaults. Safe to re-run. |
| `php artisan backup:dispatch-due` | Starts any backup whose window has arrived. Runs on a timer. |
| `php artisan backup:housekeeping` | Applies retention, prunes old runs, trims the audit log. |
| `php artisan backup:license <key>` | Sets or re-checks your licence key. |
| `php artisan app:update` | Applies a signed release. |
| `php artisan db-backup:run` | Backs up BackupMGR's own database. |
| `php artisan firewall:clear` | Gets you back in if an IP rule locks you out. |

## Requirements

A Linux server with PHP 8.3 and MySQL or MariaDB for the control plane, plus
storage for the repositories. Disk and network are what matter; the panel itself is
light. Give the control plane SSH keys to the hosts it pulls from.

## Licensing

One activation per licence by default, validated against
`https://scriptgain.com/v1`. Buy or manage yours at
[scriptgain.com/products/backup-manager](https://scriptgain.com/products/backup-manager).
