param(
    [string]$ProjectPath = "C:\Users\lucas\Downloads\Chatbot-laravel",
    [int]$IntervalSeconds = 10
)

$ErrorActionPreference = 'SilentlyContinue'

$logsDir = Join-Path $ProjectPath 'storage\logs'
if (-not (Test-Path $logsDir)) {
    New-Item -ItemType Directory -Path $logsDir | Out-Null
}

$watchdogLog = Join-Path $logsDir 'chatbot-watchdog.log'
$lockFile = Join-Path $logsDir 'chatbot-watchdog.lock'

function LogLine([string]$msg) {
    $line = "[{0}] {1}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $msg
    Add-Content -Path $watchdogLog -Value $line
}

function AcquireLock {
    try {
        if (Test-Path $lockFile) {
            $age = (Get-Item $lockFile).LastWriteTime
            # lock antigo (1h) => assume morto
            if ((New-TimeSpan -Start $age -End (Get-Date)).TotalMinutes -lt 60) {
                return $false
            }
        }
        Set-Content -Path $lockFile -Value $PID -Encoding ASCII
        return $true
    } catch {
        return $false
    }
}

function Test-PortListening([int]$port) {
    try {
        $conns = Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction Stop
        return ($conns.Count -gt 0)
    } catch {
        $netstat = & netstat -ano | Select-String ":$port\s+LISTENING" -ErrorAction SilentlyContinue
        return ($null -ne $netstat)
    }
}

if (-not (AcquireLock)) {
    LogLine "Outro watchdog já está rodando. Saindo."
    exit 0
}

LogLine "Watchdog iniciado (PID=$PID) interval=${IntervalSeconds}s ProjectPath=$ProjectPath"

$runner = Join-Path $ProjectPath 'scripts\windows\run_chatbot.ps1'
if (-not (Test-Path $runner)) {
    LogLine "ERRO: runner não encontrado em $runner"
    exit 2
}

while ($true) {
    try {
        $serverOk = Test-PortListening 8000
        if (-not $serverOk) {
            LogLine "Porta 8000 não está LISTEN. Chamando runner para subir servidor/worker."
            powershell.exe -NoProfile -ExecutionPolicy Bypass -File $runner -ProjectPath $ProjectPath | Out-Null
        }

        # Mesmo com porta OK, o worker pode ter caído. O runner checa PID e sobe se precisar.
        powershell.exe -NoProfile -ExecutionPolicy Bypass -File $runner -ProjectPath $ProjectPath | Out-Null

        # Atualiza lock (mostra que está vivo)
        Set-Content -Path $lockFile -Value $PID -Encoding ASCII
    } catch {
        LogLine "Erro no loop do watchdog: $($_.Exception.Message)"
    }

    Start-Sleep -Seconds $IntervalSeconds
}
