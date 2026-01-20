$files = Get-ChildItem -Path "C:\Users\lucas\Downloads\Chatbot-laravel" -Filter *.php -Recurse

$fixed = 0
foreach ($file in $files) {
    $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
    
    if ($bytes.Length -ge 3 -and $bytes[0] -eq 239 -and $bytes[1] -eq 187 -and $bytes[2] -eq 191) {
        Write-Host "Removendo BOM de: $($file.FullName)"
        
        $content = Get-Content $file.FullName -Raw
        $content = $content.TrimStart([char]0xFEFF)
        [System.IO.File]::WriteAllText($file.FullName, $content, [System.Text.UTF8Encoding]::new($false))
        
        $fixed++
    }
}

Write-Host "Concluido! $fixed arquivos corrigidos." -ForegroundColor Green
