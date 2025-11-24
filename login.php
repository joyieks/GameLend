<?php
require_once 'includes/session_config.php';
$page_title = "Login";

// Get Supabase credentials
$supabaseUrl = getenv('SUPABASE_URL') ?: '';
$supabaseAnonKey = getenv('SUPABASE_ANON_KEY') ?: '';

if (($supabaseUrl === '' || $supabaseAnonKey === '') && file_exists(__DIR__ . '/includes/supabase_config.php')) {
    $cfg = include __DIR__ . '/includes/supabase_config.php';
    if (is_array($cfg)) {
        $supabaseUrl = $supabaseUrl ?: ($cfg['SUPABASE_URL'] ?? '');
        $supabaseAnonKey = $supabaseAnonKey ?: ($cfg['SUPABASE_ANON_KEY'] ?? '');
    }
}

// Redirect if already logged in
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit();
    } else {
        header('Location: customer/dashboard.php');
        exit();
    }
}

$error = '';
$success = '';

// Check for logout message
if(isset($_GET['logout'])) {
    $success = 'You have been successfully logged out.';
}

// Check for session timeout message
if(isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please login again.';
}

// Check for account disabled message
if(isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

// Check for registration success message
if(isset($_SESSION['registration_success'])) {
    $success = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
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
    
    .login-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }
    
    .login-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        width: 100%;
        max-width: 500px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .login-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4);
    }
    
    .card-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 2rem;
        text-align: center;
        position: relative;
    }
    
    .card-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--secondary), var(--accent));
    }
    
    .card-title {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 800;
        color: white !important;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }
    
    .card-subtitle {
        margin: 0.5rem 0 0 0;
        font-size: 0.95rem;
        opacity: 0.9;
        color: #ecf0f1;
    }
    
    .card-body {
        padding: 2rem;
    }
    
    .alert {
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .alert-danger {
        background: rgba(214, 48, 49, 0.1);
        color: var(--danger);
        border: 1px solid rgba(214, 48, 49, 0.2);
    }
    
    .alert-success {
        background: rgba(0, 184, 148, 0.1);
        color: var(--success);
        border: 1px solid rgba(0, 184, 148, 0.2);
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--dark);
        font-size: 0.95rem;
    }
    
    .form-control {
        width: 100%;
        padding: 0.9rem 1rem;
        font-size: 1rem;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        background: white;
        box-shadow: 0 0 0 3px rgba(108, 92, 238, 0.1);
    }
    
    .password-container {
        position: relative;
    }
    
    .toggle-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #6c757d;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 50%;
        transition: all 0.3s ease;
    }
    
    .toggle-password:hover {
        background: #e9ecef;
        color: var(--primary);
    }
    
    .btn {
        display: block;
        width: 100%;
        padding: 1rem;
        border-radius: 10px;
        border: none;
        font-size: 1.05rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 1rem;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        box-shadow: 0 4px 15px rgba(108, 92, 231, 0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(108, 92, 231, 0.4);
    }
    
    .register-link {
        text-align: center;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e9ecef;
        color: #6c757d;
    }
    
    .register-link a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 700;
        transition: all 0.3s ease;
    }
    
    .register-link a:hover {
        color: var(--primary-dark);
        text-decoration: underline;
    }
    
    .forgot-password-link {
        text-align: center;
        margin-top: 1rem;
        margin-bottom: 0.5rem;
    }
    
    .forgot-password-link a {
        color: var(--secondary);
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s ease;
    }
    
    .forgot-password-link a:hover {
        color: var(--primary);
        text-decoration: underline;
    }
    
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    
    .modal-overlay.active {
        display: flex;
    }
    
    .modal-content {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        max-width: 450px;
        width: 90%;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s ease;
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .modal-header {
        margin-bottom: 1.5rem;
    }
    
    .modal-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 0.5rem;
    }
    
    .modal-subtitle {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .modal-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }
    
    .btn-secondary {
        background: var(--gray);
        color: var(--dark);
        flex: 1;
    }
    
    .btn-secondary:hover {
        background: #bdc3c7;
    }
    
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
    
    .alert-warning {
        background: linear-gradient(135deg, #fffaf0 0%, #fef5e7 100%);
        color: #975a16;
        border-left: 4px solid #dd6b20;
    }
    
    .alert-warning i {
        color: #dd6b20;
        margin-right: 8px;
        font-size: 1.1rem;
        vertical-align: middle;
    }
    
    .alert-info {
        background: linear-gradient(135deg, #ebf8ff 0%, #dbeafe 100%);
        color: #2c5282;
        border-left: 4px solid #3182ce;
    }
    
    .alert-info i {
        color: #3182ce;
        margin-right: 8px;
        font-size: 1.1rem;
        vertical-align: middle;
    }
    
    @media (max-width: 768px) {
        .login-container {
            padding: 1rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .form-control {
            font-size: 16px; /* Prevents zoom on iOS */
        }
        
        .alert {
            padding: 0.875rem 1rem;
            font-size: 0.9rem;
        }
    }
</style>

<div class="login-container">
    <div class="login-card">
        <div class="card-header">
            <h2 class="card-title">🎮 GameLend</h2>
            <p class="card-subtitle">Sign in to continue</p>
        </div>
        
        <div class="card-body">
            <div class="alert alert-danger" id="loginError" style="display:none;"></div>
            <div class="alert alert-success" id="loginSuccess" style="display:none;"></div>
            
            <form id="loginForm">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           placeholder="Enter your email">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" class="form-control" required
                               placeholder="Enter your password">
                        <button type="button" class="toggle-password" onclick="toggleLoginPassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
            <div class="forgot-password-link">
                <a href="#" onclick="showForgotPasswordModal(); return false;">
                    <i class="fas fa-key"></i> Forgot Password?
                </a>
            </div>
            
            <div class="register-link">
                Don't have an account? <a href="register.php">Create one here</a>
            </div>
        </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal-overlay" id="forgotPasswordModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-key"></i> Reset Password
            </h3>
            <p class="modal-subtitle">Enter your email address and we'll send you a password reset link.</p>
        </div>
        
        <div class="alert alert-danger" id="modalError" style="display:none;"></div>
        <div class="alert alert-success" id="modalSuccess" style="display:none;"></div>
        
        <form id="forgotPasswordForm">
            <div class="form-group">
                <label for="resetEmail" class="form-label">
                    <i class="fas fa-envelope"></i> Email Address
                </label>
                <input type="email" id="resetEmail" name="resetEmail" class="form-control" required 
                       placeholder="Enter your email">
            </div>
            
            <div class="modal-buttons">
                <button type="button" class="btn btn-secondary" onclick="closeForgotPasswordModal()">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script>
const SUPABASE_URL = <?php echo json_encode($supabaseUrl); ?>;
const SUPABASE_ANON_KEY = <?php echo json_encode($supabaseAnonKey); ?>;

if (!SUPABASE_URL || !SUPABASE_ANON_KEY) {
  document.getElementById('loginError').style.display = 'block';
  document.getElementById('loginError').innerHTML = '<i class="fas fa-exclamation-circle"></i> Missing Supabase configuration.';
}

const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

const loginError = document.getElementById('loginError');
const loginSuccess = document.getElementById('loginSuccess');

function showError(msg) {
  loginError.style.display = 'block';
  loginError.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + msg;
  loginSuccess.style.display = 'none';
}

function showSuccess(msg) {
  loginSuccess.style.display = 'block';
  loginSuccess.innerHTML = '<i class="fas fa-check-circle"></i> ' + msg;
  loginError.style.display = 'none';
}

function clearAlerts() {
  loginError.style.display = 'none';
  loginSuccess.style.display = 'none';
}

// Handle email confirmation callback from URL hash
async function handleEmailConfirmation() {
  const hashParams = new URLSearchParams(window.location.hash.substring(1));
  const error = hashParams.get('error');
  const errorDescription = hashParams.get('error_description');
  const accessToken = hashParams.get('access_token');
  
  // Check for errors in the URL
  if (error) {
    clearAlerts();
    if (error === 'access_denied' && errorDescription) {
      const message = errorDescription.replace(/\+/g, ' ');
      if (message.includes('expired')) {
        showError('Email confirmation link has expired. Please request a new one by registering again or contact support.');
      } else {
        showError('Email confirmation failed: ' + message);
      }
    } else {
      showError('Email confirmation failed. Please try again or contact support.');
    }
    // Clean up URL
    window.history.replaceState({}, document.title, window.location.pathname);
    return;
  }
  
  // Check if there's an access token (successful confirmation)
  if (accessToken) {
    clearAlerts();
    showSuccess('Email confirmed successfully! You can now log in.');
    
    // Get the session
    const { data: { session } } = await supabase.auth.getSession();
    
    if (session) {
      // User is already logged in after confirmation, create PHP session
      showSuccess('Email confirmed! Logging you in...');
      
      try {
        const response = await fetch('login_handler.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ access_token: session.access_token })
        });
        
        const result = await response.json();
        
        if (result.success && result.redirect) {
          window.location.href = result.redirect;
        } else {
          showError(result.error || 'Session creation failed. Please log in manually.');
        }
      } catch (err) {
        showError('Failed to create session. Please log in manually.');
      }
    }
    
    // Clean up URL
    window.history.replaceState({}, document.title, window.location.pathname);
  }
}

// Run email confirmation handler on page load
handleEmailConfirmation();

// Show PHP messages if they exist
<?php if($success): ?>
showSuccess(<?php echo json_encode($success); ?>);
<?php endif; ?>

<?php if($error): ?>
showError(<?php echo json_encode($error); ?>);
<?php endif; ?>

document.getElementById('loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  clearAlerts();
  
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const submitButton = e.target.querySelector('button[type="submit"]');
  const originalButtonText = submitButton.innerHTML;
  
  // Client-side validation with clear error messages
  if (!email || !password) {
    return showError('Both email and password are required. Please fill in all fields.');
  }
  
  // Email format validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    return showError('Please enter a valid email address (e.g., user@example.com).');
  }
  
  // Password length validation
  if (password.length < 6) {
    return showError('Password must be at least 6 characters long. Please try again.');
  }
  
  // Disable button and show loading state
  submitButton.disabled = true;
  submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
  
  try {
    // Sign in with Supabase
    const { data, error } = await supabase.auth.signInWithPassword({ 
      email, 
      password 
    });
    
    // Handle Supabase authentication errors
    if (error) {
      submitButton.disabled = false;
      submitButton.innerHTML = originalButtonText;
      
    // Provide clear, professional error messages
    if (error.message.includes('Invalid login credentials')) {
      return showError('Incorrect email or password. Please check your credentials and try again.');
    } else if (error.message.includes('Email not confirmed')) {
      return showError('Email verification required. Please check your inbox and verify your email address before logging in.');
    } else if (error.message.includes('Email link is invalid')) {
      return showError('This login link has expired. Please request a new one by trying to log in again.');
    } else if (error.message.includes('Too many requests')) {
      return showError('Too many login attempts detected. For security reasons, please wait 5 minutes before trying again.');
    } else if (error.message.includes('User not found')) {
      return showError('No account found with this email address. Please check your email or register for a new account.');
    } else {
      return showError(error.message || 'Unable to sign in. Please try again or contact support if the problem persists.');
    }
    }
    
    // Check if session exists
    if (!data.session) {
      submitButton.disabled = false;
      submitButton.innerHTML = originalButtonText;
      return showError('Email verification required. Please check your inbox and click the verification link before logging in.');
    }
    
    // Send token to PHP to create session
    showSuccess('Login successful! Creating session...');
    
    const response = await fetch('login_handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        access_token: data.session.access_token
      })
    });
    
    // Handle HTTP errors with detailed messages
    if (!response.ok) {
      let errorMessage = 'Unable to complete login. Please try again.';
      
      try {
        const errorData = await response.json();
        console.error('Login handler error:', errorData);
        
        // Use the error message from the server (now professional and clear)
        errorMessage = errorData.error || errorMessage;
        
        // Log debug info if available (for developers)
        if (errorData.debug) {
          console.error('Debug info:', errorData.debug);
        }
      } catch (parseError) {
        console.error('Error parsing error response:', parseError);
        errorMessage = 'Server error occurred. Please try again or contact support.';
      }
      
      submitButton.disabled = false;
      submitButton.innerHTML = originalButtonText;
      return showError(errorMessage);
    }
    
    // Parse successful response
    let result;
    try {
      result = await response.json();
    } catch (parseError) {
      console.error('Error parsing success response:', parseError);
      submitButton.disabled = false;
      submitButton.innerHTML = originalButtonText;
      return showError('Unable to process server response. Please refresh the page and try again.');
    }
    
    // Handle session creation result
    if (result.success) {
      showSuccess('Redirecting to dashboard...');
      
      // Redirect after short delay
      setTimeout(() => {
        window.location.href = result.redirect;
      }, 500);
    } else {
      submitButton.disabled = false;
      submitButton.innerHTML = originalButtonText;
      showError(result.error || 'Unable to establish your session. Please try logging in again.');
    }
    
  } catch (err) {
    // Handle network errors and other exceptions with clear messages
    console.error('Login error:', err);
    submitButton.disabled = false;
    submitButton.innerHTML = originalButtonText;
    
    if (err.message.includes('Failed to fetch') || err.message.includes('NetworkError')) {
      showError('Unable to connect to the server. Please check your internet connection and try again.');
    } else if (err.message.includes('timeout')) {
      showError('Connection timed out. Please check your internet connection and try again.');
    } else {
      showError('An unexpected error occurred. Please try again or contact support if the problem persists.');
    }
  }
});

