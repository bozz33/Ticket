$ErrorActionPreference = 'Stop'

$services = @(
    'api-gateway-service',
    'notifications-service',
    'media-service',
    'catalog-search-service',
    'analytics-service',
    'access-checkin-service'
)

foreach ($service in $services) {
    Push-Location (Join-Path $PSScriptRoot "..\$service")
    try {
        Write-Host "Testing $service"
        npm test
    } finally {
        Pop-Location
    }
}
