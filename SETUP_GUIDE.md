# GameLend - Setup and Run Guide

This guide will help you set up and run the GameLend project on your local machine.

## Prerequisites

Before you begin, ensure you have:

1. **XAMPP** installed (or any Apache + PHP server)
   - PHP 8.0 or higher
   - Apache web server
   - Download from: https://www.apachefriends.org/

2. **Supabase Account** (free tier works)
   - Sign up at: https://supabase.com
   - Create a new project

3. **Modern Web Browser** (Chrome, Firefox, Edge, etc.)

## Step-by-Step Setup

### Step 1: Start XAMPP

1. Open **XAMPP Control Panel**
2. Start **Apache** service
3. Verify Apache is running (should show green status)

### Step 2: Verify Project Location

Your project should be located at:
- **Windows**: `C:\xampp\htdocs\GameLend\`
- **macOS**: `/Applications/XAMPP/htdocs/GameLend/`
- **Linux**: `/opt/lampp/htdocs/GameLend/`

If it's not there, move or copy the project folder to the correct location.

### Step 3: Set Up Supabase Project

1. **Go to Supabase Dashboard**: https://supabase.com/dashboard
2. **Create a new project** (or use existing)
3. **Wait for project to be ready** (takes 1-2 minutes)

### Step 4: Set Up Database Schema

1. In Supabase Dashboard, go to **SQL Editor**
2. Click **New Query**
3. Open `db/schema.sql` from your project
4. **Copy the entire contents** of `schema.sql`
5. **Paste into Supabase SQL Editor**
6. Click **Run** (or press Ctrl+Enter)
7. Wait for success message

### Step 5: Configure Environment Variables

1. **Create a `.env` file** in the project root (`C:\xampp\htdocs\GameLend\.env`)

2. **Get your Supabase credentials**:
   - Go to Supabase Dashboard → **Settings** (gear icon) → **API**
   - Copy the following values:

3. **Add to `.env` file**:
```env
# Supabase Database Connection
DB_HOST=aws-1-us-east-2.pooler.supabase.com
DB_PORT=6543
DB_NAME=postgres
DB_USER=postgres.xxxxxxxxxxxxx
DB_PASSWORD=your-database-password

# Alternative: Use DATABASE_URL instead
# DATABASE_URL=postgres://postgres.xxxxxxxxxxxxx:your-password@aws-1-us-east-2.pooler.supabase.com:6543/postgres

# Supabase Auth (for authentication)
SUPABASE_URL=https://xxxxxxxxxxxxx.supabase.co
SUPABASE_ANON_KEY=your-anon-key-here
SUPABASE_SERVICE_ROLE_KEY=your-service-role-key-here
```

**Where to find these values:**
- **DB_HOST, DB_PORT, DB_NAME**: Go to **Settings** → **Database** → **Connection string** (use Transaction Pooler)
- **DB_USER**: Your database user (usually `postgres.xxxxxxxxxxxxx`)
- **DB_PASSWORD**: The password you set when creating the project
- **SUPABASE_URL**: Found in **Settings** → **API** → **Project URL**
- **SUPABASE_ANON_KEY**: Found in **Settings** → **API** → **anon public** key
- **SUPABASE_SERVICE_ROLE_KEY**: Found in **Settings** → **API** → **service_role** key (keep secret!)

### Step 6: Create Admin User

1. Go to Supabase Dashboard → **Authentication** → **Users**
2. Click **Add User** → **Create new user**
3. Enter:
   - **Email**: `admin@gamelend.com`
   - **Password**: Create a strong password (remember this!)
   - **Auto Confirm User**: ✅ Check this box (for testing)
4. Click **Create User**

5. **Set Admin Role** (in SQL Editor):
```sql
UPDATE users 
SET role = 'admin' 
WHERE email = 'admin@gamelend.com';
```

### Step 7: Configure Supabase Auth Settings

1. Go to **Authentication** → **URL Configuration**
2. Set **Site URL**: `http://localhost/GameLend`
   - ⚠️ **Important**: This is the base URL for your application
   - This is where users will be redirected after email verification and password resets
3. Add **Redirect URLs** (one per line):
   ```
   http://localhost/GameLend/change_password.php
   http://localhost/GameLend/auth_callback.php
   http://localhost/GameLend/login.php
   http://localhost/GameLend/customer/dashboard.php
   http://localhost/GameLend/admin/dashboard.php
   ```
   - ⚠️ **Important**: `change_password.php` must be included for password reset to work!

