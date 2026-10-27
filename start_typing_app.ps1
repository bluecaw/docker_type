$dockerDesktop = Join-Path $env:ProgramFiles 'Docker\Docker\Docker Desktop.exe'

if (!(Test-Path $dockerDesktop)) {
    Write-Host 'Docker Desktop was not found.'
    Read-Host 'Press Enter to close'
    exit 1
}

$running = Get-Process -Name 'Docker Desktop' -ErrorAction SilentlyContinue
if (!$running) {
    Start-Process -FilePath $dockerDesktop
}

Write-Host 'Waiting for Docker...'
$deadline = (Get-Date).AddMinutes(3)
$ready = $false

while ((Get-Date) -lt $deadline) {
    & docker info *> $null
    if ($LASTEXITCODE -eq 0) {
        $ready = $true
        break
    }
    Start-Sleep -Seconds 2
}

if (!$ready) {
    Write-Host 'Docker did not start within 3 minutes.'
    Read-Host 'Press Enter to close'
    exit 1
}

Set-Location $PSScriptRoot
& docker compose up -d

if ($LASTEXITCODE -ne 0) {
    Write-Host 'The app containers could not be started.'
    Read-Host 'Press Enter to close'
    exit 1
}

Start-Sleep -Seconds 3
Start-Process -FilePath 'http://localhost:8080'