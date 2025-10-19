# Final Security Check Before Commit
Write-Host "================================" -ForegroundColor Cyan
Write-Host "FINAL SECURITY CHECK" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

$allClear = $true

# Check 1
Write-Host "1. Checking if .env is tracked..." -ForegroundColor Yellow
$envTracked = git ls-files | Select-String "^\.env$"
if ($envTracked) {
    Write-Host "   FAIL: .env is tracked!" -ForegroundColor Red
    $allClear = $false
} else {
    Write-Host "   PASS" -ForegroundColor Green
}

# Check 2
Write-Host "2. Checking .gitignore..." -ForegroundColor Yellow
$gitignoreContent = Get-Content .gitignore -Raw
if ($gitignoreContent -match "\.env") {
    Write-Host "   PASS" -ForegroundColor Green
} else {
    Write-Host "   FAIL: .env not in .gitignore!" -ForegroundColor Red
    $allClear = $false
}

# Check 3
Write-Host "3. Checking for project references..." -ForegroundColor Yellow
$credentialSearch = Get-ChildItem -Path "*.md", "docs\*.md" -Recurse -ErrorAction SilentlyContinue | 
    Where-Object { $_.Name -ne "PRE_COMMIT_SECURITY_CHECKLIST.md" } |
    Select-String -Pattern "ecyncrgyvyepppgelczk" -SimpleMatch -ErrorAction SilentlyContinue
if ($credentialSearch) {
    Write-Host "   FAIL: Found real project reference!" -ForegroundColor Red
    $credentialSearch | ForEach-Object { Write-Host "     $_" -ForegroundColor Red }
    $allClear = $false
} else {
    Write-Host "   PASS" -ForegroundColor Green
}

# Check 4
Write-Host "4. Checking .env.example..." -ForegroundColor Yellow
$envExample = Get-Content .env.example -Raw
if ($envExample -match "ecyncrgyvyepppgelczk") {
    Write-Host "   FAIL: .env.example contains real credentials!" -ForegroundColor Red
    $allClear = $false
} else {
    Write-Host "   PASS" -ForegroundColor Green
}

Write-Host ""
Write-Host "================================" -ForegroundColor Cyan
if ($allClear) {
    Write-Host "ALL CHECKS PASSED!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Safe to commit to GitHub!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "  git add ." -ForegroundColor White
    Write-Host "  git commit -m 'Initial commit'" -ForegroundColor White
    Write-Host "  git push origin development" -ForegroundColor White
    exit 0
} else {
    Write-Host "SECURITY ISSUES FOUND!" -ForegroundColor Red
    Write-Host "DO NOT COMMIT!" -ForegroundColor Red
    exit 1
}
