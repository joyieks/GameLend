# 🔧 Supabase Configuration Fix

## Problem
Getting `500 Internal Server Error` when trying to register:
```
POST https://ecyncrgyvyepppgelczk.supabase.co/auth/v1/signup?redirect_to=http%3A%2F%2Flocalhost%2FGameLend%2Fauth.php%3Fmode%3Dlogin 500 (Internal Server Error)
```

## Root Cause
- Site URL in Supabase is incomplete: `http://localhost/GameL`
- Redirect URLs not configured properly
- Email confirmation settings may not be enabled

---

## ✅ Solution: Configure Supabase Authentication

### Step 1: Fix Site URL

1. Go to: **Supabase Dashboard** → **Authentication** → **URL Configuration**
2. Update **Site URL** to:
   ```
   http://localhost/GameLend
   ```
   ⚠️ Make sure it's complete (not cut off at "GameL")
3. Click **"Save changes"**

### Step 2: Add Redirect URLs

In the **Redirect URLs** section, click **"Add URL"** and add these one by one:

```
http://localhost/GameLend/auth.php
http://localhost/GameLend/change_password.php
http://localhost/GameLend/login.php
http://localhost/GameLend/register.php
http://localhost/GameLend/**
```

The last one (`**` wildcard) allows any path under `/GameLend/`

### Step 3: Enable Email Confirmations

1. Go to: **Authentication** → **Settings** → **Email Auth**
2. Make sure these are enabled:
   - ✅ **Enable Email Signup**
   - ✅ **Confirm Email** (this sends verification emails)
   - ✅ **Secure Email Change** (optional but recommended)

### Step 4: Configure Email Templates

1. Go to: **Authentication** → **Email Templates**
2. Check the **"Confirm signup"** template:
   - Should be enabled
   - Should have `{{ .ConfirmationURL }}` in the template
   - Should redirect to your site

**Default template looks like:**
```html
<h2>Confirm your signup</h2>
<p>Follow this link to confirm your email:</p>
<p><a href="{{ .ConfirmationURL }}">Confirm your email</a></p>
```

### Step 5: Test Email Provider (Important!)

1. Go to: **Project Settings** → **Authentication** → **SMTP Settings**
2. Check if you're using:
   - **Supabase's built-in email** (limited to development, may not work in production)
   - **Custom SMTP** (recommended for production)

⚠️ **Note:** Supabase's default email may be blocked by Gmail/Outlook. For production, configure a custom SMTP provider (SendGrid, Mailgun, etc.)

---

## 🧪 Testing After Configuration

1. **Clear browser cache** (or use Incognito mode)
2. Go to: `http://localhost/GameLend/register.php`
3. Fill in the registration form:
   - First Name: Test
   - Last Name: User
   - Email: your-real-email@gmail.com
   - Password: test123456
4. Click **Register**
5. Check your email inbox (and spam folder!) for confirmation email
6. Click the confirmation link in the email
7. You should be redirected to login page

---

## 🔍 Troubleshooting

### Still getting 500 error?
- Wait 1-2 minutes after saving Supabase config (changes take time to propagate)
- Check browser console for exact error message
- Verify your Supabase project is not paused

### Email not arriving?
- Check spam/junk folder
- Verify email confirmation is enabled in Supabase
- Check Supabase logs: **Authentication** → **Logs**
- Try with a different email provider (Gmail, Outlook, etc.)

### Wrong redirect URL?
- Make sure Site URL exactly matches: `http://localhost/GameLend`
- Check that redirect URLs include the full path
- Use wildcards (`**`) for flexibility during development

---

## 📝 Production Deployment

When deploying to production, update:

1. **Site URL**: `https://yourdomain.com`
2. **Redirect URLs**: 
   ```
   https://yourdomain.com/auth.php
   https://yourdomain.com/change_password.php
   https://yourdomain.com/**
   ```
3. **Environment Variables** in hosting provider:
   ```
   SUPABASE_URL=https://ecyncrgyvyepppgelczk.supabase.co
   SUPABASE_ANON_KEY=eyJhbGc...
   ```

---

## 📧 Email Template Customization

You can customize the confirmation email template:

1. Go to: **Authentication** → **Email Templates** → **Confirm signup**
2. Edit the HTML template
3. Make sure to keep `{{ .ConfirmationURL }}` variable
4. Add your branding/styling

**Example custom template:**
```html
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
  <h2 style="color: #6c5ce7;">Welcome to GameLend! 🎮</h2>
  <p>Thanks for signing up! Please confirm your email address:</p>
  <a href="{{ .ConfirmationURL }}" 
     style="background: #6c5ce7; color: white; padding: 12px 24px; 
            text-decoration: none; border-radius: 8px; display: inline-block; 
            margin: 20px 0;">
    Confirm Email Address
  </a>
  <p style="color: #666; font-size: 14px;">
    If you didn't create an account, you can safely ignore this email.
  </p>
</div>
```

---

## ✅ Quick Checklist

Before testing registration:
- [ ] Site URL is complete: `http://localhost/GameLend`
- [ ] Redirect URLs added (including wildcard)
- [ ] Email confirmation is enabled
- [ ] Email template is configured
- [ ] Waited 1-2 minutes after saving changes
- [ ] Browser cache cleared

---

**Need help?** Check Supabase documentation: https://supabase.com/docs/guides/auth
