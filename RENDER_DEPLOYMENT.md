# Render Deployment Guide for GameLend

## Prerequisites
- Supabase account with PostgreSQL database
- Render account (free tier works)
- GitHub repository with your code

## Step 1: Prepare Your Repository

1. **Push your code to GitHub:**
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   git branch -M main
   git remote add origin https://github.com/yourusername/gamelend.git
   git push -u origin main
   ```

2. **Ensure these files are in your repo:**
   - ✅ `Dockerfile` (already exists)
   - ✅ `render.yaml` (already exists, updated)
   - ✅ `.env.example` (template for environment variables)
   - ✅ `health.php` (for health checks)

## Step 2: Set Up Supabase Database

1. **Go to Supabase Dashboard** (https://supabase.com/dashboard)

2. **Get your connection details:**
   - Navigate to: Settings > Database > Connection String
   - Copy the **Transaction Pooler** connection string (port 6543)
   - Format: `postgresql://postgres.[project-ref]:[password]@aws-0-[region].pooler.supabase.com:6543/postgres`

3. **Run the schema SQL:**
   - Go to SQL Editor in Supabase
   - Copy contents of `db/schema.sql`
   - Execute to create all tables

4. **Insert default settings:**
   - Copy contents of `db/add_system_settings.sql`
   - Execute to create settings table with defaults

5. **Disable the game availability trigger:**
   - Copy contents of `db/disable_trigger.sql`
   - Execute to prevent double updates

## Step 3: Deploy to Render

### Option A: Deploy via Dashboard (Recommended)

