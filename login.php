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
    } else {
        header('Location: customer/dashboard.php');
    }
    exit();
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
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
  
  // Client-side validation
  if (!email || !password) {
    return showError('Please fill in all fields.');
  }
  
  // Email format validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    return showError('Please enter a valid email address.');
  }
  
  // Password length validation
  if (password.length < 6) {
    return showError('Password must be at least 6 characters long.');
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
      
      // Provide user-friendly error messages
      if (error.message.includes('Invalid login credentials')) {
        return showError('Invalid email or password. Please try again.');
      } else if (error.message.includes('Email not confirmed')) {
        return showError('Please verify your email address before logging in. Check your inbox for the verification link.');
      } else if (error.message.includes('Email link is invalid')) {
        return showError('The login link has expired. Please try logging in again.');
      } else if (error.message.includes('Too many requests')) {
        return showError('Too many login attempts. Please wait a few minutes and try again.');
      } else {
        return showError(error.message || 'Login failed. Please try again.');
      }
    }
    
    // Check if session exists
    if (!data.session) {
      submitButton.disabled = false;
      submitButton.innerHTML = originalButtonText;
      return showError('Please verify your email before logging in. Check your inbox for the verification link.');
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
    
    // Handle HTTP errors
    if (!response.ok) {
      let errorMessage = 'Failed to create session. Please try again.';
      
      try {
        const errorData = await response.json();
        console.error('Login handler error:', errorData);
        errorMessage = errorData.error || errorMessage;
        
        // Log debug info if available
        if (errorData.debug) {
          console.error('Debug info:', errorData.debug);
        }
      } catch (parseError) {
        console.error('Error parsing error response:', parseError);
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
      return showError('Invalid server response. Please try again.');
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
      showError(result.error || 'Failed to create session. Please try again.');
    }
    
  } catch (err) {
    // Handle network errors and other exceptions
    console.error('Login error:', err);
    submitButton.disabled = false;
    submitButton.innerHTML = originalButtonText;
    
    if (err.message.includes('Failed to fetch')) {
      showError('Network error. Please check your internet connection and try again.');
    } else if (err.message.includes('NetworkError')) {
      showError('Cannot connect to server. Please check your internet connection.');
    } else {
      showError('An unexpected error occurred: ' + err.message);
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
    const { error } = await supabase.auth.resetPasswordForEmail(resetEmail, {
      redirectTo: window.location.origin + '/GameLend/change_password.php'
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
