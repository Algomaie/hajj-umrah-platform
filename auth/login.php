<?php
// Include required files and start session
require_once '../includes/functions.php';
startSession();

// Set page title and body class
$pageTitle = __('login');
$bodyClass = 'bg-light';


// Check if user is already logged in
if (isLoggedIn()) {
    
    $userType = $_SESSION['user_type'] ?? 'pilgrim';
    redirect(SITE_URL . '/dashboard/' . $userType . '.php');
    exit;
}

// Initialize error variables
$identifierError = '';
$passwordError = '';
$generalError = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = isset($_POST['identifier']) ? sanitizeInput($_POST['identifier']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';
    
    // Validate inputs
    $valid = true;
    
    if (empty($identifier)) {
        $identifierError = __('enter_identifier');
        $valid = false;
    }
    
    if (empty($password)) {
        $passwordError = __('enter_password');
        $valid = false;
    }
    
    if ($valid) {
        // Check if identifier is email or username
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        
        // Attempt to login with either email or username
        $sql = $isEmail ? "SELECT * FROM users WHERE email = ?" : "SELECT * FROM users WHERE username = ?";
        $user = fetchRow($sql, [$identifier]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['language'] = $user['language'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Update last login timestamp
            updateData("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
            
            // Set remember me cookie if requested
            if ($remember) {
                $token = generateRandomString(32);
                $expires = time() + (86400 * 30); // 30 days
                insertData("INSERT INTO user_tokens (user_id, token, expires) VALUES (?, ?, ?)", 
                           [$user['id'], $token, date('Y-m-d H:i:s', $expires)]);
                setcookie('remember_token', $token, $expires, '/', '', false, true);
            }
            
            // Log activity
            logActivity($user['id'], 'login');
            
            // Redirect to appropriate dashboard or requested page
            $redirect = $user['user_type'] === 'admin' 
            ? SITE_URL . '/admin/dashboard.php'
            : (isset($_SESSION['redirect_after_login']) 
                ? $_SESSION['redirect_after_login'] 
                : SITE_URL . '/index.php');
            unset($_SESSION['redirect_after_login']);
            redirect($redirect);
            exit;
        } else {
            $generalError = __('invalid_credentials');
        }
    }
}

// Include header
include_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-circle text-primary" style="font-size: 3rem;"></i>
                        <h1 class="h3 mt-3"><?php echo __('login'); ?></h1>
                        <p class="text-muted"><?php echo __('login_description'); ?></p>
                    </div>
                    
                    <!-- Display flash message from sessionStorage or server-side -->
                    <?php if (!empty($generalError)): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-4">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $generalError; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('close'); ?>"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" novalidate>
                        <div class="mb-3">
                            <label for="identifier" class="form-label"><?php echo __('username_or_email'); ?></label>
                            <div class="input-group <?php echo !empty($identifierError) ? 'has-validation' : ''; ?>">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control <?php echo !empty($identifierError) ? 'is-invalid' : ''; ?>" 
                                       id="identifier" name="identifier" 
                                       placeholder="<?php echo __('username_or_email_placeholder'); ?>" 
                                       value="<?php echo isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier']) : ''; ?>"
                                       required>
                                <?php if (!empty($identifierError)): ?>
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        <?php echo $identifierError; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label"><?php echo __('password'); ?></label>
                            <div class="input-group <?php echo !empty($passwordError) ? 'has-validation' : ''; ?>">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control <?php echo !empty($passwordError) ? 'is-invalid' : ''; ?>" 
                                       id="password" name="password" required>
                                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if (!empty($passwordError)): ?>
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        <?php echo $passwordError; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-4 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    <?php echo __('remember_me'); ?>
                                </label>
                            </div>
                            <a href="<?php echo SITE_URL; ?>/auth/reset_password.php" class="text-primary text-decoration-none">
                                <?php echo __('forgot_password'); ?>
                            </a>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-sign-in-alt me-2"></i> <?php echo __('login_button'); ?>
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="mb-0"><?php echo __('dont_have_account'); ?> 
                            <a href="<?php echo SITE_URL; ?>/auth/register.php" class="text-primary fw-semibold">
                                <?php echo __('register_now'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });

    // Clear validation errors when user starts typing
    document.getElementById('identifier').addEventListener('input', function() {
        this.classList.remove('is-invalid');
    });
    
    document.getElementById('password').addEventListener('input', function() {
        this.classList.remove('is-invalid');
    });

    // Check for sessionStorage flash message
    document.addEventListener('DOMContentLoaded', () => {
        const flash = sessionStorage.getItem('flashMessage');
        if (flash) {
            const { type, message } = JSON.parse(flash);
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show mb-4`;
            alertDiv.setAttribute('role', 'alert');
            alertDiv.innerHTML = `
                <i class="fas fa-exclamation-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('close'); ?>"></button>
            `;
            document.querySelector('.card-body').prepend(alertDiv);
            sessionStorage.removeItem('flashMessage');
        }
    });
</script>

<?php
include_once '../includes/footer.php';
?>