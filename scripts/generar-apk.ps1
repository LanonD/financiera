param(
    [string]$Repo = "LanonD/financiera",
    [string]$Branch = "",
    [string]$Workflow = "android-apk.yml"
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($Branch)) {
    $Branch = (git branch --show-current).Trim()
}

if ([string]::IsNullOrWhiteSpace($Branch)) {
    $Branch = "master"
}

$token = $env:GITHUB_TOKEN

if ([string]::IsNullOrWhiteSpace($token)) {
    Write-Host "Falta GITHUB_TOKEN." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Opcion rapida sin script:"
    Write-Host "1. Sube tus cambios con git push."
    Write-Host "2. Abre https://github.com/$Repo/actions/workflows/$Workflow"
    Write-Host "3. Presiona 'Run workflow' y elige la rama: $Branch"
    Write-Host ""
    Write-Host "Para usar este script, crea un GitHub token con permiso Actions/Workflow y ejecuta:"
    Write-Host '$env:GITHUB_TOKEN="TU_TOKEN"'
    Write-Host ".\scripts\generar-apk.ps1 -Branch $Branch"
    exit 1
}

$uri = "https://api.github.com/repos/$Repo/actions/workflows/$Workflow/dispatches"
$headers = @{
    "Authorization" = "Bearer $token"
    "Accept" = "application/vnd.github+json"
    "X-GitHub-Api-Version" = "2022-11-28"
}
$body = @{ ref = $Branch } | ConvertTo-Json

Invoke-RestMethod -Method Post -Uri $uri -Headers $headers -Body $body -ContentType "application/json"

Write-Host "Workflow enviado correctamente." -ForegroundColor Green
Write-Host "Rama: $Branch"
Write-Host "APK: https://github.com/$Repo/actions/workflows/$Workflow"
