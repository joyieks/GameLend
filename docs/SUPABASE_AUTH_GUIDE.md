# Supabase Auth Integration Guide

## Overview
GameLend now uses **Supabase Auth** for secure user authentication instead of storing password hashes. This is more secure and provides features like:
- ✅ Email verification
- ✅ Password reset via email
- ✅ Secure JWT tokens
- ✅ OAuth providers (Google, GitHub, etc.) - optional
- ✅ Magic links - optional

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     User Registration Flow                   │
└─────────────────────────────────────────────────────────────┘

1. User fills registration form → 
2. Supabase Auth creates user →
3. Verification email sent →
4. User clicks link →
5. Trigger creates record in public.users table →
6. User can login

┌─────────────────────────────────────────────────────────────┐
│                        Login Flow                            │
└─────────────────────────────────────────────────────────────┘

1. User enters email/password →
2. Supabase Auth verifies →
3. JWT token returned →
4. Token stored in cookie/session →
5. Backend verifies token →
6. User data loaded from public.users →
7. Session established
```

## Setup Instructions

### Step 1: Get Supabase Credentials

1. Go to your Supabase project dashboard
2. Click on **Settings** (gear icon) → **API**
3. Copy these values:
   - **Project URL**: `https://xxxxx.supabase.co`
   - **anon/public key**: Used for client-side auth

### Step 2: Configure Environment Variables

Create a `.env` file in your project root (or set in your hosting environment):

```env
SUPABASE_URL=https://ecyncrgyvyepppgelczk.supabase.co
SUPABASE_ANON_KEY=your-anon-key-here
```

Or update `includes/supabase_auth.php` directly (not recommended for production).

### Step 3: Run Database Migration

Run the migration script to update your database:

1. Go to Supabase Dashboard → **SQL Editor**
2. Run `db/migration_update_user_names.sql`

This will:
- Add `auth_id` column
- Remove `password_hash` column
- Add trigger to auto-create users
- Update name fields

### Step 4: Enable Email Auth in Supabase

1. Go to **Authentication** → **Providers**
2. Make sure **Email** is enabled
3. Configure email templates:
   - Go to **Authentication** → **Email Templates**
   - Customize:
     - **Confirm Signup** template
     - **Reset Password** template
     - **Magic Link** (optional)

### Step 5: Create Admin User

1. Go to **Authentication** → **Users**
2. Click **Add User** → **Create new user**
3. Enter:
   - Email: `admin@gamelend.com`
   - Password: Create a strong password
   - **User Metadata**: Add custom fields:
     ```json
     {
       "first_name": "Admin",
       "last_name": "User",
       "role": "admin"
     }
     ```
4. After creation, verify in SQL Editor:
   ```sql
   SELECT * FROM users WHERE email = 'admin@gamelend.com';
   UPDATE users SET role = 'admin' WHERE email = 'admin@gamelend.com';
   ```

## Implementation

### Frontend (auth.php)

The `auth.php` page uses Supabase JavaScript client for authentication:

```javascript
// Initialize Supabase client
const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

// Sign up
const { data, error } = await supabase.auth.signUp({
  email: 'user@example.com',
  password: 'password123',
  options: {
    data: {
      first_name: 'John',
      middle_name: 'M',
      last_name: 'Doe',
      phone: '+1234567890'
    }
  }
});

// Sign in
const { data, error } = await supabase.auth.signInWithPassword({
  email: 'user@example.com',
  password: 'password123'
});

// Sign out
await supabase.auth.signOut();
```

### Backend (PHP)

Protected pages use `includes/supabase_auth.php`:

```php
<?php
session_start();
require_once '../db/db_connect.php';
require_once '../includes/supabase_auth.php';

// Initialize auth session
initializeAuthSession($pdo);

// Require authentication
requireAuth();

// Require admin role
requireAdmin();
?>
```

## User Registration Flow

1. **User visits `auth.php` and fills registration form**
2. **JavaScript calls Supabase Auth signUp:**
   ```javascript
   const { data, error } = await supabase.auth.signUp({
     email: email,
     password: password,
     options: {
       data: {
         first_name: firstName,
         middle_name: middleName,
         last_name: lastName,
         phone: phone
       }
     }
   });
   ```

3. **Supabase sends verification email** with link like:
   ```
   https://ecyncrgyvyepppgelczk.supabase.co/auth/v1/verify
     ?token=xxxxx
     &type=signup
     &redirect_to=https://yourapp.com/welcome
   ```

4. **User clicks verification link**

5. **Database trigger automatically creates user record:**
   ```sql
   -- Trigger inserts into public.users when auth.users gets a new record
   INSERT INTO public.users (auth_id, email, first_name, last_name, role, status)
   VALUES (NEW.id, NEW.email, ..., 'customer', 'active');
   ```

