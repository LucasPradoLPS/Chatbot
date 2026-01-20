cd "c:\Users\lucas\Downloads\Chatbot-laravel"

Write-Host "════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "IMPLEMENTACAO: Verificacao de Horario de Atendimento" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════" -ForegroundColor Cyan

Write-Host "`n📋 DETALHES DA IMPLEMENTACAO:" -ForegroundColor Yellow

Write-Host "`n1. LOGICA ADICIONADA:" -ForegroundColor Green
Write-Host "   - Verifica se eh fim de semana (sabado ou domingo)" -ForegroundColor Green
Write-Host "   - Verifica se esta fora do horario (antes de 08h ou depois de 17h)" -ForegroundColor Green
Write-Host "   - Usa timezone de Sao Paulo (America/Sao_Paulo)" -ForegroundColor Green

Write-Host "`n2. QUANDO FORA DO HORARIO:" -ForegroundColor Green
Write-Host "   - Envia mensagem automatica ao cliente" -ForegroundColor Green
Write-Host "   - Nao ativa a IA" -ForegroundColor Green
Write-Host "   - Registra no log para monitoramento" -ForegroundColor Green

Write-Host "`n3. MENSAGEM ENVIADA:" -ForegroundColor Green
Write-Host "   ⏰ Horário de Atendimento" -ForegroundColor Green
Write-Host "   Nosso horário de atendimento é:" -ForegroundColor Green
Write-Host "   🕗 Segunda a sexta-feira, das 08h às 17h." -ForegroundColor Green
Write-Host "   Ficaremos felizes em te atender dentro desse horário 😊" -ForegroundColor Green

Write-Host "`n4. HORARIO ATUAL DO SISTEMA:" -ForegroundColor Yellow
$agora = (Get-Date)
Write-Host "   Data: $($agora.ToString('dd/MM/yyyy'))" -ForegroundColor Yellow
Write-Host "   Hora: $($agora.ToString('HH:mm:ss'))" -ForegroundColor Yellow
Write-Host "   Dia: $($agora.ToString('dddd'))" -ForegroundColor Yellow

$dia_semana = [int][System.DayOfWeek]::Monday # 0=segunda em .NET
$hora = $agora.Hour

if ($hora -ge 8 -and $hora -lt 17 -and $agora.DayOfWeek -gt 0 -and $agora.DayOfWeek -lt 6) {
    Write-Host "   Status: DENTRO DO HORARIO ✅" -ForegroundColor Green
} else {
    Write-Host "   Status: FORA DO HORARIO ❌" -ForegroundColor Red
}

Write-Host "`n5. ARQUIVO MODIFICADO:" -ForegroundColor Cyan
Write-Host "   app/Jobs/ProcessWhatsappMessage.php (linhas ~125-175)" -ForegroundColor Cyan

Write-Host "`n6. ARQUIVOS DE TESTE CRIADOS:" -ForegroundColor Cyan
Write-Host "   - testar_logica_horario.php" -ForegroundColor Cyan
Write-Host "   - testar_horario_atendimento.ps1" -ForegroundColor Cyan

Write-Host "`n════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host "IMPLEMENTACAO CONCLUIDA COM SUCESSO" -ForegroundColor Green
Write-Host "════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host ""