1. **Go to Render Dashboard** (https://dashboard.render.com/)

2. **Click "New +" → "Web Service"**

3. **Connect your GitHub repository**
   - Select `GameLend` repository
   - Click "Connect"

4. **Configure the service:**
   - **Name:** `gamelend` (or your preferred name)
   - **Environment:** `Docker`
   - **Region:** Choose closest to you
   - **Branch:** `main` or `development`
   - **Dockerfile Path:** `./Dockerfile` (default)
   - **Instance Type:** `Starter` (free tier)

5. **Add Environment Variables:**
   Click "Advanced" → "Add Environment Variable" for each:

   ```
   SUPABASE_URL=https://your-project.supabase.co
   SUPABASE_ANON_KEY=your-anon-key-from-supabase
   SUPABASE_SERVICE_ROLE_KEY=your-service-role-key
   
   DB_HOST=aws-0-region.pooler.supabase.com
   DB_PORT=6543
   DB_NAME=postgres
   DB_USER=postgres.your-project-ref
   DB_PASSWORD=your-database-password
   
   APP_ENV=production
   APP_URL=https://your-app-name.onrender.com
   SESSION_SECURE=true
   SESSION_SAMESITE=Strict
   ```

   **Where to find these values:**
   - Supabase Dashboard > Settings > API:
     - `SUPABASE_URL` = Project URL
     - `SUPABASE_ANON_KEY` = anon/public key
     - `SUPABASE_SERVICE_ROLE_KEY` = service_role key
   
   - Supabase Dashboard > Settings > Database > Connection String:
     - Use **Transaction Pooler** (port 6543)
     - `DB_HOST` = hostname from connection string
     - `DB_USER` = postgres.xxxxxxxxxxxxx
     - `DB_PASSWORD` = your password

6. **Click "Create Web Service"**
   - Render will build your Docker image
   - Initial build takes 3-5 minutes
   - Watch the logs for any errors

### Option B: Deploy via render.yaml (Blueprint)

1. **In Render Dashboard**, click "New +" → "Blueprint"

2. **Connect your GitHub repository**

3. **Render will detect `render.yaml`** automatically

4. **Add environment variables** as listed above

5. **Click "Apply"**

## Step 4: Configure Supabase Auth Redirects

1. **Go to Supabase Dashboard** > Authentication > URL Configuration

2. **Add your Render URL to allowed URLs:**
   - **Site URL:** `https://your-app-name.onrender.com`
   - **Redirect URLs:** 
     ```
     https://your-app-name.onrender.com/login.php
     https://your-app-name.onrender.com/customer/dashboard.php
     https://your-app-name.onrender.com/admin/dashboard.php
     ```

3. **Save changes**

## Step 5: Test Your Deployment

1. **Visit your Render URL:** `https://your-app-name.onrender.com`

2. **Check health endpoint:** `https://your-app-name.onrender.com/health.php`
   - Should return: `{"status":"healthy","timestamp":"...","database":"connected"}`

3. **Test registration:**
   - Go to `/register.php`
   - Create a new account
   - Check email for verification link

4. **Test login:**
   - Use the verified account to log in
   - Verify dashboard loads correctly

5. **Test core features:**
   - Browse games
   - Borrow a game
   - Return a game
   - Admin panel (if admin user)

## Step 6: Create Admin User

**Option 1: Via Admin Creation Script**
```bash
# SSH into Render (if available) or use SQL Editor in Supabase
# Run: php admin/create_admin.php
```

**Option 2: Via Supabase SQL Editor**
```sql
-- First, register a user via the app
-- Then update their role:
UPDATE users 
SET role = 'admin' 
WHERE email = 'your-email@example.com';
```

## Troubleshooting

### Build Fails
- **Check Dockerfile syntax:** Ensure no syntax errors
- **Check logs:** Look at Render build logs for specific errors
- **Verify dependencies:** Ensure all PHP extensions are installed

### Database Connection Issues
- **Verify credentials:** Double-check all DB_* environment variables
- **Use Transaction Pooler:** Port 6543, not 5432
- **Check Supabase status:** Ensure database is running

### Authentication Issues
- **Check Supabase URLs:** Ensure redirect URLs are added
- **Verify SUPABASE_URL:** Must match your project URL exactly
- **Check CORS:** Supabase should allow your Render domain

### 500 Errors
- **Check Render logs:** Go to Logs tab in Render dashboard
- **Enable error display temporarily:**
  ```php
  // In health.php or index.php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
- **Check file permissions:** Ensure web server can read files

### Session Issues
- **Verify SESSION_SECURE=true** in production
- **Check cookies:** Must use HTTPS for secure cookies
- **Clear browser cache:** Force-refresh after deployment

## Maintenance

### Update Code
```bash
git add .
git commit -m "Your update message"
git push origin main
```
Render will auto-deploy if you enabled auto-deploy.

### Manual Deploy
- Go to Render Dashboard
- Click your service
- Click "Manual Deploy" → "Deploy latest commit"

### View Logs
- Render Dashboard > Your Service > Logs
- Real-time logs show all PHP errors and warnings

### Database Changes
- Use Supabase SQL Editor
- Or connect via `psql` using Session Pooler (port 5432)

## Performance Optimization

### Enable PHP OpCache
Already configured in Dockerfile:
```dockerfile
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
```

### Database Indexing
All necessary indexes already created in `schema.sql`:
- User email, auth_id, role
- Game title, platform, status
- Transaction user_id, game_id, status

### Caching
Consider adding:
- Redis for session storage (upgrade needed)
- CDN for static assets (Cloudflare free tier)

## Security Checklist

- ✅ Environment variables stored securely in Render
- ✅ SESSION_SECURE=true in production
- ✅ HTTPS enforced by Render
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection headers
- ✅ CSRF token validation
- ✅ Password hashing via Supabase Auth
- ✅ Row Level Security on Supabase (optional)

## Costs

**Free Tier Limits:**
- Render: 750 hours/month (enough for 1 app)
- Supabase: 500MB database, 2GB bandwidth
- Your app will spin down after 15 min of inactivity
- First request after spin-down takes 30-60 seconds

**Upgrade Options:**
- Render Starter: $7/month (no spin down)
- Supabase Pro: $25/month (8GB database)

## Support

**Issues?**
- Check Render logs: Dashboard > Logs
- Check Supabase logs: Dashboard > Logs
- Review error messages in browser console
- Test locally first with production environment variables

**Need Help?**
- Render Docs: https://render.com/docs
- Supabase Docs: https://supabase.com/docs
- PHP Docs: https://www.php.net/docs.php

---

## Quick Reference

### Environment Variables
```env
SUPABASE_URL=https://xxx.supabase.co
SUPABASE_ANON_KEY=eyJhbGc...
SUPABASE_SERVICE_ROLE_KEY=eyJhbGc...
DB_HOST=aws-0-us-west-1.pooler.supabase.com
DB_PORT=6543
DB_NAME=postgres
DB_USER=postgres.xxx
DB_PASSWORD=your-password
APP_ENV=production
APP_URL=https://your-app.onrender.com
SESSION_SECURE=true
SESSION_SAMESITE=Strict
```

### Useful Commands
```bash
# Deploy
git push origin main

# View logs
# (Use Render dashboard)

# Database migrations
# (Use Supabase SQL Editor)
```

### URLs to Bookmark
- **Your App:** https://your-app-name.onrender.com
- **Render Dashboard:** https://dashboard.render.com
- **Supabase Dashboard:** https://supabase.com/dashboard
- **Health Check:** https://your-app-name.onrender.com/health.php

---

**Deployment Date:** $(date)  
**Version:** 1.0  
**Status:** ✅ Production Ready
