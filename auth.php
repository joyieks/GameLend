<?php
require_once 'includes/session_config.php';
$page_title = "Account";

$supabaseUrl = getenv('SUPABASE_URL') ?: '';
$supabaseAnonKey = getenv('SUPABASE_ANON_KEY') ?: '';

// Fallback: optional local config file if env vars are not set
if (($supabaseUrl === '' || $supabaseAnonKey === '') && file_exists(__DIR__ . '/includes/supabase_config.php')) {
    $cfg = include __DIR__ . '/includes/supabase_config.php';
    if (is_array($cfg)) {
        $supabaseUrl = $supabaseUrl ?: ($cfg['SUPABASE_URL'] ?? '');
        $supabaseAnonKey = $supabaseAnonKey ?: ($cfg['SUPABASE_ANON_KEY'] ?? '');
    }
}

include 'includes/header.php';
?>

<div class="card" style="max-width: 520px; margin: 2rem auto;">
    <div class="card-header">
        <h2 class="card-title">Account</h2>
    </div>
    <div class="card-body">
        <div class="alert alert-danger" id="authError" style="display:none;"></div>
        <div class="alert alert-success" id="authSuccess" style="display:none;"></div>

        <div id="authForms">
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" class="form-control" placeholder="you@example.com" required>
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="password-container">
                    <input type="password" id="password" class="form-control" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div style="display:flex; gap: .5rem; flex-wrap: wrap;">
                <button class="btn btn-primary" id="btnSignIn">Sign In</button>
                <button class="btn" style="background:#2c3e50;color:#fff;" id="btnSignUp">Sign Up</button>
            </div>
            <p class="register-link" style="margin-top:1rem;">Forgot your password? Use Supabase reset from your email provider setup, or add magic link later.</p>
        </div>

        <div id="sessionBox" style="display:none; margin-top:1rem;">
            <p><strong>Signed in as</strong>: <span id="userEmail"></span></p>
            <button class="btn btn-primary" id="btnSignOut"><i class="fas fa-sign-out-alt"></i> Sign Out</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script>
const SUPABASE_URL = <?php echo json_encode($supabaseUrl); ?>;
const SUPABASE_ANON_KEY = <?php echo json_encode($supabaseAnonKey); ?>;
const MODE = new URLSearchParams(window.location.search).get('mode');

if (!SUPABASE_URL || !SUPABASE_ANON_KEY) {
  const el = document.getElementById('authError');
  el.style.display = 'block';
  el.textContent = 'Missing SUPABASE_URL or SUPABASE_ANON_KEY env variables.';
}

const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

const authError = document.getElementById('authError');
const authSuccess = document.getElementById('authSuccess');
const authForms = document.getElementById('authForms');
const sessionBox = document.getElementById('sessionBox');
const userEmail = document.getElementById('userEmail');

function showError(msg){ authError.style.display='block'; authError.textContent=msg; }
function showSuccess(msg){ authSuccess.style.display='block'; authSuccess.textContent=msg; }
function clearAlerts(){ authError.style.display='none'; authSuccess.style.display='none'; }

async function refreshSessionUI(){
  const { data: { session } } = await supabase.auth.getSession();
  clearAlerts();
  if (session && session.user) {
    authForms.style.display = 'none';
    sessionBox.style.display = 'block';
    userEmail.textContent = session.user.email || '(no email)';
  } else {
    authForms.style.display = 'block';
    sessionBox.style.display = 'none';
  }
}

document.getElementById('btnSignIn').addEventListener('click', async () => {
  clearAlerts();
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  if (!email || !password) return showError('Email and password are required.');
  
  const { data, error } = await supabase.auth.signInWithPassword({ email, password });
  if (error) return showError(error.message);
  
  // Send token to PHP to create session
  try {
    const response = await fetch('login_handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        access_token: data.session.access_token
      })
    });
    
    const result = await response.json();
    
    if (result.success) {
      showSuccess('Login successful! Redirecting...');
      setTimeout(() => {
        window.location.href = result.redirect;
      }, 500);
    } else {
      showError(result.error || 'Login failed');
    }
  } catch (err) {
    showError('Error creating session: ' + err.message);
  }
});

document.getElementById('btnSignUp').addEventListener('click', async () => {
  clearAlerts();
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  if (!email || !password) return showError('Email and password are required.');
  const { error } = await supabase.auth.signUp({ email, password });
  if (error) return showError(error.message);
  showSuccess('Check your email to confirm your account.');
});

document.getElementById('btnSignOut').addEventListener('click', async () => {
  clearAlerts();
  const { error } = await supabase.auth.signOut();
  if (error) return showError(error.message);
  showSuccess('Signed out.');
  refreshSessionUI();
});

supabase.auth.onAuthStateChange(() => { refreshSessionUI(); });

(async function initAuth(){
  // Check if there's a hash with access_token (from email confirmation)
  const hash = window.location.hash;
  if (hash && hash.includes('access_token')) {
    const params = new URLSearchParams(hash.substring(1));
    const access_token = params.get('access_token');
    const refresh_token = params.get('refresh_token');
    
    if (access_token) {
      clearAlerts();
      showSuccess('Email confirmed! Setting up your session...');
      
      // Set the session in Supabase
      await supabase.auth.setSession({ access_token, refresh_token });
      
      // Get the session
      const { data: { session } } = await supabase.auth.getSession();
      
      if (session) {
        // Send to PHP to create PHP session
        try {
          const response = await fetch('login_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ access_token: session.access_token })
          });
          
          const result = await response.json();
          
          if (result.success) {
            showSuccess('Account confirmed! Redirecting to dashboard...');
            setTimeout(() => {
              window.location.href = result.redirect;
            }, 1000);
            return; // Don't continue with normal init
          }
        } catch (err) {
          showError('Error creating session: ' + err.message);
        }
      }
    }
  }
  
  // If explicit mode is requested, ensure we show forms and not an existing session
  if (MODE === 'login' || MODE === 'register') {
    await supabase.auth.signOut();
    if (MODE === 'register') {
      // Optional: focus UX for sign up
      document.getElementById('btnSignUp').classList.add('focus');
    }
  }
  refreshSessionUI();
})();

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


