# 🚀 Quick Reference: Git & Environment Setup

## Current Status
- ✅ **Branch:** `development` (active)
- ✅ **Credentials:** Protected in `.env` (not in git)
- ✅ **Repository:** Clean and secure
- ✅ **Application:** Working normally

---

## 📍 Daily Workflow

### Make Changes
```powershell
# Work normally - .env loads automatically
# Edit any PHP files
# Test at: http://localhost/GameLend/
```

### Commit Changes
```powershell
# Stage all changes (excluding .env automatically)
git add .

# Commit with meaningful message
git commit -m "Add feature: description here"

# Push to development branch
git push
```

---

## 🔐 Environment Variables

### Your .env File Location
```
c:\xampp\htdocs\GameLend\.env
```

### What's Inside (DO NOT SHARE)
```env
SUPABASE_URL=https://your-project-ref.supabase.co
SUPABASE_ANON_KEY=eyJhbGc...
DB_HOST=aws-1-us-east-2.pooler.supabase.com
DB_PORT=6543
DB_USER=postgres.your-project-ref
DB_PASSWORD=GameLend
```

### How It's Used
- `includes/env_loader.php` reads `.env` automatically
- All config files use `getenv('VARIABLE_NAME')`
- No hardcoded credentials anywhere

---

## 🌿 Branch Commands

### Check Current Branch
```powershell
git branch --show-current
# Should show: development
```

### View All Branches
```powershell
git branch -a
# * development (current)
#   main
#   remotes/origin/development
#   remotes/origin/main
```

### Switch Branches
```powershell
# Switch to main
git checkout main

# Switch back to development
git checkout development
```

### Update Branch
```powershell
# Pull latest changes from GitHub
git pull origin development
```

---

## ✅ Safety Checks

### Before Every Push
```powershell
# 1. Check what's staged
git status

# 2. Verify .env is NOT listed
git status | findstr ".env"
# Should only show: .env.example

# 3. Confirm you're on development
git branch --show-current
# Should show: development

# 4. Push safely
git push
```

### Verify No Credentials in Code
```powershell
# Should find NO matches
findstr /s /i "your-project-ref" *.php
# (except in docs and comments)
```

---

## 🆘 Emergency Commands

### Accidentally Staged .env
```powershell
# Remove from staging (don't commit it!)
git reset HEAD .env
```

### Need to Undo Last Commit (Before Push)
```powershell
# Undo commit but keep changes
git reset --soft HEAD~1
```

### Accidentally on Main Branch
```powershell
# Move changes to development without committing
git stash
git checkout development
git stash pop
```

### Rotate Compromised Credentials
1. Go to https://supabase.com/dashboard
2. Settings > API > Reset keys
3. Update `.env` with new values
4. Restart your local server

---

## 📦 Files You Touch

### ✅ SAFE to Edit & Commit
- All `.php` files
- `.env.example` (template only)
- `.gitignore`
- `*.sql` (schema files)
- `*.md` (documentation)
- `*.css`, `*.js`

### ❌ NEVER Commit
- `.env` (actual credentials)
- `*.log` (log files)
- `.vscode/` (IDE settings)
- `*.backup`, `*.bak`

---

## 🔗 Useful Links

- **Repository:** https://github.com/joyieks/GameLend
- **New PR:** https://github.com/joyieks/GameLend/pull/new/development
- **Supabase Dashboard:** https://supabase.com/dashboard
- **Local App:** http://localhost/GameLend/

---

## 📖 Full Documentation

- `SECURITY_MIGRATION_COMPLETE.md` - Complete security overview
- `docs/SECURITY_SETUP.md` - Detailed security guide
- `BORROW_GAME_FIX_SUMMARY.md` - Feature fixes
- `.env.example` - Environment template

---

## 💡 Pro Tips

1. **Always work on development branch**
   ```powershell
   git checkout development
   ```

2. **Check status before committing**
   ```powershell
   git status
   ```

3. **Pull before starting work**
   ```powershell
   git pull origin development
   ```

4. **Descriptive commit messages**
   ```powershell
   git commit -m "Fix: description of what changed"
   ```

5. **Push regularly**
   ```powershell
   git push
   ```

---

**Remember:** `.env` file is LOCAL ONLY and protected by `.gitignore`!

**Status:** ✅ Ready for Development
