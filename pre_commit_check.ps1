# Final Security Check Before Commit
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host "🔒 FINAL SECURITY CHECK BEFORE COMMIT" -ForegroundColor Cyan
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host ""

$allClear = $true

# Check 1: Verify .env is not tracked
Write-Host "1️⃣ Checking if .env is tracked..." -ForegroundColor Yellow
$envTracked = git ls-files | Select-String "^\.env$"
if ($envTracked) {
    Write-Host "   ❌ FAIL: .env is tracked in git!" -ForegroundColor Red
    Write-Host "   Run: git rm --cached .env" -ForegroundColor Yellow
    $allClear = $false
} else {
    Write-Host "   ✅ PASS: .env is not tracked" -ForegroundColor Green
}
Write-Host ""

# Check 2: Verify .env is in .gitignore
Write-Host "2️⃣ Checking .gitignore..." -ForegroundColor Yellow
$gitignoreContent = Get-Content .gitignore -Raw
if ($gitignoreContent -match "^\.env") {
    Write-Host "   ✅ PASS: .env is in .gitignore" -ForegroundColor Green
} else {
    Write-Host "   ❌ FAIL: .env not in .gitignore!" -ForegroundColor Red
    $allClear = $false
}
Write-Host ""

# Check 3: Search for exposed credentials in staged files
Write-Host "3️⃣ Checking for credentials in documentation..." -ForegroundColor Yellow
$credentialSearch = Select-String -Path "*.md","docs\*.md" -Pattern "ecyncrgyvyepppgelczk" -SimpleMatch
if ($credentialSearch) {
    Write-Host "   ❌ FAIL: Found real project reference!" -ForegroundColor Red
    $credentialSearch | ForEach-Object { Write-Host "      $_" -ForegroundColor Red }
    $allClear = $false
} else {
    Write-Host "   ✅ PASS: No project references found" -ForegroundColor Green
}
Write-Host ""

# Check 4: Search for JWT tokens
Write-Host "4️⃣ Checking for JWT tokens..." -ForegroundColor Yellow
$jwtSearch = Select-String -Path "*.md","*.php","*.html" -Pattern "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9\.eyJpc3M" -SimpleMatch | Where-Object { $_.Path -notmatch "\.env$" }
if ($jwtSearch) {
    Write-Host "   ⚠️  WARNING: Found JWT-like tokens!" -ForegroundColor Yellow
    $jwtSearch | ForEach-Object { 
        if ($_.Path -notmatch "\.env\.example|test_supabase") {
            Write-Host "      $_" -ForegroundColor Red
            $allClear = $false
        }
    }
} else {
    Write-Host "   ✅ PASS: No JWT tokens in committed files" -ForegroundColor Green
}
Write-Host ""

# Check 5: Verify .env.example has placeholders only
Write-Host "5️⃣ Checking .env.example..." -ForegroundColor Yellow
$envExample = Get-Content .env.example -Raw
if ($envExample -match "ecyncrgyvyepppgelczk" -or $envExample -match "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9\.eyJpc3M") {
    Write-Host "   ❌ FAIL: .env.example contains real credentials!" -ForegroundColor Red
    $allClear = $false
} else {
    Write-Host "   ✅ PASS: .env.example has placeholders only" -ForegroundColor Green
}
Write-Host ""

# Check 6: Check for hardcoded passwords
Write-Host "6️⃣ Checking for hardcoded passwords..." -ForegroundColor Yellow
$passwordSearch = Select-String -Path "*.php" -Pattern "password.*=.*['\"](?!.*\$|.*getenv)" | Where-Object { $_.Path -notmatch "\\vendor\\|node_modules|\.env" }
if ($passwordSearch) {
    Write-Host "   ⚠️  WARNING: Possible hardcoded passwords found" -ForegroundColor Yellow
    Write-Host "   Review these manually:" -ForegroundColor Yellow
    $passwordSearch | Select-Object -First 5 | ForEach-Object { Write-Host "      $($_.Path):$($_.LineNumber)" -ForegroundColor Yellow }
} else {
    Write-Host "   ✅ PASS: No obvious hardcoded passwords" -ForegroundColor Green
}
Write-Host ""

# Final verdict
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
if ($allClear) {
    Write-Host "✅ ALL CHECKS PASSED!" -ForegroundColor Green
    Write-Host ""
    Write-Host "🚀 SAFE TO COMMIT TO GITHUB!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "  git add ." -ForegroundColor White
    Write-Host "  git status  # Review what will be committed" -ForegroundColor White
    Write-Host "  git commit -m 'feat: Initial GameLend deployment with Supabase integration'" -ForegroundColor White
    Write-Host "  git push origin development" -ForegroundColor White
    Write-Host ""
    Write-Host "📋 Remember to:" -ForegroundColor Yellow
    Write-Host "  - Set environment variables in Render dashboard" -ForegroundColor White
    Write-Host "  - Update Supabase redirect URLs for production" -ForegroundColor White
    Write-Host "  - Enable RLS policies in Supabase" -ForegroundColor White
    Write-Host ""
    exit 0
} else {
    Write-Host "❌ SECURITY ISSUES FOUND!" -ForegroundColor Red
    Write-Host ""
    Write-Host "⚠️  DO NOT COMMIT UNTIL ISSUES ARE RESOLVED!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Review the failures above and fix them before committing." -ForegroundColor Yellow
    Write-Host ""
    exit 1
}
