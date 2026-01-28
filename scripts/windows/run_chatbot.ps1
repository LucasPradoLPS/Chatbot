param(
    [string]$ProjectPath = "C:\Users\lucas\Downloads\Chatbot-laravel"
)

$ErrorActionPreference = 'Stop'

$logsDir = Join-Path $ProjectPath 'storage\logs'
if (-not (Test-Path $logsDir)) {
    New-Item -ItemType Directory -Path $logsDir | Out-Null
}

$runnerLog = Join-Path $logsDir 'chatbot-runner.log'
$serverOut = Join-Path $logsDir 'artisan-serve.out.log'
$serverErr = Join-Path $logsDir 'artisan-serve.err.log'
$workerOut = Join-Path $logsDir 'queue-worker.out.log'
$workerErr = Join-Path $logsDir 'queue-worker.err.log'
$pidFile = Join-Path $logsDir 'chatbot-processes.json'

function Write-RunnerLog([string]$msg) {
    $line = "[{0}] {1}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $msg
    Add-Content -Path $runnerLog -Value $line
}

function Get-PhpPath {
    try {
        $cmd = Get-Command php -ErrorAction Stop
        return $cmd.Source
    } catch {
        return $null
    }
}

function Test-PortListening([int]$port) {
    try {
        $conns = Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction Stop
        return ($conns.Count -gt 0)
    } catch {
        # Fallback (Get-NetTCPConnection pode falhar em alguns ambientes)
        $netstat = & netstat -ano | Select-String ":$port\s+LISTENING" -ErrorAction SilentlyContinue
        return ($null -ne $netstat)
    }
}

function Read-Pids {
    if (-not (Test-Path $pidFile)) { return $null }
    try {
        return Get-Content $pidFile -Raw | ConvertFrom-Json
    } catch {
        return $null
    }
}

function Pid-IsRunning([int]$processId) {
    if ($processId -le 0) { return $false }
    try {
        $p = Get-Process -Id $processId -ErrorAction Stop
        return ($null -ne $p)
    } catch {
        return $false
    }
}

function Find-QueueWorkerProcessId {
    try {
        $procs = Get-CimInstance Win32_Process -Filter "Name='php.exe'" -ErrorAction Stop |
            Where-Object { $_.CommandLine -match 'artisan\s+queue:work' }
        if ($procs) {
            return [int]$procs[0].ProcessId
        }
        return 0
    } catch {
        return 0
    }
}

$php = Get-PhpPath
if (-not $php) {
    Write-RunnerLog "ERRO: 'php' não encontrado no PATH. Instale PHP ou ajuste PATH do Windows."
    exit 2
}

Write-RunnerLog "Iniciando runner com PHP='$php' ProjectPath='$ProjectPath'"

# Estado atual (server/worker)
$existing = Read-Pids
$port8000Listening = Test-PortListening 8000
$serverOk = $false
$workerOk = $false

if ($port8000Listening) {
    $serverOk = $true
} elseif ($existing -and (Pid-IsRunning([int]$existing.serverPid))) {
    $serverOk = $true
}

$workerPidDetected = 0
if ($existing -and (Pid-IsRunning([int]$existing.workerPid))) {
    $workerOk = $true
    $workerPidDetected = [int]$existing.workerPid
} else {
    $found = Find-QueueWorkerProcessId
    if ($found -gt 0) {
        $workerOk = $true
        $workerPidDetected = $found
    }
}

if ($serverOk -and $workerOk) {
    $serverPidInfo = if ($existing) { [int]$existing.serverPid } else { 0 }
    Write-RunnerLog "Já existe server+worker rodando (serverOk=$serverOk workerOk=$workerOk serverPid=$serverPidInfo workerPid=$workerPidDetected). Saindo."
    exit 0
}

# Server (porta 8000)
if ($serverOk) {
    Write-RunnerLog "Servidor OK (porta 8000 em LISTEN ou PID existente). Não vou iniciar 'artisan serve'."
    $serverPid = if ($existing) { [int]$existing.serverPid } else { 0 }
} else {
    Write-RunnerLog "Iniciando 'artisan serve' na porta 8000."
    $serverProc = Start-Process -FilePath $php -WorkingDirectory $ProjectPath -WindowStyle Hidden -PassThru -ArgumentList @(
        '-d','max_execution_time=0',
        'artisan','serve',
        '--host=0.0.0.0','--port=8000'
    ) -RedirectStandardOutput $serverOut -RedirectStandardError $serverErr
    $serverPid = $serverProc.Id
    Write-RunnerLog "artisan serve PID=$serverPid"
}

# Worker
if ($workerOk) {
    Write-RunnerLog "Worker OK (PID detectado=$workerPidDetected). Não vou iniciar outro 'queue:work'."
    $workerPid = $workerPidDetected
} else {
    Write-RunnerLog "Iniciando 'queue:work' (default,handoff) com auto-limites."
    $workerProc = Start-Process -FilePath $php -WorkingDirectory $ProjectPath -WindowStyle Hidden -PassThru -ArgumentList @(
        '-d','max_execution_time=0',
        'artisan','queue:work',
        '--queue=default,handoff',
        '--tries=3','--timeout=300','--sleep=1',
        '--memory=256','--max-jobs=500','--max-time=3600'
    ) -RedirectStandardOutput $workerOut -RedirectStandardError $workerErr
    $workerPid = $workerProc.Id
    Write-RunnerLog "queue:work PID=$workerPid"
}

@{
    startedAt = (Get-Date).ToString('o')
    serverPid = $serverPid
    workerPid = $workerPid
    projectPath = $ProjectPath
    php = $php
} | ConvertTo-Json | Set-Content -Path $pidFile -Encoding UTF8

Write-RunnerLog "Runner finalizado com sucesso (processos iniciados)."
exit 0
