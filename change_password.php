<?php
require_once 'includes/session_config.php';
$page_title = "Change Password";

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
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--dark);
        line-height: 1.6;
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
    
    /* Make the page full-width with background */
    main.container {
        max-width: 100%;
        padding: 0;
    }
    
    .password-reset-hero {
        /* Background image with a soft dark overlay for readability */
        background: transparent;
        color: white;
        text-align: center;
        padding: 4rem 2rem;
        border-radius: 0;
        margin-bottom: 0;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        position: relative;
        overflow: hidden;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    .password-reset-hero > * {
        position: relative;
        z-index: 1;
    }
    
    .password-reset-card {
        max-width: 480px;
        width: 100%;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px) saturate(180%);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1);
        animation: slideUp 0.5s ease-out;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .password-reset-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem 2.5rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .password-reset-card .card-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: pulse 3s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.1); opacity: 0.8; }
    }
    
    .password-reset-card .card-header .header-icon {
        font-size: 2.5rem;
        margin-bottom: 0.75rem;
        display: block;
        opacity: 0.95;
    }
    
    .password-reset-card .card-title {
        margin: 0;
        font-size: 1.75rem;
        color: white !important;
        font-weight: 700;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        letter-spacing: -0.5px;
        position: relative;
        z-index: 1;
    }
    
    .password-reset-card .card-subtitle {
        margin: 0.5rem 0 0 0;
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 400;
        position: relative;
        z-index: 1;
    }
    
    .password-reset-card .card-body {
        padding: 2.5rem;
        text-align: left;
    }
    
    .password-reset-card .form-group {
        margin-bottom: 1.75rem;
    }
    
    .password-reset-card .form-label {
        display: block;
        margin-bottom: 0.75rem;
        font-weight: 600;
        color: #2d3436;
        font-size: 0.95rem;
        letter-spacing: 0.2px;
        text-align: left;
    }
    
    .password-reset-card .password-container {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .password-reset-card .form-control {
        width: 100%;
        padding: 0.875rem 3rem 0.875rem 1.25rem;
        border: 2px solid #e1e8ed;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: #ffffff;
        color: #2d3436;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    }
    
    .password-reset-card .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1), 0 4px 12px rgba(0, 0, 0, 0.08);
        transform: translateY(-1px);
    }
    
    .password-reset-card .form-control::placeholder {
        color: #a0aec0;
    }
    
    .password-reset-card .toggle-password {
        position: absolute;
        right: 0.875rem;
        background: transparent;
        border: none;
        color: #718096;
        cursor: pointer;
        padding: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        border-radius: 8px;
        width: 36px;
        height: 36px;
    }
    
    .password-reset-card .toggle-password:hover {
        background: #f7fafc;
        color: #667eea;
        transform: scale(1.1);
    }
    
    .password-reset-card .toggle-password:active {
        transform: scale(0.95);
    }
    
    .password-reset-card .toggle-password i {
        font-size: 1.1rem;
    }
    
    .password-reset-card .btn {
        width: 100%;
        padding: 1rem 2rem;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: none;
        letter-spacing: 0.3px;
        box-shadow: 0 4px 14px rgba(102, 126, 234, 0.4);
        position: relative;
        overflow: hidden;
    }
    
    .password-reset-card .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .password-reset-card .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
    }
    
    .password-reset-card .btn-primary:active {
        transform: translateY(0);
    }
    
    .password-reset-card .btn-primary::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }
    
    .password-reset-card .btn-primary:active::before {
        width: 300px;
        height: 300px;
    }
    
    .password-reset-card .alert {
        padding: 1rem 1.25rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        animation: slideDown 0.3s ease-out;
        border: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .password-reset-card .alert-danger {
        background: linear-gradient(135deg, #fee 0%, #fdd 100%);
        color: #c53030;
        border-left: 4px solid #c53030;
    }
    
    .password-reset-card .alert-success {
        background: linear-gradient(135deg, #f0fff4 0%, #e6ffed 100%);
        color: #22543d;
        border-left: 4px solid #38a169;
    }
    
    .password-reset-card .alert i {
        font-size: 1.2rem;
    }
    
    @media (max-width: 768px) {
        .password-reset-hero {
            padding: 2rem 1rem;
            min-height: 100vh;
        }
        
        .password-reset-card {
            margin: 0 1rem;
            max-width: 100%;
        }
        
        .password-reset-card .card-header {
            padding: 1.75rem 2rem;
        }
        
        .password-reset-card .card-body {
            padding: 2rem 1.75rem;
        }
        
        .password-reset-card .card-title {
            font-size: 1.5rem;
        }
    }
</style>

<div class="password-reset-hero">
    <div class="password-reset-card">
        <div class="card-header">
            <i class="fas fa-lock header-icon"></i>
            <h2 class="card-title">Set a New Password</h2>
            <p class="card-subtitle">Create a strong password to secure your account</p>
        </div>
        <div class="card-body">
            <div class="alert alert-danger" id="pwError" style="display:none;">
                <i class="fas fa-exclamation-circle"></i>
                <span></span>
            </div>
            <div class="alert alert-success" id="pwSuccess" style="display:none;">
                <i class="fas fa-check-circle"></i>
                <span></span>
            </div>
            <form id="pwForm">
                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fas fa-key" style="margin-right: 0.5rem; color: #667eea;"></i>
                        New Password
                    </label>
                    <div class="password-container">
                        <input 
                            type="password" 
                            id="password" 
                            class="form-control" 
                            placeholder="Enter your new password"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm">
                        <i class="fas fa-key" style="margin-right: 0.5rem; color: #667eea;"></i>
                        Confirm New Password
                    </label>
                    <div class="password-container">
                        <input 
                            type="password" 
                            id="confirm" 
                            class="form-control" 
                            placeholder="Confirm your new password"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm')" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save" style="margin-right: 0.5rem;"></i>
                    Update Password
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script>
const SUPABASE_URL = <?php echo json_encode($supabaseUrl); ?>;
const SUPABASE_ANON_KEY = <?php echo json_encode($supabaseAnonKey); ?>;
const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

const pwError = document.getElementById('pwError');
const pwSuccess = document.getElementById('pwSuccess');
function showError(msg){ 
    pwError.style.display='flex'; 
    pwError.querySelector('span').textContent = msg;
    pwSuccess.style.display='none';
}
function showSuccess(msg){ 
    pwSuccess.style.display='flex'; 
    pwSuccess.querySelector('span').textContent = msg;
    pwError.style.display='none';
}
function clearAlerts(){ 
    pwError.style.display='none'; 
    pwSuccess.style.display='none'; 
}

// When Supabase redirects here with an access_token, we must set the session first
(async function ensureSessionFromHash(){
  const hash = window.location.hash;
  if (hash && hash.includes('access_token')) {
    const params = new URLSearchParams(hash.substring(1));
    const access_token = params.get('access_token');
    const refresh_token = params.get('refresh_token');
    if (access_token) {
      await supabase.auth.setSession({ access_token, refresh_token });
      // Clean hash to avoid leaking tokens in subsequent nav
      history.replaceState(null, '', window.location.pathname);
    }
  }
})();

document.getElementById('pwForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  clearAlerts();
  const password = document.getElementById('password').value;
  const confirm = document.getElementById('confirm').value;
  if (!password || !confirm) return showError('Please fill in both fields.');
  if (password !== confirm) return showError('Passwords do not match.');

  const { error } = await supabase.auth.updateUser({ password });
  if (error) return showError(error.message);
  showSuccess('Password updated. You can now login.');
  setTimeout(() => { window.location.href = 'login.php'; }, 1200);
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


