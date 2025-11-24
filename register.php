<?php
require_once 'includes/session_config.php';
$page_title = "Register";

$supabaseUrl = getenv('SUPABASE_URL') ?: '';
$supabaseAnonKey = getenv('SUPABASE_ANON_KEY') ?: '';
if (($supabaseUrl === '' || $supabaseAnonKey === '') && file_exists(__DIR__ . '/includes/supabase_config.php')) {
    $cfg = include __DIR__ . '/includes/supabase_config.php';
    if (is_array($cfg)) {
        $supabaseUrl = $supabaseUrl ?: ($cfg['SUPABASE_URL'] ?? '');
        $supabaseAnonKey = $supabaseAnonKey ?: ($cfg['SUPABASE_ANON_KEY'] ?? '');
    }
}

include 'includes/header.php';
?>

<style>
    :root {
        --primary: #6c5ce7;
        --primary-dark: #5649c9;
        --secondary: #00cec9;
        --accent: #fd79a8;
        --dark: #2d3436;
        --light: #f5f6fa;
        --success: #00b894;
        --warning: #fdcb6e;
        --danger: #d63031;
        --gray: #dfe6e9;
    }
    body {
        background: linear-gradient(rgba(17, 24, 39, 0.65), rgba(17, 24, 39, 0.65)), url('assets/img/background2.png') center/cover no-repeat;
        background-attachment: fixed;
        min-height: 100vh;
    }
    
    /* Ensure header and footer blend with background */
    .navbar {
        background: rgba(44, 62, 80, 0.75) !important;
        backdrop-filter: blur(20px) saturate(180%) !important;
        -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    
    .footer {
        background: rgba(44, 62, 80, 0.75) !important;
        backdrop-filter: blur(20px) saturate(180%) !important;
        -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
        box-shadow: 0 -4px 30px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1) !important;
        border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    
    .footer p {
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
    }
    
    .login-container { /* reuse login layout for visual parity */
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }
    .login-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        width: 100%;
        max-width: 600px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .login-card:hover { transform: translateY(-5px); box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15); }
    .card-header { background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 1.5rem; text-align: center; position: relative; }
    .card-header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--secondary), var(--accent)); }
    .card-title { margin: 0; font-size: 1.5rem; font-weight: 800; color: white !important; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3); }
    .card-body { padding: 1.5rem; }
    /* Enhanced Alert Styles */
    .alert {
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        border: none;
        font-size: 0.95rem;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        animation: slideDown 0.3s ease-out;
        position: relative;
        overflow: hidden;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .alert-danger {
        background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);
        color: #c53030;
        border-left: 4px solid #e53e3e;
    }
    
    .alert-danger i {
        color: #e53e3e;
        margin-right: 8px;
        font-size: 1.1rem;
        vertical-align: middle;
    }
    
    .alert-success {
        background: linear-gradient(135deg, #f0fff4 0%, #e6ffec 100%);
        color: #276749;
        border-left: 4px solid #38a169;
    }
    
    .alert-success i {
        color: #38a169;
        margin-right: 8px;
        font-size: 1.1rem;
        vertical-align: middle;
    }
    .form-group { margin-bottom: 1rem; }
    .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--dark); }
    .form-control { width: 100%; padding: 0.8rem; font-size: 1rem; border: 2px solid #e9ecef; border-radius: 10px; transition: all 0.3s ease; background: #f8f9fa; }
    .form-control:focus { outline: none; border-color: var(--primary); background: white; box-shadow: 0 0 0 3px rgba(108, 92, 238, 0.1); }
    .password-container { position: relative; }
    .password-container input { padding-right: 3rem; }
    .toggle-password { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6c757d; font-size: 1.2rem; padding: 0.5rem; transition: color 0.3s ease; }
    .toggle-password:hover { color: var(--primary); }
    .btn { display: block; width: 100%; padding: 0.8rem; border-radius: 10px; border: none; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(108, 92, 231, 0.4); }
    @media (max-width: 768px) {
        .login-container { padding: 1rem; min-height: 90vh; }
        .login-card { margin: 1rem 0; }
        .card-body { padding: 1.5rem; }
        .form-control { font-size: 16px; padding: 0.875rem; }
        .btn { padding: 0.875rem 1.25rem; min-height: 44px; }
    }
    @media (max-width: 576px) {
        .login-container { padding: 0.5rem; }
        .card-body { padding: 1rem; }
        .card-title { font-size: 1.25rem; }
    }
    @media (max-width: 480px) {
        .login-card { border-radius: 15px; }
        .card-title { font-size: 1.1rem; }
    }
</style>

<div class="login-container">
    <div class="login-card">
        <div class="card-header">
            <h2 class="card-title">Create Account</h2>
        </div>
        <div class="card-body">
            <div class="alert alert-danger" id="regError" style="display:none;"></div>
            <div class="alert alert-success" id="regSuccess" style="display:none;"></div>

            <form id="registerForm">
                <div class="form-group">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="middle_name" class="form-label">Middle Name (Optional)</label>
                    <input type="text" id="middle_name" name="middle_name" class="form-control">
                </div>
                <div class="form-group">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="+1234567890">
                </div>
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" class="form-control" required minlength="6">
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small style="color: #6c757d;">Minimum 6 characters</small>
                </div>
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="password-container">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </form>

            <div class="register-link" style="text-align:center; margin-top:1rem; color:#6c757d;">
                Already have an account? <a href="login.php" style="color: var(--primary); font-weight:600;">Login here</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script>
const SUPABASE_URL = <?php echo json_encode($supabaseUrl); ?>;
const SUPABASE_ANON_KEY = <?php echo json_encode($supabaseAnonKey); ?>;
const regError = document.getElementById('regError');
const regSuccess = document.getElementById('regSuccess');
function showError(msg){ 
  regError.style.display='block'; 
  regError.innerHTML='<i class="fas fa-exclamation-circle"></i> ' + msg;
  regSuccess.style.display='none';
}
function showSuccess(msg){ 
  regSuccess.style.display='block'; 
  regSuccess.innerHTML='<i class="fas fa-check-circle"></i> ' + msg;
  regError.style.display='none';
}
function clearAlerts(){ 
  regError.style.display='none'; 
  regSuccess.style.display='none'; 
}

if (!SUPABASE_URL || !SUPABASE_ANON_KEY) {
  showError('System configuration error. Please contact the administrator.');
}

const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

document.getElementById('registerForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  clearAlerts();

  const first_name = document.getElementById('first_name').value.trim();
  const middle_name = document.getElementById('middle_name').value.trim();
  const last_name = document.getElementById('last_name').value.trim();
  const phone = document.getElementById('phone').value.trim();
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const confirm_password = document.getElementById('confirm_password').value;

  // Validation with clear messages
  if (!first_name || !last_name || !email || !password) {
    return showError('Please fill in all required fields (First Name, Last Name, Email, and Password).');
  }
  
  // Email format validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    return showError('Please enter a valid email address (e.g., user@example.com).');
  }

  if (password.length < 6) {
    return showError('Password must be at least 6 characters long for security purposes.');
  }

  if (password !== confirm_password) {
    return showError('Passwords do not match. Please make sure both passwords are identical.');
  }

  // Build redirect URL for email confirmation
  const redirectTo = window.location.origin + '/login.php';

  // Use Supabase signUp with email confirmation
  const { data, error } = await supabase.auth.signUp({
    email: email,
    password: password,
    options: {
      emailRedirectTo: redirectTo,
      data: {
        first_name: first_name,
        middle_name: middle_name,
        last_name: last_name,
        phone: phone,
        role: 'customer'
      }
    }
  });

  if (error) {
    // Provide clear, specific error messages
    if (error.message.includes('already registered') || error.message.includes('already been registered')) {
      return showError('This email address is already registered. Please use a different email or try logging in.');
    } else if (error.message.includes('invalid email')) {
      return showError('The email address format is invalid. Please enter a valid email address.');
    } else if (error.message.includes('Password should be')) {
      return showError('Password does not meet requirements. Please use at least 6 characters.');
    } else if (error.message.includes('rate limit')) {
      return showError('Too many registration attempts. Please wait a few minutes before trying again.');
    } else {
      return showError(error.message || 'Registration failed. Please try again or contact support if the problem persists.');
    }
  }

  // Check if email confirmation is required
  if (data.user && !data.session) {
    showSuccess('Registration successful! Please check your email inbox and click the verification link to activate your account.');
    setTimeout(() => {
      window.location.href = 'login.php';
    }, 4000);
  } else if (data.session) {
    // If auto-confirmed (unlikely in production)
    showSuccess('Registration successful! Your account is now active. Redirecting to login...');
    setTimeout(() => {
      window.location.href = 'login.php';
    }, 1500);
  }
});

function togglePasswordVisibility(inputId) {
  const passwordInput = document.getElementById(inputId);
  const toggleButton = passwordInput.nextElementSibling;
  const icon = toggleButton.querySelector('i');
  if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    passwordInput.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}
</script>

<?php include 'includes/footer.php'; ?>


