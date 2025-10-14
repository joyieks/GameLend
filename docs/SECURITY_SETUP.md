# 🔐 Security Setup Guide

## Important: Protecting Your Credentials

This guide explains how to properly manage sensitive credentials in the GameLend project.

## 🚨 Security Changes Made

### What Was Fixed
1. **Removed hardcoded credentials** from all PHP files
2. **Created `.env` file** for local credential storage
3. **Added `.gitignore`** to prevent committing sensitive files
4. **Created environment loader** to read credentials from `.env`
5. **Updated all config files** to use environment variables only

### Files Changed
- ✅ `includes/supabase_config.php` - No longer contains hardcoded keys
- ✅ `db/db_connect.php` - Reads credentials from environment only
- ✅ `includes/auth_check.php` - Dynamic Content Security Policy
- ✅ `test_supabase_auth.html` - Placeholder credentials only
- ✅ `.env` - Contains your actual credentials (NOT committed to git)
- ✅ `.env.example` - Template for others (safe to commit)

## 📋 Setup Instructions

### For Local Development

1. **The `.env` file already exists with your credentials**
   ```bash
   # Located at: c:\xampp\htdocs\GameLend\.env
   # This file is already configured and ready to use
   ```

2. **Verify your `.env` file contains:**
   ```env
   SUPABASE_URL=https://ecyncrgyvyepppgelczk.supabase.co
   SUPABASE_ANON_KEY=your-anon-key
   DB_HOST=aws-1-us-east-2.pooler.supabase.com
   DB_PORT=6543
   DB_NAME=postgres
   DB_USER=postgres.ecyncrgyvyepppgelczk
   DB_PASSWORD=GameLend
   ```

3. **Your application will automatically load these values**
   - The `env_loader.php` file handles this automatically
   - No additional configuration needed

### For New Team Members

1. **Copy the example file:**
   ```bash
   copy .env.example .env
   ```

2. **Fill in actual values in `.env`:**
   - Get Supabase credentials from project admin
   - Get database password from project admin
   - Never share these values in Slack, email, etc.

3. **Verify `.env` is in `.gitignore`:**
   ```bash
   # Should see ".env" listed in .gitignore
   type .gitignore
   ```

## 🔒 Security Best Practices

### DO ✅
- Keep credentials in `.env` file only
- Use `.env.example` as a template (no real values)
- Rotate credentials if accidentally exposed
- Use different credentials for dev/staging/production
- Add `.env` to `.gitignore` (already done)

### DON'T ❌
- Never commit `.env` to git
- Never hardcode credentials in PHP/HTML files
- Never share credentials in chat/email
- Never commit files with real API keys
- Never push to public repositories with credentials

## 🌿 Git Branch Strategy

### Current Branch: `development`
- This is your working branch
- Make all changes here
- Never push directly to `main`

### Workflow
```bash
# You're already on development branch
git status

# Stage your changes (but .env is ignored automatically)
git add .

# Commit your changes
git commit -m "Your commit message"

# Push to development branch (first time)
git push -u origin development

# Push subsequent changes
git push
```

### What Gets Committed
✅ **Safe to commit:**
- `.env.example` (template only)
- `.gitignore` (protects sensitive files)
- All PHP files (now use environment variables)
- Documentation files
- SQL schema files

❌ **Never committed (protected by .gitignore):**
- `.env` (your actual credentials)
- Log files
- Backup files
- IDE config files

## 🔑 Rotating Credentials (If Exposed)

If your credentials were accidentally exposed:

### 1. Rotate Supabase Keys
1. Go to [Supabase Dashboard](https://supabase.com/dashboard)
2. Select your project
3. Go to Settings > API
4. Click "Reset" on exposed keys
5. Update your `.env` file with new keys

### 2. Change Database Password
1. Go to Supabase Dashboard
2. Settings > Database
3. Click "Change Password"
4. Update `DB_PASSWORD` in `.env`

### 3. Notify Your Team
- Let others know to update their `.env` files
- Share new credentials securely (not in chat/email)

## 📂 File Structure

```
GameLend/
├── .env                          # ❌ Local credentials (NOT in git)
├── .env.example                  # ✅ Template (safe to commit)
├── .gitignore                    # ✅ Protects sensitive files
├── includes/
│   ├── env_loader.php           # ✅ Loads .env variables
│   ├── supabase_config.php      # ✅ Uses env variables only
│   └── auth_check.php           # ✅ Dynamic security headers
├── db/
│   └── db_connect.php           # ✅ Uses env variables only
└── docs/
    └── SECURITY_SETUP.md        # 📖 This file
```

## 🧪 Testing Your Setup

### Verify Environment Loading
```php
<?php
require_once 'includes/env_loader.php';
echo getenv('SUPABASE_URL'); // Should show your URL
?>
```

### Test Database Connection
```bash
# Navigate to your project
cd c:\xampp\htdocs\GameLend

# Test in browser
http://localhost/GameLend/health.php
```

### Check Git Status
```bash
git status

# Should NOT see .env in the list
# Should see .env.example as modified/new
```

## 🆘 Troubleshooting

### "Environment variables not loading"
- Ensure `.env` file exists in project root
- Check file has no BOM (save as UTF-8 without BOM)
- Verify `env_loader.php` is included in your scripts

### "Database connection failed"
- Check `DB_PASSWORD` in `.env` is correct
- Verify `DB_HOST` points to transaction pooler
- Ensure port is `6543` (not `5432`)

### ".env still showing in git"
- Run: `git rm --cached .env`
- Verify `.env` is in `.gitignore`
- Commit the removal: `git commit -m "Remove .env from tracking"`

## 📚 Additional Resources

- [12 Factor App - Config](https://12factor.net/config)
- [OWASP - Secrets Management](https://owasp.org/www-project-secrets-management/)
- [Supabase Security Best Practices](https://supabase.com/docs/guides/platform/going-into-prod)
- [Git - Removing Sensitive Data](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository)

## ✅ Quick Checklist

Before pushing to git:
- [ ] `.env` file is NOT staged (`git status` shouldn't show it)
- [ ] `.gitignore` includes `.env`
- [ ] No hardcoded credentials in PHP files
- [ ] `test_supabase_auth.html` has placeholder values only
- [ ] All changes are on `development` branch (not `main`)
- [ ] `.env.example` is up-to-date with new variables

## 📝 Questions?

If you need help with security setup:
1. Check this documentation first
2. Review the `.env.example` file
3. Test with `health.php` endpoint
4. Contact the project admin for credential issues

---

**Last Updated:** October 15, 2025
**Security Level:** ✅ Credentials Protected
