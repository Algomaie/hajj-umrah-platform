<?php
// Load required files
require_once('../includes/functions.php');

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    // Save current page for redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    setFlashMessage('warning', __('login_required'));
    redirect(SITE_URL . '/auth/login.php');
    exit;
}

// Set page title
$pageTitle = __('profile');
$bodyClass = 'bg-light';

// Fetch current user data
$userId = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$user = fetchRow($sql, [$userId]);

if (!$user) {
    setFlashMessage('danger', __('user_not_found'));
    redirect(SITE_URL . '/auth/logout.php');
    exit;
}

// Initialize variables for profile update
$errors = [];
$success = false;

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = isset($_POST['form_action']) ? $_POST['form_action'] : '';
    
    // Process personal information update
    if ($formAction === 'update_info') {
        $fullName = isset($_POST['full_name']) ? sanitizeInput($_POST['full_name']) : '';
        $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
        $passportNumber = isset($_POST['passport_number']) ? sanitizeInput($_POST['passport_number']) : '';
        $nationality = isset($_POST['nationality']) ? sanitizeInput($_POST['nationality']) : '';
        $language = isset($_POST['language']) ? sanitizeInput($_POST['language']) : 'en';
        
        // Validate inputs
        if (empty($fullName)) {
            $errors[] = __('full_name_required');
        }
        
        if (empty($email)) {
            $errors[] = __('email_required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('invalid_email');
        } elseif ($email !== $user['email']) {
            // Check if email is already in use by another user
            $checkEmail = fetchRow("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $userId]);
            if ($checkEmail) {
                $errors[] = __('email_exists');
            }
        }
        
        if (empty($phone)) {
            $errors[] = __('phone_required');
        }
        
        if (empty($passportNumber)) {
            $errors[] = __('passport_number_required');
        }
        
        // Update user if no errors
        if (empty($errors)) {
            $sql = "UPDATE users SET 
                    full_name = ?, 
                    email = ?, 
                    phone = ?, 
                    passport_number = ?, 
                    nationality = ?, 
                    language = ? 
                    WHERE id = ?";
                    
            $updated = updateData($sql, [
                $fullName, 
                $email, 
                $phone, 
                $passportNumber, 
                $nationality, 
                $language, 
                $userId
            ]);
            
            if ($updated) {
                // Update session language if changed
                if ($_SESSION['language'] !== $language) {
                    $_SESSION['language'] = $language;
                }
                
                $_SESSION['full_name'] = $fullName;
                
                setFlashMessage('success', __('profile_updated'));
                $success = true;
                
                // Log activity
                logActivity($userId, 'update_profile');
                
                // Refresh user data
                $user = fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);
            } else {
                $errors[] = __('update_failed');
            }
        }
    }
    
    // Process password change
    elseif ($formAction === 'change_password') {
        $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        // Validate password
        if (empty($currentPassword)) {
            $errors[] = __('current_password_required');
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $errors[] = __('current_password_incorrect');
        }
        
        if (empty($newPassword)) {
            $errors[] = __('new_password_required');
        } elseif (strlen($newPassword) < 8) {
            $errors[] = __('password_min_length');
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = __('passwords_dont_match');
        }
        
        // Update password if no errors
        if (empty($errors)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $updated = updateData($sql, [$hashedPassword, $userId]);
            
            if ($updated) {
                setFlashMessage('success', __('password_updated'));
                $success = true;
                
                // Log activity
                logActivity($userId, 'change_password');
            } else {
                $errors[] = __('update_failed');
            }
        }
    }
    
    // Process profile image update
    elseif ($formAction === 'update_image') {
        // Check if a file was uploaded
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['size'] > 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_image']['name'];
            $tmp_name = $_FILES['profile_image']['tmp_name'];
            $size = $_FILES['profile_image']['size'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                $errors[] = __('invalid_image_format');
            } elseif ($size > 2097152) { // 2MB
                $errors[] = __('image_too_large');
            } else {
                // Create uploads directory if it doesn't exist
                $uploadsDir = '../uploads/profiles/';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }
                
                // Delete old profile image if exists
                if (!empty($user['profile_image']) && file_exists($uploadsDir . $user['profile_image'])) {
                    unlink($uploadsDir . $user['profile_image']);
                }
                
                $newFilename = uniqid('user_') . '.' . $ext;
                $uploadPath = $uploadsDir . $newFilename;
                
                if (move_uploaded_file($tmp_name, $uploadPath)) {
                    $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
                    $updated = updateData($sql, [$newFilename, $userId]);
                    
                    if ($updated) {
                        setFlashMessage('success', __('profile_image_updated'));
                        $success = true;
                        
                        // Log activity
                        logActivity($userId, 'update_profile_image');
                        
                        // Refresh user data
                        $user = fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);
                    } else {
                        $errors[] = __('update_failed');
                    }
                } else {
                    $errors[] = __('image_upload_failed');
                }
            }
        } else {
            $errors[] = __('no_image_selected');
        }
    }
    
    // Display errors
    if (!empty($errors)) {
        setFlashMessage('danger', implode('<br>', $errors));
    }
    
    // Redirect to prevent form resubmission if successful
    if ($success) {
        redirect(SITE_URL . '/auth/profile.php');
        exit;
    }
}

// Include header
include_once('../includes/header.php');
?>

