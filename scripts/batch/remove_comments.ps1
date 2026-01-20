$basePath = "C:\Users\lucas\Downloads\Chatbot-laravel"

function Remove-PhpComments {
    param([string]$FilePath)
    
    Write-Host "Processando: $FilePath"
    
    $content = Get-Content -Path $FilePath -Raw
    $lines = $content -split "`n"
    $result = @()
    $inBlockComment = $false
    
    foreach ($line in $lines) {
        if ($inBlockComment) {
            if ($line -match '\*/') {
                $inBlockComment = $false
                $line = $line -replace '^.*?\*/', ''
            } else {
                continue
            }
        }
        
        if ($line -match '/\*') {
            if ($line -match '/\*.*\*/') {
                $line = $line -replace '/\*.*?\*/', ''
            } else {
                $inBlockComment = $true
                $line = $line -replace '/\*.*$', ''
            }
        }
        
        $line = $line -replace '//.*$', ''
        
        if ($line -match '\S' -or $line -eq '') {
            $result += $line
        }
    }
    
    $newContent = ($result -join "`n") -replace '\n{3,}', "`n`n"
    Set-Content -Path $FilePath -Value $newContent -Encoding UTF8
    Write-Host "OK: $FilePath" -ForegroundColor Green
}

Get-ChildItem -Path "$basePath\app" -Filter *.php -Recurse | ForEach-Object { Remove-PhpComments $_.FullName }
Get-ChildItem -Path "$basePath\routes" -Filter *.php | ForEach-Object { Remove-PhpComments $_.FullName }
Get-ChildItem -Path "$basePath\config" -Filter *.php | ForEach-Object { Remove-PhpComments $_.FullName }

Write-Host "Concluido!" -ForegroundColor Cyan
