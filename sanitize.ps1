# Simple credential sanitization script
Write-Host "Sanitizing documentation files..." -ForegroundColor Cyan

$files = @(
    "docs\SUPABASE_AUTH_GUIDE.md",
    "docs\SECURITY_SETUP.md",
    "SUPABASE_SETUP_FIX.md",
    "QUICK_REFERENCE.md",
    "SECURITY_MIGRATION_COMPLETE.md",
    "TROUBLESHOOTING_500_ERROR.md"
)

$pattern1 = "https://ecyncrgyvyepppgelczk.supabase.co"
$replace1 = "https://your-project-ref.supabase.co"

$pattern2 = "postgres.ecyncrgyvyepppgelczk"
$replace2 = "postgres.your-project-ref"

$pattern3 = "ecyncrgyvyepppgelczk"
$replace3 = "your-project-ref"

foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "Processing $file..."
        $content = Get-Content $file -Raw
        $content = $content -replace [regex]::Escape($pattern1), $replace1
        $content = $content -replace [regex]::Escape($pattern2), $replace2
        $content = $content -replace [regex]::Escape($pattern3), $replace3
        Set-Content -Path $file -Value $content -NoNewline
        Write-Host "  Done!" -ForegroundColor Green
    }
}

Write-Host "`nVerifying..." -ForegroundColor Yellow
$check = Select-String -Path $files -Pattern "ecyncrgyvyepppgelczk" -SimpleMatch
if ($check) {
    Write-Host "WARNING: Credentials still found!" -ForegroundColor Red
    $check
} else {
    Write-Host "SUCCESS: All credentials removed!" -ForegroundColor Green
}
