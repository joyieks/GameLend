# ⚠️ PRE-COMMIT SECURITY CHECKLIST

**DO NOT COMMIT TO GITHUB UNTIL ALL ITEMS ARE CHECKED!**

## 🔴 CRITICAL - Files with Real Credentials

### Files That MUST Be Fixed Before Commit:

1. **✅ `.env`** - Already in .gitignore, but verify it's not tracked
2. **❌ Documentation files** - Contains real Supabase project reference

## 📋 Security Audit

### Files Containing Real Credentials (Project Reference: ecyncrgyvyepppgelczk)

| File | Status | Action Required |
|------|--------|-----------------|
| `.env` | ✅ Gitignored | Verify not tracked in git |
| `docs/SUPABASE_AUTH_GUIDE.md` | ⚠️ Contains URL | Replace with placeholder |
| `docs/SECURITY_SETUP.md` | ⚠️ Contains URL | Replace with placeholder |
| `SUPABASE_SETUP_FIX.md` | ⚠️ Contains URL | Replace with placeholder |
| `QUICK_REFERENCE.md` | ⚠️ Contains URL | Replace with placeholder |
| `SECURITY_MIGRATION_COMPLETE.md` | ⚠️ Contains URL | Replace with placeholder |
| `TROUBLESHOOTING_500_ERROR.md` | ⚠️ Contains URL | Replace with placeholder |
| `.env.example` | ✅ Safe | Contains placeholders only |

## 🔧 Required Actions

### 1. Verify .env is Not Tracked

Run these commands in PowerShell:

```powershell
# Check if .env is tracked by git
git ls-files | Select-String ".env"

# If .env appears, remove it from tracking (keeps local file)
git rm --cached .env

# Verify .gitignore is working
git status
```

### 2. Remove Credentials from Documentation

Replace all instances of:
- `https://ecyncrgyvyepppgelczk.supabase.co` → `https://your-project-ref.supabase.co`
- `postgres.ecyncrgyvyepppgelczk` → `postgres.your-project-ref`

Files to sanitize:
- docs/SUPABASE_AUTH_GUIDE.md
- docs/SECURITY_SETUP.md
- SUPABASE_SETUP_FIX.md
- QUICK_REFERENCE.md
- SECURITY_MIGRATION_COMPLETE.md
- TROUBLESHOOTING_500_ERROR.md

### 3. Check for Exposed JWT Tokens

```powershell
# Search for JWT tokens (they look like: eyJhbGc...)
findstr /s /i "eyJhbGc" *.md *.php *.html
```

Expected results:
- ✅ `.env.example` with placeholder text
- ✅ Test files with "your-key-here"
- ❌ NO actual JWT tokens in committed files

### 4. Verify .gitignore is Complete

Ensure these patterns are in `.gitignore`:
```
.env
.env.local
.env.*.local
*.log
vendor/
node_modules/
sessions/
uploads/private/
```

## 🔍 Final Verification Commands

Run these in PowerShell before committing:

```powershell
# 1. Check what will be committed
git status

# 2. Verify .env is not in the list
git status | Select-String ".env"

# 3. Check for accidentally staged credentials
git diff --cached | Select-String "eyJhbGc"
git diff --cached | Select-String "ecyncrgyvyepppgelczk"

# 4. View what will be committed
git diff --cached --stat
```

## ✅ Safe to Commit When:

- [ ] `.env` is NOT in `git status` output
- [ ] `.env` is listed in `.gitignore`
- [ ] All documentation uses placeholders (your-project-ref.supabase.co)
- [ ] No JWT tokens (eyJhbGc...) in staged files
- [ ] No real database passwords in committed files
- [ ] `.env.example` has placeholder values only

## 🚀 After Cleanup - Commit Process

```powershell
# 1. Stage all files
git add .

# 2. Final security check
git diff --cached | Select-String -Pattern "eyJhbGc|ecyncrgyvyepppgelczk|GameLend.*password"

# 3. If clean, commit
git commit -m "Initial commit: GameLend project with Supabase integration"

# 4. Add remote (replace with your GitHub repo URL)
git remote add origin https://github.com/yourusername/GameLend.git

# 5. Push to GitHub
git push -u origin main
```

## 🔐 Additional Security Measures

### For Production (Render):
- ✅ All credentials stored as environment variables
- ✅ render.yaml uses `generateValue: true` for secrets
- ✅ Session cookies secured with HTTPS
- ✅ .htaccess prevents directory listing
- ✅ Database uses SSL connections

### For Supabase:
- [ ] Enable RLS (Row Level Security) on all tables
- [ ] Configure proper policies for users, games, transactions
- [ ] Add production URL to redirect allowlist
- [ ] Enable email confirmation
- [ ] Set up proper CORS policies

## ⚠️ If Credentials Were Already Committed

If you already pushed credentials to GitHub:

1. **Rotate ALL credentials immediately:**
   - Generate new Supabase API keys
   - Change database password
   - Update service role key

2. **Clean Git history:**
   ```powershell
   # Remove sensitive file from all commits
   git filter-branch --force --index-filter "git rm --cached --ignore-unmatch .env" --prune-empty --tag-name-filter cat -- --all
   
   # Force push (WARNING: This rewrites history)
   git push origin --force --all
   ```

3. **Notify team:** If this is a team project, inform all collaborators

## 📞 Need Help?

- **Supabase Docs:** https://supabase.com/docs
- **Git Security:** https://docs.github.com/en/authentication/keeping-your-account-and-data-secure
- **Render Docs:** https://render.com/docs

---

**Last Updated:** ${new Date().toISOString()}  
**Status:** ⚠️ CREDENTIALS FOUND - DO NOT COMMIT YET