function toggleLoginPassword() {
    const passwordInput = document.getElementById('password');
    const toggleButton = document.querySelector('.toggle-password');
    
    if (!passwordInput || !toggleButton) return;
    
    const icon = toggleButton.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        if (icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    } else {
        passwordInput.type = 'password';
        if (icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
}

// Forgot Password Modal Functions
function showForgotPasswordModal() {
  const modal = document.getElementById('forgotPasswordModal');
  const resetEmail = document.getElementById('resetEmail');
  const modalError = document.getElementById('modalError');
  const modalSuccess = document.getElementById('modalSuccess');
  
  if (modal) modal.classList.add('active');
  if (resetEmail) resetEmail.value = '';
  if (modalError) modalError.style.display = 'none';
  if (modalSuccess) modalSuccess.style.display = 'none';
}

function closeForgotPasswordModal() {
  const modal = document.getElementById('forgotPasswordModal');
  if (modal) modal.classList.remove('active');
}

// Close modal when clicking outside
const forgotPasswordModal = document.getElementById('forgotPasswordModal');
if (forgotPasswordModal) {
  forgotPasswordModal.addEventListener('click', function(e) {
    if (e.target === this) {
      closeForgotPasswordModal();
    }
  });
}

// Handle forgot password form submission
const forgotPasswordForm = document.getElementById('forgotPasswordForm');
if (forgotPasswordForm) {
  forgotPasswordForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const modalError = document.getElementById('modalError');
    const modalSuccess = document.getElementById('modalSuccess');
    const resetEmail = document.getElementById('resetEmail').value.trim();
    
    if (!resetEmail) {
      if (modalError) {
        modalError.style.display = 'block';
        modalError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter your email address.';
      }
      return;
    }
    
    if (modalError) modalError.style.display = 'none';
    if (modalSuccess) modalSuccess.style.display = 'none';
    
    // Send password reset email using Supabase
    // Get the base path (e.g., /GameLend) from current location
    const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
    // Use absolute URL to ensure correct redirect
    const redirectUrl = window.location.origin + basePath + '/change_password.php';
    const { error } = await supabase.auth.resetPasswordForEmail(resetEmail, {
      redirectTo: redirectUrl
    });
    
    if (error) {
      if (modalError) {
        modalError.style.display = 'block';
        modalError.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + error.message;
      }
    } else {
      if (modalSuccess) {
        modalSuccess.style.display = 'block';
        modalSuccess.innerHTML = '<i class="fas fa-check-circle"></i> Password reset link sent! Please check your email.';
      }
      
      // Close modal after 3 seconds
      setTimeout(() => {
        closeForgotPasswordModal();
      }, 3000);
    }
  });
}
</script>

<?php include 'includes/footer.php'; ?>