<div class="container py-5">
    <div class="row">
        <!-- Profile Sidebar -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <?php if (!empty($user['profile_image'])): ?>
                        <img src="<?php echo SITE_URL; ?>/uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="<?php echo htmlspecialchars($user['full_name']); ?>" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 150px; height: 150px;">
                            <i class="fas fa-user text-secondary" style="font-size: 5rem;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <h3 class="h4 mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                    <p class="text-muted mb-3">@<?php echo htmlspecialchars($user['username']); ?></p>
                    
                    <p>
                        <span class="badge bg-primary"><?php echo __($user['user_type']); ?></span>
                        <span class="badge bg-secondary"><?php echo $user['language'] === 'ar' ? 'العربية' : 'English'; ?></span>
                    </p>
                    
                    <hr>
                    
                    <form method="POST" action="" enctype="multipart/form-data" class="text-start">
                        <input type="hidden" name="form_action" value="update_image">
                        
                        <div class="mb-3">
                            <label for="profile_image" class="form-label"><?php echo __('change_profile_image'); ?></label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*" required>
                            <small class="form-text text-muted"><?php echo __('profile_image_help'); ?></small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-camera me-2"></i> <?php echo __('upload_image'); ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-3"><?php echo __('account_info'); ?></h5>
                    
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-envelope text-primary me-2"></i> <?php echo htmlspecialchars($user['email']); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-phone text-primary me-2"></i> <?php echo htmlspecialchars($user['phone']); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-passport text-primary me-2"></i> <?php echo htmlspecialchars($user['passport_number']); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-flag text-primary me-2"></i> <?php echo htmlspecialchars($user['nationality']); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-calendar text-primary me-2"></i> <?php echo __('created'); ?>: <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                        </li>
                        <?php if ($user['last_login']): ?>
                        <li>
                            <i class="fas fa-sign-in-alt text-primary me-2"></i> <?php echo __('last_login'); ?>: <?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Profile Edit Forms -->
        <div class="col-md-8">
            <!-- Flash Messages -->
            <?php if (hasFlashMessage()): ?>
                <div class="alert alert-<?php echo getFlashMessageType(); ?> alert-dismissible fade show mb-4">
                    <?php echo getFlashMessage(); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Personal Information Form -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i> <?php echo __('personal_information'); ?></h5>
                </div>
                
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="form_action" value="update_info">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label"><?php echo __('full_name'); ?> *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label"><?php echo __('email'); ?> *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label"><?php echo __('phone'); ?> *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="passport_number" class="form-label"><?php echo __('passport_number'); ?> *</label>
                                <input type="text" class="form-control" id="passport_number" name="passport_number" value="<?php echo htmlspecialchars($user['passport_number']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nationality" class="form-label"><?php echo __('nationality'); ?></label>
                                <select class="form-select" id="nationality" name="nationality">
                                    <option value=""><?php echo __('select_nationality'); ?></option>
                                    <option value="Saudi Arabia" <?php echo $user['nationality'] === 'Saudi Arabia' ? 'selected' : ''; ?>>Saudi Arabia</option>
                                    <option value="Egypt" <?php echo $user['nationality'] === 'Egypt' ? 'selected' : ''; ?>>Egypt</option>
                                    <option value="Pakistan" <?php echo $user['nationality'] === 'Pakistan' ? 'selected' : ''; ?>>Pakistan</option>
                                    <option value="India" <?php echo $user['nationality'] === 'India' ? 'selected' : ''; ?>>India</option>
                                    <option value="Indonesia" <?php echo $user['nationality'] === 'Indonesia' ? 'selected' : ''; ?>>Indonesia</option>
                                    <option value="Malaysia" <?php echo $user['nationality'] === 'Malaysia' ? 'selected' : ''; ?>>Malaysia</option>
                                    <option value="Turkey" <?php echo $user['nationality'] === 'Turkey' ? 'selected' : ''; ?>>Turkey</option>
                                    <option value="United States" <?php echo $user['nationality'] === 'United States' ? 'selected' : ''; ?>>United States</option>
                                    <option value="United Kingdom" <?php echo $user['nationality'] === 'United Kingdom' ? 'selected' : ''; ?>>United Kingdom</option>
                                    <option value="Other" <?php echo $user['nationality'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="language" class="form-label"><?php echo __('preferred_language'); ?></label>
                                <select class="form-select" id="language" name="language">
                                    <option value="en" <?php echo $user['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="ar" <?php echo $user['language'] === 'ar' ? 'selected' : ''; ?>>العربية</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> <?php echo __('save_changes'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password Form -->
            <div class="card shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="fas fa-lock me-2"></i> <?php echo __('change_password'); ?></h5>
                </div>
                
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="form_action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label"><?php echo __('current_password'); ?> *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="current_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label"><?php echo __('new_password'); ?> *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted"><?php echo __('password_requirements'); ?></small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label"><?php echo __('confirm_password'); ?> *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key me-2"></i> <?php echo __('update_password'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
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
    });
    
    // Preview profile image before upload
    document.getElementById('profile_image').addEventListener('change', function(e) {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                // This would be where you display a preview, but we're just logging for now
                console.log('Image selected for upload');
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Password confirmation validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    confirmPassword.addEventListener('input', function() {
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('<?php echo __("passwords_dont_match"); ?>');
        } else {
            confirmPassword.setCustomValidity('');
        }
    });
    
    // Form validation before submission
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (this.querySelector('[name="form_action"]').value === 'change_password') {
                if (newPassword.value !== confirmPassword.value) {
                    e.preventDefault();
                    confirmPassword.setCustomValidity('<?php echo __("passwords_dont_match"); ?>');
                    confirmPassword.reportValidity();
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
        });
    });
</script>

<?php
// Include footer
include_once('../includes/footer.php');
?>