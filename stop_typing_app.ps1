Set-Location $PSScriptRoot

& docker compose down
if ($LASTEXITCODE -ne 0) {
    Write-Host 'The app containers could not be stopped.'
    Read-Host 'Press Enter to close'
    exit 1
}

Write-Host 'The app containers have stopped.'
Write-Host 'Stopping Docker Desktop...'

& docker desktop stop
if ($LASTEXITCODE -ne 0) {
    Write-Host 'Containers stopped, but Docker Desktop could not be stopped with this CLI.'
    Write-Host 'You can quit Docker Desktop from its notification-area menu.'
    Read-Host 'Press Enter to close'
    exit 1
}

Write-Host 'Docker Desktop has stopped.'