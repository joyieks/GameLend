# sanitize_docs.ps1
# PowerShell script to remove real credentials from documentation files

Write-Host "🔧 Sanitizing Documentation Files..." -ForegroundColor Cyan
Write-Host ""

# Define the replacements
$replacements = @(
    @{ Old = "https://ecyncrgyvyepppgelczk.supabase.co"; New = "https://your-project-ref.supabase.co" },
    @{ Old = "postgres.ecyncrgyvyepppgelczk"; New = "postgres.your-project-ref" },
    @{ Old = "ecyncrgyvyepppgelczk"; New = "your-project-ref" }
)

# Files to sanitize
$files = @(
    "docs\SUPABASE_AUTH_GUIDE.md",
    "docs\SECURITY_SETUP.md",
    "SUPABASE_SETUP_FIX.md",
    "QUICK_REFERENCE.md",
    "SECURITY_MIGRATION_COMPLETE.md",
    "TROUBLESHOOTING_500_ERROR.md"
)

$totalReplacements = 0

foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "Processing: $file" -ForegroundColor Yellow
        
        $content = Get-Content $file -Raw
        $fileReplacements = 0
        
        foreach ($replacement in $replacements) {
            $oldValue = $replacement.Old
            $newValue = $replacement.New
            $matches = ([regex]::Matches($content, [regex]::Escape($oldValue))).Count
            
            if ($matches -gt 0) {
                $content = $content -replace [regex]::Escape($oldValue), $newValue
                $fileReplacements += $matches
                Write-Host "  ✓ Replaced $matches occurrence(s) of: $oldValue" -ForegroundColor Green
            }
        }
        
        if ($fileReplacements -gt 0) {
            Set-Content -Path $file -Value $content -NoNewline
            $totalReplacements += $fileReplacements
            Write-Host "  📝 Saved: $fileReplacements replacements made" -ForegroundColor Cyan
        } else {
            Write-Host "  ℹ No replacements needed" -ForegroundColor Gray
        }
        
        Write-Host ""
    } else {
        Write-Host "⚠ File not found: $file" -ForegroundColor Red
        Write-Host ""
    }
}

Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host "✅ Sanitization Complete!" -ForegroundColor Green
Write-Host "Total replacements made: $totalReplacements" -ForegroundColor Cyan
Write-Host ""

# Verify no credentials remain
Write-Host "🔍 Verifying no credentials remain..." -ForegroundColor Yellow
Write-Host ""

$credentialPatterns = @(
    "ecyncrgyvyepppgelczk",
    "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9\.eyJpc3M"
)

$foundIssues = $false

foreach ($pattern in $credentialPatterns) {
    Write-Host "Checking for: $pattern" -ForegroundColor Gray
    
    $matches = Get-ChildItem -Path "." -Include "*.md" -Recurse | 
               Select-String -Pattern $pattern -SimpleMatch
    
    if ($matches) {
        Write-Host "⚠ FOUND IN:" -ForegroundColor Red
        foreach ($match in $matches) {
            Write-Host "  $($match.Path):$($match.LineNumber)" -ForegroundColor Red
        }
        $foundIssues = $true
    } else {
        Write-Host "  ✓ Clear" -ForegroundColor Green
    }
    Write-Host ""
}

if ($foundIssues) {
    Write-Host "❌ CREDENTIALS STILL FOUND! Review files above." -ForegroundColor Red
    exit 1
} else {
    Write-Host "✅ No credentials found in documentation!" -ForegroundColor Green
    Write-Host ""
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
    Write-Host "🚀 SAFE TO COMMIT TO GITHUB!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "  1. git add ." -ForegroundColor White
    Write-Host "  2. git commit -m 'Initial commit: GameLend project'" -ForegroundColor White
    Write-Host "  3. git push origin development" -ForegroundColor White
    Write-Host ""
}
