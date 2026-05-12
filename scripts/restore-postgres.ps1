param(
    [Parameter(Mandatory = $true)]
    [string]$Database,

    [Parameter(Mandatory = $true)]
    [string]$BackupFile,

    [string]$Host = "127.0.0.1",
    [int]$Port = 5432,
    [string]$Username = "postgres",
    [string]$PgBinPath = "c:\laragon\bin\postgresql\postgresql\bin",
    [switch]$DropAndRecreate,
    [switch]$PlainSql
)

$pgRestore = Join-Path $PgBinPath "pg_restore.exe"
$psql = Join-Path $PgBinPath "psql.exe"
$dropDb = Join-Path $PgBinPath "dropdb.exe"
$createdb = Join-Path $PgBinPath "createdb.exe"

if (-not (Test-Path $BackupFile)) {
    throw "Fichier de sauvegarde introuvable: $BackupFile"
}

if ($DropAndRecreate) {
    & $dropDb "--if-exists" "--host=$Host" "--port=$Port" "--username=$Username" $Database
    & $createdb "--host=$Host" "--port=$Port" "--username=$Username" $Database
}

if ($PlainSql) {
    if (-not (Test-Path $psql)) {
        throw "psql introuvable: $psql"
    }

    & $psql "--host=$Host" "--port=$Port" "--username=$Username" "--dbname=$Database" "--file=$BackupFile"
} else {
    if (-not (Test-Path $pgRestore)) {
        throw "pg_restore introuvable: $pgRestore"
    }

    & $pgRestore "--clean" "--if-exists" "--no-owner" "--host=$Host" "--port=$Port" "--username=$Username" "--dbname=$Database" $BackupFile
}

if ($LASTEXITCODE -ne 0) {
    throw "La restauration PostgreSQL a échoué pour $Database"
}

Write-Output "Restore completed for $Database"
