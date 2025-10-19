# Supabase Configuration for Email Confirmations

## Quick Fix Required

Go to your Supabase Dashboard and update these settings:

### 1. Site URL Configuration
**Path:** Project Settings > Authentication > Site URL

Set to: `http://localhost/GameLend`

### 2. Redirect URLs
**Path:** Project Settings > Authentication > Redirect URLs

Add these URLs:
- `http://localhost/GameLend/login.php`
- `http://localhost/GameLend/**` (wildcard for all localhost paths)

### 3. Email Templates (Optional - improve user experience)
**Path:** Authentication > Email Templates

**Confirm signup template:**
Update the redirect URL in the template to:
```
{{ .ConfirmationURL }}
```

Make sure it doesn't have `?redirect_to=` parameter that might override your emailRedirectTo setting.

## How It Works Now

1. **User registers** (via register.php or admin adds user)
2. **Email sent** with confirmation link pointing to `http://localhost/GameLend/login.php`
3. **User clicks link** → Redirected to login.php with token in URL hash
4. **login.php JavaScript** automatically:
   - Checks for errors (expired, invalid)
   - Shows appropriate message
   - If valid, extracts session and logs user in
   - Redirects to appropriate dashboard

## Testing

1. Register a new user or add via admin
2. Check email for confirmation link
3. Click link → Should go to login.php
4. Should see "Email confirmed successfully!" message
5. Either auto-login or manually enter password

## Troubleshooting

**Still getting admin dashboard redirect?**
- Clear browser cache
- Check Supabase dashboard redirect URLs
- Make sure Site URL is set correctly

**Link expired immediately?**
- Links are valid for 24 hours by default
- Don't click old links, request new confirmation email

**No email received?**
- Check spam folder
- Verify email template is enabled in Supabase
- Check Supabase logs for delivery status
