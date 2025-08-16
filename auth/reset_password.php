<?php
// Set page title

// Include required files
require_once('../includes/functions.php');
$pageTitle = __('reset_password');
$bodyClass = 'bg-light';


// Start session
startSession();

// Process request
$step = isset($_GET['step']) ? $_GET['step'] : 'request';
$token = isset($_GET['token']) ? $_GET['token'] : null;

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'request') {
        $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
        
        if (empty($email)) {
            setFlashMessage('danger', __('email_required'));
        } else {
            // Check if email exists
            $sql = "SELECT id FROM users WHERE email = ?";
            $user = fetchRow($sql, [$email]);
            
            if ($user) {
                // Generate a token
                $token = generateRandomString(32);
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry
                
                // Store token in database
                $sql = "INSERT INTO password_resets (user_id, token, expires) VALUES (?, ?, ?)";
                insertData($sql, [$user['id'], $token, $expires]);
                
                // In a real application, send an email with the reset link
                // For now, just show the token
                $resetLink = SITE_URL . '/auth/reset_password.php?step=reset&token=' . $token;
                
                setFlashMessage('success', __('reset_email_sent') . ' <br><small>' . $resetLink . '</small>');
            } else {
                setFlashMessage('danger', __('email_not_found'));
            }
        }
    } elseif ($step === 'reset') {
        $newPassword = isset($_POST['password']) ? $_POST['password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        $token = isset($_POST['token']) ? $_POST['token'] : '';
        
        // Validate inputs
        if (empty($newPassword)) {
            setFlashMessage('danger', __('password_required'));
        } elseif (strlen($newPassword) < 8) {
            setFlashMessage('danger', __('password_min_length'));
        } elseif ($newPassword !== $confirmPassword) {
            setFlashMessage('danger', __('passwords_dont_match'));
        } elseif (empty($token)) {
            setFlashMessage('danger', __('invalid_token'));
        } else {
            // Check if token is valid
            $sql = "SELECT user_id FROM password_resets WHERE token = ? AND expires > NOW()";
            $reset = fetchRow($sql, [$token]);
            
            if ($reset) {
                // Hash new password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update user password
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                updateData($sql, [$hashedPassword, $reset['user_id']]);
                
                // Delete used token
                $sql = "DELETE FROM password_resets WHERE token = ?";
                executeQuery($sql, [$token]);
                
                // Log activity
                logActivity($reset['user_id'], 'password_reset');
                
                // Set success message
                setFlashMessage('success', __('password_reset_success'));
                
                // Redirect to login page
                redirect(SITE_URL . '/auth/login.php');
            } else {
                setFlashMessage('danger', __('invalid_or_expired_token'));
            }
        }
    }
}

// Check if token is valid if in reset step
if ($step === 'reset' && $token) {
    $sql = "SELECT user_id FROM password_resets WHERE token = ? AND expires > NOW()";
    $reset = fetchRow($sql, [$token]);
    
    if (!$reset) {
        setFlashMessage('danger', __('invalid_or_expired_token'));
        redirect(SITE_URL . '/auth/reset_password.php');
    }
}

// Include header
include_once('../includes/header.php');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-lock text-primary" style="font-size: 3rem;"></i>
                        <h1 class="h3 mt-3"><?php echo __('reset_password'); ?></h1>
                        <p class="text-muted"><?php echo __('reset_password_description'); ?></p>
                    </div>
                    
                    <?php if ($step === 'request'): ?>
                    <!-- Request reset form -->
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="email" class="form-label"><?php echo __('email'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="example@email.com" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-2"></i> <?php echo __('send_reset_link'); ?>
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="mb-0">
                            <a href="<?php echo SITE_URL; ?>/auth/login.php" class="text-primary">
                                <i class="fas fa-arrow-left me-1"></i> <?php echo __('back_to_login'); ?>
                            </a>
                        </p>
                    </div>
                    
                    <?php elseif ($step === 'reset'): ?>
                    <!-- Reset password form -->
                    <form method="POST" action="">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="mb-3">
                            <label for="password" class="form-label"><?php echo __('new_password'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted"><?php echo __('password_requirements'); ?></small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label"><?php echo __('confirm_password'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i> <?php echo __('reset_password'); ?>
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($step === 'reset'): ?>
<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
    
    // Password validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(e) {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('<?php echo __("passwords_dont_match"); ?>');
            e.preventDefault();
        } else {
            confirmPassword.setCustomValidity('');
        }
    });
    
    confirmPassword.addEventListener('input', function() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('<?php echo __("passwords_dont_match"); ?>');
        } else {
            confirmPassword.setCustomValidity('');
        }
    });
</script>
<?php endif; ?>

<?php
// Include footer
include_once('../includes/footer.php');
?>