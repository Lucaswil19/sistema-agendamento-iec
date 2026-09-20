param(
    [ValidateSet('start', 'stop')]
    [string]$Action = 'start',
    [ValidateRange(1024, 65535)]
    [int]$Port = 8000,
    [string]$XamppPath = 'C:\xampp'
)

$ErrorActionPreference = 'Stop'
$projectPath = Split-Path -Parent $PSScriptRoot
$runtimePath = Join-Path $projectPath "storage\app\local-server-$Port"
$configPath = Join-Path $runtimePath 'httpd.conf'
$pidPath = Join-Path $runtimePath 'httpd.pid'
$apachePath = Join-Path $XamppPath 'apache'
$phpPath = Join-Path $XamppPath 'php'
$httpdPath = Join-Path $apachePath 'bin\httpd.exe'
$utf8 = New-Object System.Text.UTF8Encoding($false)

function Get-ProjectServer {
    if (!(Test-Path -LiteralPath $pidPath)) { return $null }

    $serverPid = 0
    if (![int]::TryParse((Get-Content -LiteralPath $pidPath -Raw).Trim(), [ref]$serverPid)) {
        throw "PID invalido em $pidPath."
    }

    $server = Get-CimInstance Win32_Process -Filter "ProcessId = $serverPid"
    if (!$server) { return $null }

    $commandLine = $server.CommandLine -replace '\\', '/'
    $expectedConfig = $configPath -replace '\\', '/'
    if ($server.ExecutablePath -ne $httpdPath -or !$commandLine.Contains($expectedConfig)) {
        throw 'O PID pertence a outro processo. Nenhum processo foi encerrado.'
    }

    return $server
}

$existingServer = Get-ProjectServer
if ($Action -eq 'stop') {
    if ($existingServer) {
        $children = @(Get-CimInstance Win32_Process -Filter "ParentProcessId = $($existingServer.ProcessId)" |
            Where-Object { $_.ExecutablePath -eq $httpdPath })
        Stop-Process -Id $existingServer.ProcessId
        $children | ForEach-Object { Stop-Process -Id $_.ProcessId -ErrorAction SilentlyContinue }
        Remove-Item -LiteralPath $pidPath -ErrorAction SilentlyContinue
    }
    Write-Output "Servidor deste projeto encerrado (porta $Port)."
    exit 0
}

if ($existingServer) {
    Write-Output "O projeto ja esta rodando em http://127.0.0.1:$Port"
    exit 0
}

if (Get-NetTCPConnection -State Listen -LocalPort $Port -ErrorAction SilentlyContinue) {
    throw "A porta $Port esta ocupada. Encerre o php artisan serve com Ctrl+C ou informe -Port outra_porta."
}

foreach ($requiredPath in @($httpdPath, "$phpPath\php.ini", "$phpPath\php8apache2_4.dll", "$phpPath\ext\php_opcache.dll")) {
    if (!(Test-Path -LiteralPath $requiredPath)) {
        throw "Arquivo do XAMPP nao encontrado: $requiredPath. Informe -XamppPath se necessario."
    }
}

New-Item -ItemType Directory -Path $runtimePath -Force | Out-Null
$apacheConfigPath = $apachePath -replace '\\', '/'
$phpConfigPath = $phpPath -replace '\\', '/'
$runtimeConfigPath = $runtimePath -replace '\\', '/'
$publicConfigPath = (Join-Path $projectPath 'public') -replace '\\', '/'

# Esta copia pertence ao projeto; o php.ini e o Apache do XAMPP continuam intactos.
$phpIni = Get-Content -LiteralPath "$phpPath\php.ini" -Raw
if ($phpIni -notmatch '(?im)^\s*zend_extension\s*=.*opcache') {
    $phpIni += "`r`n[PHP]`r`nzend_extension=`"$phpConfigPath/ext/php_opcache.dll`"`r`n"
}
$phpIni += @"

[PHP]
extension_dir="$phpConfigPath/ext"
[opcache]
opcache.enable=1
opcache.validate_timestamps=1
opcache.revalidate_freq=0
opcache.file_update_protection=0
opcache.cache_id=iec-local-$Port
"@
[System.IO.File]::WriteAllText((Join-Path $runtimePath 'php.ini'), $phpIni, $utf8)

$apacheConfig = @"
ServerRoot "$apacheConfigPath"
Listen 127.0.0.1:$Port
ServerName 127.0.0.1:$Port
PidFile "$runtimeConfigPath/httpd.pid"
DefaultRuntimeDir "$runtimeConfigPath"
ErrorLog "$runtimeConfigPath/error.log"
LogLevel warn
ServerTokens Prod
ServerSignature Off
ThreadsPerChild 32
MaxConnectionsPerChild 0
KeepAlive On
KeepAliveTimeout 2
Timeout 30

LoadModule authz_core_module modules/mod_authz_core.so
LoadModule authz_host_module modules/mod_authz_host.so
LoadModule dir_module modules/mod_dir.so
LoadModule mime_module modules/mod_mime.so
LoadModule rewrite_module modules/mod_rewrite.so
LoadFile "$phpConfigPath/php8ts.dll"
LoadFile "$phpConfigPath/libpq.dll"
LoadFile "$phpConfigPath/libsqlite3.dll"
LoadModule php_module "$phpConfigPath/php8apache2_4.dll"
PHPIniDir "$runtimeConfigPath"
TypesConfig "$apacheConfigPath/conf/mime.types"
DirectoryIndex index.php
DocumentRoot "$publicConfigPath"

<Directory />
    AllowOverride None
    Require all denied
</Directory>
<Directory "$publicConfigPath">
    Options -Indexes
    AllowOverride All
    Require local
</Directory>
<FilesMatch "\.php$">
    SetHandler application/x-httpd-php
</FilesMatch>
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
"@
[System.IO.File]::WriteAllText($configPath, $apacheConfig, $utf8)

& $httpdPath -t -f $configPath
if ($LASTEXITCODE -ne 0) { throw 'A configuracao do servidor local e invalida.' }

$serverProcess = Start-Process -FilePath $httpdPath -ArgumentList @('-f', ('"' + $configPath + '"')) `
    -WorkingDirectory "$apachePath\bin" -WindowStyle Hidden -PassThru `
    -RedirectStandardOutput (Join-Path $runtimePath 'stdout.log') `
    -RedirectStandardError (Join-Path $runtimePath 'stderr.log')

for ($attempt = 0; $attempt -lt 50; $attempt++) {
    if ($serverProcess.HasExited) {
        throw "O Apache nao iniciou. Consulte $runtimePath\error.log e stderr.log."
    }
    if (Get-NetTCPConnection -State Listen -LocalPort $Port -ErrorAction SilentlyContinue) {
        Write-Output "Projeto iniciado em http://127.0.0.1:$Port"
        if ($Port -eq 8000 -and $XamppPath -eq 'C:\xampp') {
            Write-Output 'Para encerrar: composer run local:stop'
        } else {
            Write-Output "Para encerrar: powershell -NoProfile -File scripts/local-server.ps1 stop -Port $Port -XamppPath `"$XamppPath`""
        }
        exit 0
    }
    Start-Sleep -Milliseconds 200
}

throw "O servidor ainda nao abriu a porta. Consulte $runtimePath\error.log."