4. Go to **Authentication** → **Providers**
5. Ensure **Email** provider is enabled
6. (Optional) Configure email templates in **Authentication** → **Email Templates**

### Step 8: Test the Application

1. **Open your browser**
2. **Navigate to**: `http://localhost/GameLend/`
3. You should see the GameLend homepage

4. **Test Login**:
   - Click **Login** or go to `http://localhost/GameLend/auth.php`
   - Enter admin credentials:
     - Email: `admin@gamelend.com`
     - Password: (the password you set)
   - You should be redirected to the admin dashboard

5. **Test Registration**:
   - Go to `http://localhost/GameLend/auth.php`
   - Click **Register** tab
   - Fill in the form
   - Submit (you'll receive a verification email)
   - Click the verification link in the email
   - Login with your new account

## Quick Start Checklist

- [ ] XAMPP Apache is running
- [ ] Project is in `htdocs/GameLend/` folder
- [ ] Supabase project created
- [ ] Database schema imported (`db/schema.sql`)
- [ ] `.env` file created with correct credentials
- [ ] Admin user created in Supabase Auth
- [ ] Admin role set in database
- [ ] Supabase Auth URLs configured
- [ ] Can access `http://localhost/GameLend/`
- [ ] Can login as admin

## Troubleshooting

### Issue: "Database connection failed"
**Solutions:**
- Check `.env` file exists and has correct values
- Verify Supabase project is active
- Check DB_HOST, DB_PORT, DB_USER, DB_PASSWORD are correct
- Ensure you're using Transaction Pooler port (6543) not direct port (5432)

### Issue: "Page not found (404)"
**Solutions:**
- Verify project is in `C:\xampp\htdocs\GameLend\`
- Check Apache is running in XAMPP
- Try accessing `http://localhost/GameLend/index.php` directly

### Issue: "Authentication failed" or "User not found"
**Solutions:**
- Verify user exists in Supabase Auth (Authentication → Users)
- Check user exists in `users` table (SQL Editor: `SELECT * FROM users;`)
- Ensure user has `status = 'active'`
- Check Supabase Auth URLs are configured correctly

### Issue: "Permission denied" errors
**Solutions:**
- Check Row Level Security (RLS) policies are created (should be in schema.sql)
- Verify RLS context is being set (check `db/setup_rls_context.php`)
- Check user role is correct in database

### Issue: PHP errors
**Solutions:**
- Check PHP version: `php -v` (should be 8.0+)
- Enable error display in `php.ini`:
  ```ini
  display_errors = On
  error_reporting = E_ALL
  ```
- Check XAMPP error logs: `C:\xampp\apache\logs\error.log`

## Default Access

### Admin Account
- **Email**: `admin@gamelend.com`
- **Password**: (the password you set in Supabase Auth)
- **Access**: Admin dashboard with full system access

### Customer Account
- Register through the registration page
- Email verification required
- Access: Customer dashboard with borrowing features

## Next Steps

After setup is complete:

1. **Add Games**: Login as admin → Games → Add New Game
2. **Create Test Customer**: Register a new account
3. **Test Borrowing**: Login as customer → Browse games → Borrow a game
4. **Test Returns**: Return borrowed games
5. **View Reports**: Admin dashboard → Reports

## Development Tips

- **Enable PHP Error Display**: For debugging, enable errors in `php.ini`
- **Check Browser Console**: For JavaScript errors (F12 → Console)
- **Check Network Tab**: For API call issues (F12 → Network)
- **Supabase Logs**: Check Supabase Dashboard → Logs for database errors

## Production Deployment

For production deployment:
1. Use HTTPS (not HTTP)
2. Update `.env` with production Supabase credentials
3. Update Supabase Auth redirect URLs to production domain
4. Disable error display in PHP
5. Set up proper backup system
6. Configure custom email domain in Supabase

## Support

If you encounter issues:
1. Check the troubleshooting section above
2. Review Supabase Dashboard logs
3. Check XAMPP Apache error logs
4. Verify all environment variables are set correctly
5. Ensure database schema was imported successfully

---

**Happy Coding! 🎮**

