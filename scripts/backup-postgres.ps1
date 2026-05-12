param(
    [Parameter(Mandatory = $true)]
    [string]$Database,

    [string]$Host = "127.0.0.1",
    [int]$Port = 5432,
    [string]$Username = "postgres",
    [string]$PgBinPath = "c:\laragon\bin\postgresql\postgresql\bin",
    [string]$OutputDirectory = "c:\laragon\www\Ticket\storage\backups\postgres",
    [string]$Format = "custom"
)

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$extension = if ($Format -eq "plain") { "sql" } else { "dump" }
$outputPath = Join-Path $OutputDirectory "$Database-$timestamp.$extension"
$pgDump = Join-Path $PgBinPath "pg_dump.exe"

if (-not (Test-Path $pgDump)) {
    throw "pg_dump introuvable: $pgDump"
}

New-Item -ItemType Directory -Force -Path $OutputDirectory | Out-Null

$args = @(
    "--host=$Host",
    "--port=$Port",
    "--username=$Username",
    "--dbname=$Database",
    "--file=$outputPath"
)

if ($Format -eq "plain") {
    $args += "--format=p"
} else {
    $args += "--format=c"
}

& $pgDump @args

if ($LASTEXITCODE -ne 0) {
    throw "La sauvegarde PostgreSQL a échoué pour $Database"
}

Write-Output $outputPath
