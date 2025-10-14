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

<div class="card" style="max-width: 520px; margin: 2rem auto;">
    <div class="card-header">
        <h2 class="card-title">Set a New Password</h2>
    </div>
    <div class="card-body">
        <div class="alert alert-danger" id="pwError" style="display:none;"></div>
        <div class="alert alert-success" id="pwSuccess" style="display:none;"></div>
        <form id="pwForm">
            <div class="form-group">
                <label class="form-label" for="password">New Password</label>
                <div class="password-container">
                    <input type="password" id="password" class="form-control" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="confirm">Confirm New Password</label>
                <div class="password-container">
                    <input type="password" id="confirm" class="form-control" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update Password</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script>
const SUPABASE_URL = <?php echo json_encode($supabaseUrl); ?>;
const SUPABASE_ANON_KEY = <?php echo json_encode($supabaseAnonKey); ?>;
const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

const pwError = document.getElementById('pwError');
const pwSuccess = document.getElementById('pwSuccess');
function showError(msg){ pwError.style.display='block'; pwError.textContent=msg; }
function showSuccess(msg){ pwSuccess.style.display='block'; pwSuccess.textContent=msg; }
function clearAlerts(){ pwError.style.display='none'; pwSuccess.style.display='none'; }

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