6. **User can now login**

## Login Flow

1. **User enters email/password on `auth.php`**

2. **JavaScript calls Supabase Auth signIn:**
   ```javascript
   const { data, error } = await supabase.auth.signInWithPassword({
     email: email,
     password: password
   });
   ```

3. **Supabase returns JWT token** and session

4. **JavaScript stores token in cookie** for backend access

5. **Page redirects to dashboard**

6. **Backend (PHP) verifies token:**
   ```php
   $authUser = verifySupabaseToken($token);
   $appUser = getOrCreateAppUser($pdo, $authUser);
   $_SESSION['user_id'] = $appUser['id'];
   $_SESSION['role'] = $appUser['role'];
   ```

## Password Reset Flow

1. **User clicks "Forgot Password" on login page**

2. **JavaScript calls:**
   ```javascript
   await supabase.auth.resetPasswordForEmail(email, {
     redirectTo: 'https://yourapp.com/reset-password'
   });
   ```

3. **Supabase sends password reset email**

4. **User clicks link in email** → redirected to reset page

5. **User enters new password:**
   ```javascript
   await supabase.auth.updateUser({
     password: newPassword
   });
   ```

## Security Benefits

### ✅ No Password Storage
- Passwords never stored in your database
- Supabase handles hashing and security

### ✅ JWT Token Authentication
- Tokens expire automatically
- Can be revoked server-side
- Signed and verified cryptographically

### ✅ Built-in Protection
- Rate limiting on auth endpoints
- CAPTCHA support
- Email verification
- Account lockout after failed attempts

### ✅ Separation of Concerns
- Authentication (Supabase Auth)
- Authorization (Your app database)
- User data (Your app database)

## Customization

### Email Templates

Customize in Supabase Dashboard → **Authentication** → **Email Templates**:

1. **Confirm Signup**
   ```html
   <h2>Welcome to GameLend!</h2>
   <p>Click the link below to verify your email:</p>
   <a href="{{ .ConfirmationURL }}">Verify Email</a>
   ```

2. **Reset Password**
   ```html
   <h2>Reset Your Password</h2>
   <p>Click the link below to reset your password:</p>
   <a href="{{ .ConfirmationURL }}">Reset Password</a>
   ```

### Redirect URLs

Configure in Supabase Dashboard → **Authentication** → **URL Configuration**:

- **Site URL**: `http://localhost/GameLend` (development)
- **Redirect URLs**: Add allowed redirect URLs after authentication

## Troubleshooting

### Issue: "User not found" after registration
**Solution**: Check if trigger is created:
```sql
SELECT * FROM pg_trigger WHERE tgname = 'on_auth_user_created';
```

### Issue: Token verification fails
**Solution**: 
1. Check `SUPABASE_URL` and `SUPABASE_ANON_KEY` are correct
2. Ensure cookies are enabled
3. Check if token is being set properly

### Issue: Users not getting emails
**Solution**:
1. Check Supabase Dashboard → **Authentication** → **Email**
2. Verify SMTP settings if using custom email
3. Check spam folder
4. Enable email confirmations in Auth settings

### Issue: "Access denied" after login
**Solution**: Check user status and role:
```sql
SELECT id, email, role, status FROM users WHERE email = 'user@example.com';
UPDATE users SET status = 'active' WHERE email = 'user@example.com';
```

## Migration from Old System

If you have existing users with password_hash:

1. **Export user emails:**
   ```sql
   SELECT email, first_name, last_name FROM users;
   ```

2. **Ask users to register again** via Supabase Auth

3. **Or bulk import** using Supabase API (requires service role key)

4. **Users will need to verify email** and set new password

## Testing

### Test Registration:
1. Go to `http://localhost/GameLend/auth.php`
2. Fill in registration form
3. Check email for verification link
4. Click link to verify
5. Login with credentials

### Test Login:
1. Enter email/password
2. Should redirect to dashboard
3. Check session is established:
   ```php
   var_dump($_SESSION);
   ```

### Test Password Reset:
1. Click "Forgot Password"
2. Enter email
3. Check email for reset link
4. Set new password
5. Login with new password

## Production Deployment

1. **Update environment variables** with production values
2. **Configure allowed redirect URLs** in Supabase
3. **Set up custom email domain** (optional)
4. **Enable rate limiting**
5. **Monitor Auth logs** in Supabase Dashboard

## Additional Resources

- [Supabase Auth Documentation](https://supabase.com/docs/guides/auth)
- [Supabase JavaScript Client](https://supabase.com/docs/reference/javascript/auth-signup)
- [JWT Token Guide](https://jwt.io/)
