# BackupMGR agent installer for Windows (run in an ELEVATED PowerShell):
#   $env:BMGR_MASTER='https://MASTER'; $env:BMGR_TOKEN='<enroll-token>'; irm https://MASTER/downloads/install-windows.ps1 | iex
$ErrorActionPreference = 'Stop'
$Master = $env:BMGR_MASTER
$Token  = $env:BMGR_TOKEN
if (-not $Master -or -not $Token) { Write-Host "Set `$env:BMGR_MASTER and `$env:BMGR_TOKEN first." -ForegroundColor Red; return }
$Master = $Master.TrimEnd('/')
$Dest = "$env:ProgramData\BackupMGR"
$Cfg  = "$Dest\agent.json"
New-Item -ItemType Directory -Force -Path $Dest | Out-Null
Write-Host "==> Downloading agent + kopia from $Master/downloads/win"
Invoke-WebRequest -UseBasicParsing -Uri "$Master/downloads/win/agent.exe" -OutFile "$Dest\agent.exe"
Invoke-WebRequest -UseBasicParsing -Uri "$Master/downloads/win/kopia.exe" -OutFile "$Dest\kopia.exe"
Write-Host "==> Enrolling with the Manager"
& "$Dest\agent.exe" enroll -master $Master -token $Token -config $Cfg
if ($LASTEXITCODE -ne 0) { Write-Host "Enrollment failed." -ForegroundColor Red; return }
Write-Host "==> Registering scheduled task (runs at startup as SYSTEM, auto-restarts)"
$taskName = "BackupMGRAgent"
$action  = New-ScheduledTaskAction -Execute "$Dest\agent.exe" -Argument "run -config `"$Cfg`""
$trigger = New-ScheduledTaskTrigger -AtStartup
$set     = New-ScheduledTaskSettingsSet -RestartCount 999 -RestartInterval (New-TimeSpan -Minutes 1) -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -ExecutionTimeLimit ([TimeSpan]::Zero)
$prin    = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $set -Principal $prin -Force | Out-Null
Start-ScheduledTask -TaskName $taskName
Write-Host "==> Done. BackupMGR agent enrolled + running (Get-ScheduledTask BackupMGRAgent)." -ForegroundColor Green
