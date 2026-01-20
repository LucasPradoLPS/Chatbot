$DBPath = "c:\Users\lucas\Downloads\Chatbot-laravel\database\database.sqlite"
$CorrectAssistantId = "asst_TK2zcCJXJE7reRvMIY0Vw4im"

Write-Host "Checking current database state..." -ForegroundColor Cyan

# Test if sqlite3 exists
$sqlite3 = (Get-Command sqlite3 -ErrorAction SilentlyContinue)

if ($sqlite3) {
    Write-Host "Using sqlite3 command line tool" -ForegroundColor Green
    
    # Show current state
    Write-Host "`nCurrent agents in database:" -ForegroundColor Yellow
    & sqlite3 $DBPath "SELECT id, numero_cliente, funcao, agente_base_id FROM agente_gerados LIMIT 20;"
    
    # Update incorrect assistant IDs
    Write-Host "`nUpdating assistant IDs..." -ForegroundColor Yellow
    & sqlite3 $DBPath "UPDATE agente_gerados SET agente_base_id = '$CorrectAssistantId' WHERE agente_base_id = '2' OR agente_base_id IS NULL OR agente_base_id = '';"
    
    # Verify update
    Write-Host "`nVerifying update:" -ForegroundColor Green
    & sqlite3 $DBPath "SELECT id, numero_cliente, funcao, agente_base_id FROM agente_gerados LIMIT 20;"
    
} else {
    Write-Host "sqlite3 not found in PATH" -ForegroundColor Red
    Write-Host "Trying alternative: Use PHP to connect to database" -ForegroundColor Yellow
}
