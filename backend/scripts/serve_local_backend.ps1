param(
    [int]$Port = 8000
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$serverScript = Join-Path $projectRoot 'server.php'
$artisan = Join-Path $projectRoot 'artisan'
$publicBuildManifest = Join-Path $projectRoot 'public\build\manifest.json'
$filamentCss = Join-Path $projectRoot 'public\css\filament\filament\app.css'
$laragonRoot = Split-Path -Parent (Split-Path -Parent (Split-Path -Parent $projectRoot))
$postgresBin = Join-Path $laragonRoot 'bin\postgresql\postgresql\bin'
$pgCtl = Join-Path $postgresBin 'pg_ctl.exe'
$postgresDataDir = Join-Path $laragonRoot 'data\postgresql'
$postgresPidFile = Join-Path $postgresDataDir 'postmaster.pid'
$postgresLog = Join-Path $postgresDataDir 'runtime-start.log'

function Test-TcpPort {
    param(
        [string]$HostName,
        [int]$Port
    )

    $client = New-Object System.Net.Sockets.TcpClient

    try {
        $async = $client.BeginConnect($HostName, $Port, $null, $null)

        if (-not $async.AsyncWaitHandle.WaitOne(1000, $false)) {
            return $false
        }

        $client.EndConnect($async)

        return $true
    }
    catch {
        return $false
    }
    finally {
        $client.Dispose()
    }
}

function Ensure-LocalPostgreSql {
    param(
        [string]$HostName,
        [int]$DbPort
    )

    if ($HostName -notin @('127.0.0.1', 'localhost')) {
        return
    }

    if (Test-TcpPort -HostName $HostName -Port $DbPort) {
        return
    }

    if (-not (Test-Path $pgCtl)) {
        throw "pg_ctl.exe introuvable dans $postgresBin"
    }

    if (-not (Test-Path $postgresDataDir)) {
        throw "Répertoire de données PostgreSQL introuvable dans $postgresDataDir"
    }

    if (Test-Path $postgresPidFile) {
        $pidContent = Get-Content $postgresPidFile -ErrorAction SilentlyContinue
        $postgresPid = if ($pidContent.Count -gt 0) { $pidContent[0] } else { $null }

        if ($postgresPid -and -not (Get-Process -Id $postgresPid -ErrorAction SilentlyContinue)) {
            $stalePidFile = "$postgresPidFile.stale.$([DateTimeOffset]::UtcNow.ToUnixTimeSeconds())"
            Move-Item -Path $postgresPidFile -Destination $stalePidFile -Force
        }
    }

    & $pgCtl -D $postgresDataDir -l $postgresLog start | Out-Host

    for ($attempt = 0; $attempt -lt 30; $attempt++) {
        if (Test-TcpPort -HostName $HostName -Port $DbPort) {
            return
        }

        Start-Sleep -Seconds 1
    }

    throw "PostgreSQL ne répond pas sur ${HostName}:$DbPort après démarrage. Consultez $postgresLog"
}

if (-not (Test-Path $serverScript)) {
    throw "server.php introuvable dans $projectRoot"
}

$envFile = Join-Path $projectRoot '.env'
$dbHost = '127.0.0.1'
$dbPort = 5432

if (Test-Path $envFile) {
    $envLines = Get-Content $envFile -ErrorAction SilentlyContinue

    foreach ($line in $envLines) {
        if ($line -match '^(CENTRAL_DB_HOST|DB_HOST)=(.+)$' -and $dbHost -eq '127.0.0.1') {
            $dbHost = $Matches[2].Trim()
        }

        if ($line -match '^(CENTRAL_DB_PORT|DB_PORT)=(\d+)$' -and $dbPort -eq 5432) {
            $dbPort = [int] $Matches[2]
        }
    }
}

Ensure-LocalPostgreSql -HostName $dbHost -DbPort $dbPort

if (-not (Test-Path $filamentCss)) {
    & php $artisan filament:assets --ansi
}

if (-not (Test-Path $publicBuildManifest)) {
    npm --prefix $projectRoot run build
}

Write-Host "Starting backend on http://127.0.0.1:$Port using $serverScript" -ForegroundColor Yellow
& php -S "127.0.0.1:$Port" $serverScript
