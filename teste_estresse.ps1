# TESTE DE ESTRESSE DO CHATBOT
param(
    [int]$NumUsuarios = 5,
    [int]$MensagensPerUsuario = 3,
    [int]$ParalelizacaoDados = 10
)

$baseUrl = "http://127.0.0.1:8000/api/webhook/whatsapp/messages-upsert"
$logPath = "c:\Users\lucas\Downloads\Chatbot-laravel\storage\logs\laravel.log"
$resultsFile = "c:\Users\lucas\Downloads\Chatbot-laravel\storage\logs\teste_estresse_resultado.txt"

"=== TESTE DE ESTRESSE DO CHATBOT ===" | Out-File -FilePath $resultsFile -Encoding UTF8 -Force
"Data/Hora: $(Get-Date)" | Add-Content -Path $resultsFile
"Usuarios: $NumUsuarios" | Add-Content -Path $resultsFile
"Mensagens por usuario: $MensagensPerUsuario" | Add-Content -Path $resultsFile
"Paralelizacao: $ParalelizacaoDados" | Add-Content -Path $resultsFile
"`n" | Add-Content -Path $resultsFile

Write-Host "INICIANDO TESTE DE ESTRESSE" -ForegroundColor Green
Write-Host "Usuarios: $NumUsuarios | Mensagens cada: $MensagensPerUsuario`n" -ForegroundColor Cyan

$mensagens = @(
    "Oi, tudo bem?",
    "Quero comprar um imovel",
    "Alugar seria melhor",
    "Perdizes",
    "Ate 500 mil",
    "3 quartos",
    "Sim, autorizo",
    "Concordo",
    "Me mostra as opcoes",
    "Qual valor?",
    "Agendar visita",
    "Segunda-feira",
    "14:00",
    "Confirmo",
    "Posso falar com um corretor?",
    "Mais informacoes",
    "Obrigado!"
)

$nomes = @(
    "Joao", "Maria", "Carlos", "Ana", "Pedro", "Julia", "Lucas", "Fernanda", 
    "Roberto", "Camila", "Gustavo", "Beatriz", "Felipe", "Diana", "Marcelo"
)

$sucessos = 0
$falhas = 0

Write-Host "FASE 1: Teste com $NumUsuarios usuarios" -ForegroundColor Yellow

$tasks = @()

for ($u = 1; $u -le $NumUsuarios; $u++) {
    $usuarioId = 55000000 + $u * 100 + (Get-Random -Minimum 0 -Maximum 100)
    $nome = $nomes[(Get-Random -Minimum 0 -Maximum $nomes.Length)]
    
    for ($m = 1; $m -le $MensagensPerUsuario; $m++) {
        $mensagem = $mensagens[(Get-Random -Minimum 0 -Maximum $mensagens.Length)]
        $messageId = "STRESS_U${u}_M${m}_" + (Get-Random)
        
        $body = @{
            instance = "N8n"
            data = @{
                key = @{
                    remoteJid = "${usuarioId}@s.whatsapp.net"
                    id = $messageId
                    fromMe = $false
                }
                pushName = $nome
                message = @{
                    conversation = $mensagem
                }
            }
        } | ConvertTo-Json -Depth 10

        $tasks += @{
            Usuario = $u
            Nome = $nome
            Mensagem = $m
            Conteudo = $mensagem
            Body = $body
        }
    }
}

Write-Host "Enviando $($tasks.Count) requisicoes..." -ForegroundColor Cyan

$lotes = [Math]::Ceiling($tasks.Count / $ParalelizacaoDados)

for ($lote = 0; $lote -lt $lotes; $lote++) {
    $inicio = $lote * $ParalelizacaoDados
    $fim = [Math]::Min(($lote + 1) * $ParalelizacaoDados, $tasks.Count)
    $tarefasLote = $tasks[$inicio..($fim-1)]

    $jobs = $tarefasLote | ForEach-Object {
        $task = $_
        Start-Job -ScriptBlock {
            param($url, $body)
            try {
                $resp = Invoke-WebRequest -Uri $url -Method POST -Headers @{"Content-Type"="application/json"} -Body $body -TimeoutSec 10 -UseBasicParsing -ErrorAction Stop
                return @{ Status = $resp.StatusCode; Sucesso = $true }
            } catch {
                return @{ Status = $_.Exception.Message; Sucesso = $false; Erro = $_.Exception.Message }
            }
        } -ArgumentList $baseUrl, $task.Body
    }

    $resultados = $jobs | Wait-Job | Receive-Job
    $jobs | Remove-Job

    foreach ($i in 0..($resultados.Count - 1)) {
        $resultado = $resultados[$i]
        $task = $tarefasLote[$i]
        
        if ($resultado.Sucesso -or $resultado.Status -eq 202) {
            $sucessos++
            Write-Host "OK U$($task.Usuario)-M$($task.Mensagem)" -ForegroundColor Green
        } else {
            $falhas++
            Write-Host "ERRO U$($task.Usuario)-M$($task.Mensagem)" -ForegroundColor Red
        }
    }

    Write-Host "  Lote $($lote + 1)/$lotes concluido" -ForegroundColor Cyan
    Start-Sleep -Milliseconds 500
}

Write-Host "`nAguardando processamento (20 segundos)..." -ForegroundColor Cyan
Start-Sleep -Seconds 20

Write-Host "`nANALISANDO LOGS..." -ForegroundColor Yellow

$logContent = Get-Content -Path $logPath -Tail 500 -Raw
$erros = ($logContent -split [System.Environment]::NewLine | Where-Object { $_ -match '\[ERROR\]|\[WARNING\]' }).Count
$infos = ($logContent -split [System.Environment]::NewLine | Where-Object { $_ -match '\[INFO\]' }).Count

Write-Host "`nRESULTADOS:" -ForegroundColor Green
Write-Host "  Sucessos: $sucessos" -ForegroundColor Green
Write-Host "  Falhas: $falhas" -ForegroundColor Red
$taxa = if (($sucessos + $falhas) -gt 0) { [Math]::Round(($sucessos / ($sucessos + $falhas)) * 100, 2) } else { 0 }
Write-Host "  Taxa: $taxa%" -ForegroundColor Cyan
Write-Host "`nLOGS:" -ForegroundColor Green
Write-Host "  INFO: $infos" -ForegroundColor Cyan
Write-Host "  WARNINGS/ERRORS: $erros" -ForegroundColor Yellow

"`n=== RESULTADOS FINAIS ===" | Add-Content -Path $resultsFile
"Sucessos: $sucessos" | Add-Content -Path $resultsFile
"Falhas: $falhas" | Add-Content -Path $resultsFile
"Taxa de sucesso: $taxa%" | Add-Content -Path $resultsFile

Write-Host "`nTESTE CONCLUIDO" -ForegroundColor Green
Write-Host "Resultado em: $resultsFile" -ForegroundColor Cyan
