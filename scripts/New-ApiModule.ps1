#Requires -Version 5.1
<#
.SYNOPSIS
    Scaffold API controller + Form Requests + optional Model/Migration/Vue + checklist.
    (SYNOPSIS tieng Viet: tao Controller API + Request mau + checklist.)
#>
param(
    [Parameter(Mandatory = $true)]
    [string] $Name,

    [Parameter(Mandatory = $true)]
    [string] $Subdir,

    [switch] $IncludeVue,
    [switch] $Model,
    [switch] $Migration,
    [switch] $DryRun
)

$ErrorActionPreference = 'Stop'
$RepoRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
Set-Location $RepoRoot

if ($Name -notmatch '^[A-Za-z][A-Za-z0-9]+$') {
    throw 'Invalid -Name: use PascalCase (e.g. TaxiVoucher).'
}
$Subdir = $Subdir.Trim().TrimEnd('/\')
if ($Subdir -notmatch '^[A-Za-z][A-Za-z0-9]*(/[A-Za-z][A-Za-z0-9]*)*$') {
    throw 'Invalid -Subdir: letters, digits, slashes only (e.g. Taxi or Admin/Reports).'
}

$controllerClass = "${Name}Controller"
$controllerPath = "App/Http/Controllers/Api/$Subdir/$controllerClass"
$requestStore = "Store${Name}Request"
$requestUpdate = "Update${Name}Request"
$requestNs = "App/Http/Requests/Api/$Subdir"

$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php -and -not $DryRun) {
    throw 'php not found in PATH (required for artisan).'
}

function Invoke-Artisan {
    param([Parameter(Mandatory)][string[]] $Arguments)
    if ($DryRun) {
        Write-Host ('DRY: php artisan ' + ($Arguments -join ' ')) -ForegroundColor Yellow
        return
    }
    & php artisan @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "artisan failed: $($Arguments -join ' ')"
    }
}

Invoke-Artisan @('make:controller', $controllerPath, '--api', '--no-interaction')
Invoke-Artisan @('make:request', "Api/$Subdir/$requestStore", '--no-interaction')
Invoke-Artisan @('make:request', "Api/$Subdir/$requestUpdate", '--no-interaction')

if ($Model) {
    Invoke-Artisan @('make:model', $Name, '--no-interaction')
}

if ($Migration) {
    $table = [regex]::Replace($Name, '(?<!^)([A-Z])', '_$1').ToLowerInvariant()
    Invoke-Artisan @('make:migration', "create_${table}_table", "--create=$table", '--no-interaction')
}

$viewSeg = ($Subdir -split '/')[-1].ToLower()
$vueDir = Join-Path $RepoRoot "resources/js/src/views/$viewSeg"
$vueFile = Join-Path $vueDir "${Name}View.vue"

if ($IncludeVue) {
    $vueTemplate = @'
<template>
  <div class="p-4">
    <h1 class="text-lg font-semibold text-slate-900">__NAME__</h1>
    <p class="mt-1 text-sm text-slate-600">TODO: wire API + i18n.</p>
  </div>
</template>

<script setup>
// import { ref, onMounted } from 'vue'
// import http from '../../api/http'
</script>

<style scoped>
</style>
'@.Replace('__NAME__', $Name)

    if (-not $DryRun) {
        New-Item -ItemType Directory -Path $vueDir -Force | Out-Null
        Set-Content -Path $vueFile -Value $vueTemplate -Encoding UTF8
        Write-Host "Created $vueFile" -ForegroundColor Green
    } else {
        Write-Host "DRY: would create $vueFile" -ForegroundColor Yellow
    }
}

$spaMutateHint = if ($Subdir -match 'Driver') {
    'driver-mutate.php or common-mutate.php'
} else {
    'dispatch-staff-mutate.php (or common-mutate if shared)'
}
$spaReadHint = if ($Subdir -match 'Driver') {
    'driver-read.php'
} else {
    'dispatch-staff-read.php and/or common-read.php'
}

$viewTodo = if ($IncludeVue) {
    "- [ ] Finish **resources/js/src/views/$viewSeg/${Name}View.vue**"
} else {
    '- [ ] Add view (re-run with **-IncludeVue**) or create manually'
}

$checklist = @"
## Checklist: $Name

### Backend
- [ ] Register routes in **routes/api/spa/** — read: $spaReadHint — mutate: $spaMutateHint
- [ ] Controller: **app/Http/Controllers/Api/$Subdir/$controllerClass.php** (thin; use Service)
- [ ] Validation: **$requestNs/$requestStore.php**, **$requestUpdate.php**
- [ ] Policy / Spatie permission if needed
- [ ] JsonResource if response is non-trivial
- [ ] **tests/Feature/**

### Frontend
- [ ] **resources/js/src/api/<module>.js** — API helpers
- [ ] **resources/js/src/router/index.js** — path, meta.title, meta.featureKey
$viewTodo

### Domain
- [ ] Link FR/UC from SRS in ticket
"@

$docDir = Join-Path $RepoRoot 'docs/scaffold'
$checkPath = Join-Path $docDir "$Name-CHECKLIST.md"

if (-not $DryRun) {
    New-Item -ItemType Directory -Path $docDir -Force | Out-Null
    Set-Content -Path $checkPath -Value $checklist -Encoding UTF8
    Write-Host "Checklist: $checkPath" -ForegroundColor Green
} else {
    Write-Host "DRY: checklist -> $checkPath" -ForegroundColor Yellow
}

$hintNs = 'App\Http\Controllers\Api\' + ($Subdir -replace '/', '\') + '\'
Write-Host ""
Write-Host "Suggested controller class: ${hintNs}${controllerClass}" -ForegroundColor Cyan
