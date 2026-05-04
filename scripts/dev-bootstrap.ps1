#Requires -Version 5.1
<#
.SYNOPSIS
    Cài composer/npm, .env mẫu, app key. Không tải SRS hay gọi API ngoài.
#>
param(
    [switch] $SkipComposer,
    [switch] $SkipNpm,
    [switch] $Migrate,
    [switch] $Seed
)

$ErrorActionPreference = 'Stop'
$RepoRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
Set-Location $RepoRoot

Write-Host "[va-dieuvan] Repo: $RepoRoot" -ForegroundColor Cyan

if (-not $SkipComposer) {
    if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
        Write-Warning "Không tìm thấy composer trong PATH. Bỏ qua hoặc cài Composer."
    } else {
        composer install --no-interaction
    }
}

if (-not $SkipNpm) {
    if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
        Write-Warning "Không tìm thấy npm trong PATH."
    } else {
        if (Test-Path (Join-Path $RepoRoot 'package-lock.json')) {
            npm ci
        } else {
            npm install
        }
    }
}

$envFile = Join-Path $RepoRoot '.env'
$envExample = Join-Path $RepoRoot '.env.example'
if (-not (Test-Path $envFile) -and (Test-Path $envExample)) {
    Copy-Item $envExample $envFile
    Write-Host "Đã tạo .env từ .env.example" -ForegroundColor Green
}

if (Get-Command php -ErrorAction SilentlyContinue) {
    php artisan key:generate --force 2>$null
    if ($Migrate) {
        php artisan migrate --force
        if ($Seed) {
            php artisan db:seed --force
        }
    }
} else {
    Write-Warning "php không có trong PATH — bỏ qua key:generate / migrate."
}

Write-Host "Xong. Chạy: php artisan serve và npm run dev" -ForegroundColor Green
