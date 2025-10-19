<?php
require_once '../includes/session_config.php';
$page_title = "Add New User";

// Include authentication check
require_once '../includes/auth_check.php';

// Set security headers
setSecurityHeaders();

// Validate session and require admin access
validateSession();
requireAdmin();

// Get Supabase config
$config = require_once '../includes/supabase_config.php';
$supabaseUrl = $config['SUPABASE_URL'];
$supabaseAnonKey = $config['SUPABASE_ANON_KEY'];

include 'includes/admin_header.php';
?>

<style>
    .form-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--dark);
    }
    
    .form-label.required:after {
        content: ' *';
        color: #dc3545;
    }
    
    .form-control {
        width: 100%;
        padding: 0.75rem;
        font-size: 1rem;
        border: 1px solid #ddd;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(108, 92, 238, 0.15);
    }
    
    .form-control:disabled {
        background-color: #f5f5f5;
        cursor: not-allowed;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        font-weight: 600;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-primary:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #5a6268;
    }
    
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .alert-info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    
    .form-help {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
    
    .loading {
        display: none;
        text-align: center;
        padding: 1rem;
    }
    
    .loading.active {
        display: block;
    }
    
    .spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid #667eea;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="form-container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-user-plus"></i> Add New User
            </h2>
        </div>
        
        <div id="messageContainer"></div>
        
        <div class="alert alert-info">
            <strong>Note:</strong> This will create a new user account with Supabase Authentication. 
            The user will receive an email to verify their account and set their password.
        </div>
        
        <form id="addUserForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name" class="form-label required">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" 
                           placeholder="Enter first name" required minlength="2">
                </div>
                
                <div class="form-group">
                    <label for="middle_name" class="form-label">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" class="form-control" 
                           placeholder="Enter middle name (optional)">
                </div>
            </div>
            
            <div class="form-group">
                <label for="last_name" class="form-label required">Last Name</label>
                <input type="text" id="last_name" name="last_name" class="form-control" 
                       placeholder="Enter last name" required minlength="2">
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label required">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" 
                       placeholder="user@example.com" required>
                <div class="form-help">
                    User will receive verification email at this address
                </div>
            </div>
            
            <div class="form-group">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" 
                       placeholder="+1234567890 (optional)">
            </div>
            
            <div class="form-group">
                <label for="role" class="form-label required">Role</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="customer" selected>Customer</option>
                    <option value="admin">Administrator</option>
                </select>
                <div class="form-help">
                    Admins have full access to manage the system
                </div>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label required">Temporary Password</label>
                <input type="password" id="password" name="password" class="form-control" 
                       placeholder="Enter temporary password" required minlength="6">
                <div class="form-help">
                    User can change this after first login (minimum 6 characters)
                </div>
            </div>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Creating user account...</p>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" id="submitBtn" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Create User
                </button>
                <a href="users.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script>
const SUPABASE_URL = <?php echo json_encode($supabaseUrl); ?>;
const SUPABASE_ANON_KEY = <?php echo json_encode($supabaseAnonKey); ?>;

if (!SUPABASE_URL || !SUPABASE_ANON_KEY) {
    showMessage('Missing Supabase configuration. Please check your .env file.', 'danger');
}

const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
const form = document.getElementById('addUserForm');
const submitBtn = document.getElementById('submitBtn');
const loading = document.getElementById('loading');
const messageContainer = document.getElementById('messageContainer');

function showMessage(message, type = 'info') {
    messageContainer.innerHTML = `
        <div class="alert alert-${type}">
            ${message}
        </div>
    `;
    
    // Scroll to top to show message
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    // Auto-hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            messageContainer.innerHTML = '';
        }, 5000);
    }
}

function setLoading(isLoading) {
    if (isLoading) {
        loading.classList.add('active');
        submitBtn.disabled = true;
        form.querySelectorAll('input, select').forEach(el => el.disabled = true);
    } else {
        loading.classList.remove('active');
        submitBtn.disabled = false;
        form.querySelectorAll('input, select').forEach(el => el.disabled = false);
    }
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData(form);
    const userData = {
        email: formData.get('email').trim(),
        password: formData.get('password'),
        first_name: formData.get('first_name').trim(),
        middle_name: formData.get('middle_name').trim() || null,
        last_name: formData.get('last_name').trim(),
        phone: formData.get('phone').trim() || null,
        role: formData.get('role')
    };
    
    // Basic validation
    if (userData.first_name.length < 2) {
        showMessage('First name must be at least 2 characters long', 'danger');
        return;
    }
    
    if (userData.last_name.length < 2) {
        showMessage('Last name must be at least 2 characters long', 'danger');
        return;
    }
    
    if (userData.password.length < 6) {
        showMessage('Password must be at least 6 characters long', 'danger');
        return;
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(userData.email)) {
        showMessage('Please enter a valid email address', 'danger');
        return;
    }
    
    setLoading(true);
    messageContainer.innerHTML = '';
    
    try {
        // Create user with Supabase Auth
        const { data, error } = await supabase.auth.signUp({
            email: userData.email,
            password: userData.password,
            options: {
                data: {
                    first_name: userData.first_name,
                    middle_name: userData.middle_name,
                    last_name: userData.last_name,
                    phone: userData.phone,
                    role: userData.role
                },
                emailRedirectTo: 'http://localhost/GameLend/login.php'
            }
        });
        
        if (error) {
            throw error;
        }
        
        // Success!
        showMessage(
            `✅ User account created successfully!<br><br>
            <strong>Email:</strong> ${userData.email}<br>
            <strong>Name:</strong> ${userData.first_name} ${userData.last_name}<br>
            <strong>Role:</strong> ${userData.role}<br><br>
            The user will receive a verification email to activate their account.`,
            'success'
        );
        
        // Reset form
        form.reset();
        
        // Redirect to users list after 3 seconds
        setTimeout(() => {
            window.location.href = 'users.php';
        }, 3000);
        
    } catch (error) {
        console.error('Error creating user:', error);
        
        let errorMessage = 'Failed to create user account. ';
        
        if (error.message.includes('already registered')) {
            errorMessage += 'This email address is already registered.';
        } else if (error.message.includes('Invalid email')) {
            errorMessage += 'Please provide a valid email address.';
        } else if (error.message.includes('Password')) {
            errorMessage += 'Password must be at least 6 characters.';
        } else {
            errorMessage += error.message || 'Please try again.';
        }
        
        showMessage(errorMessage, 'danger');
    } finally {
        setLoading(false);
    }
});

// Real-time email validation
document.getElementById('email').addEventListener('blur', function() {
    const email = this.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email && !emailRegex.test(email)) {
        this.style.borderColor = '#dc3545';
    } else {
        this.style.borderColor = '#ddd';
    }
});

// Password strength indicator
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strength = password.length < 6 ? 'weak' : 
                    password.length < 10 ? 'medium' : 'strong';
    
    if (password.length > 0 && password.length < 6) {
        this.style.borderColor = '#dc3545';
    } else if (password.length >= 6 && password.length < 10) {
        this.style.borderColor = '#ffc107';
    } else if (password.length >= 10) {
        this.style.borderColor = '#28a745';
    } else {
        this.style.borderColor = '#ddd';
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>
