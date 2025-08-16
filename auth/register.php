<?php
// Include required files and start session
require_once '../includes/functions.php';
startSession();

// Set page title and body class
$pageTitle = __('register');
$bodyClass = 'bg-light';

// Check if user is already logged in
if (isLoggedIn()) {
    redirectToDashboard();
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlashMessage('danger', __('invalid_csrf_token'));
        redirect(SITE_URL . '/auth/register.php');
        exit;
    }

    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $fullName = isset($_POST['full_name']) ? sanitizeInput($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
    $passportNumber = isset($_POST['passport_number']) ? sanitizeInput($_POST['passport_number']) : '';
    $nationality = isset($_POST['nationality']) ? sanitizeInput($_POST['nationality']) : '';
    $userType = isset($_POST['user_type']) && in_array($_POST['user_type'], ['pilgrim', 'guardian', 'authority']) ? sanitizeInput($_POST['user_type']) : 'pilgrim';
    $language = isset($_POST['language']) && in_array($_POST['language'], ['en', 'ar']) ? sanitizeInput($_POST['language']) : 'en';
    
    // Validate inputs
    $errors = [];
    
    if (empty($username)) {
        $errors[] = __('username_required');
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = __('invalid_username_format');
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";
        if (fetchRow($sql, [$username])) {
            $errors[] = __('username_exists');
        }
    }
    
    if (empty($fullName)) {
        $errors[] = __('full_name_required');
    }
    
    if (empty($email)) {
        $errors[] = __('email_required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('invalid_email');
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";
        if (fetchRow($sql, [$email])) {
            $errors[] = __('email_exists');
        }
    }
    
    if (empty($password)) {
        $errors[] = __('password_required');
    } elseif (strlen($password) < 8) {
        $errors[] = __('password_min_length');
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = __('passwords_dont_match');
    }
    
    if (empty($phone)) {
        $errors[] = __('phone_required');
    } elseif (!preg_match('/^\+?[0-9]{8,15}$/', $phone)) {
        $errors[] = __('invalid_phone_format');
    }
    
    if (empty($passportNumber)) {
        $errors[] = __('passport_number_required');
    } elseif (!preg_match('/^[A-Z0-9]{6,20}$/', $passportNumber)) {
        $errors[] = __('invalid_passport_format');
    }
    
    if (empty($nationality)) {
        $errors[] = __('nationality_required');
    }
    
    // Profile image handling
    $profileImage = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['size'] > 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_image']['name'];
        $tmp_name = $_FILES['profile_image']['tmp_name'];
        $size = $_FILES['profile_image']['size'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $errors[] = __('invalid_image_format');
        } elseif ($size > 2097152) {
            $errors[] = __('image_too_large');
        } else {
            $uploadsDir = '../uploads/profiles/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            
            $newFilename = uniqid('user_') . '.' . $ext;
            $uploadPath = $uploadsDir . $newFilename;
            
            if (!move_uploaded_file($tmp_name, $uploadPath)) {
                $errors[] = __('image_upload_failed');
            } else {
                $profileImage = $newFilename;
            }
        }
    }
    
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, password, email, full_name, phone, passport_number, nationality, user_type, language, created_at, profile_image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
        
        $userId = insertData($sql, [
            $username, $hashedPassword, $email, $fullName, $phone,
            $passportNumber, $nationality, $userType, $language, $profileImage
        ]);
        
        if ($userId) {
            logActivity($userId, 'register');
            
            // Automatically log in the user
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_type'] = $userType;
            $_SESSION['username'] = $username;
            $_SESSION['language'] = $language;
            $_SESSION['full_name'] = $fullName;
            
            setFlashMessage('success', __('registration_success'));
            redirectToDashboard();
        } else {
            setFlashMessage('danger', __('registration_failed'));
        }
    } else {
        $_SESSION['form_data'] = [
            'username' => $username, 'full_name' => $fullName, 'email' => $email,
            'phone' => $phone, 'passport_number' => $passportNumber, 'nationality' => $nationality,
            'user_type' => $userType, 'language' => $language
        ];
        
        setFlashMessage('danger', implode('<br>', $errors));
    }
}

// Redirect to dashboard based on user type
function redirectToDashboard() {
    $userType = $_SESSION['user_type'] ?? 'pilgrim';
    $redirect = $userType === 'admin' 
        ? SITE_URL . '/admin/dashboard.php' 
        : SITE_URL . '/solash.php';
    redirect($redirect);
    exit;
}

// Include header
include_once '../includes/header.php';

// Get form data from session if exists
$formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_data']);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus text-primary" style="font-size: 3rem;"></i>
                        <h1 class="h3 mt-3"><?php echo __('register'); ?></h1>
                        <p class="text-muted"><?php echo __('register_description'); ?></p>
                    </div>
                    
                    <!-- Display flash message from sessionStorage or server-side -->
                    <?php if (hasFlashMessage('danger') || hasFlashMessage('success')): ?>
                        <div class="alert alert-<?php echo hasFlashMessage('danger') ? 'danger' : 'success'; ?> alert-dismissible fade show mb-4">
                            <?php echo getFlashMessage('danger') ?: getFlashMessage('success'); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('close'); ?>"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        
                        <!-- Account Information Section -->
                        <h5 class="mb-3"><?php echo __('account_information'); ?></h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label"><?php echo __('username'); ?> *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo isset($formData['username']) ? htmlspecialchars($formData['username']) : ''; ?>" required>
                                <small class="form-text text-muted"><?php echo __('username_help'); ?></small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label"><?php echo __('email'); ?> *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($formData['email']) ? htmlspecialchars($formData['email']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label"><?php echo __('password'); ?> *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted"><?php echo __('password_requirements'); ?></small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label"><?php echo __('confirm_password'); ?> *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <!-- Personal Information Section -->
                        <h5 class="mb-3 mt-4"><?php echo __('personal_information'); ?></h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label"><?php echo __('full_name'); ?> *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo isset($formData['full_name']) ? htmlspecialchars($formData['full_name']) : ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label"><?php echo __('phone'); ?> *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo isset($formData['full_name']) ? htmlspecialchars($formData['phone']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="passport_number" class="form-label"><?php echo __('passport_number'); ?> *</label>
                                <input type="text" class="form-control" id="passport_number" name="passport_number" 
                                       value="<?php echo isset($formData['passport_number']) ? htmlspecialchars($formData['passport_number']) : ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="nationality" class="form-label"><?php echo __('nationality'); ?> *</label>
                                <select class="form-select" id="nationality" name="nationality" required>
                                    <option value=""><?php echo __('select_nationality'); ?></option>
                                    <option value="Saudi Arabia" <?php echo (isset($formData['nationality']) && $formData['nationality'] == 'Saudi Arabia') ? 'selected' : ''; ?>>Saudi Arabia</option>
                                    <option value="Egypt" <?php echo (isset($formData['nationality']) && $formData['nationality'] == 'Egypt') ? 'selected' : ''; ?>>Egypt</option>
                                    <option value="Pakistan" <?php echo (isset($formData['nationality']) && $formData['nationality'] == 'Pakistan') ? 'selected' : ''; ?>>Pakistan</option>
                                    <option value="India" <?php echo (isset($formData['nationality']) && $formData['nationality'] == 'India') ? 'selected' : ''; ?>>India</option>
                                    <option value="Indonesia" <?php echo (isset($formData['nationality']) && $formData['nationality'] == 'Indonesia') ? 'selected' : ''; ?>>Indonesia</option>
                                    <option value="Malaysia" <?php echo (isset($formData['nationality']) && $formData['nationality'] == 'Malaysia') ? 'selected' : ''; ?>>Malaysia</option>
                                    <option value="Turkey" <?php echo (isset($formData['nationality']) && $formData['nationality'] == 'Turkey') ? 'selected' : ''; ?>>Turkey</option>
                                    <option value="United States" <?php echo (isset($formData['nationality']) && $formData['nationality'] == 'United States') ? 'selected' : ''; ?>>United States</option>
                                    <option value="United Kingdom" <?php echo (isset($formData['nationality']) && $formData['nationality'] == 'United Kingdom') ? 'selected' : ''; ?>>United Kingdom</option>
                                    <option value="Other" <?php echo (isset($formData['nationality']) && $formData['nationality'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label d-block"><?php echo __('user_type'); ?> *</label>
                                <div class="btn-group w-100" role="group" aria-label="<?php echo __('user_type_selection'); ?>">
                                    <?php $currentUserType = isset($formData['user_type']) ? $formData['user_type'] : 'pilgrim'; ?>
                                    <input type="radio" class="btn-check" name="user_type" id="user_type_pilgrim" value="pilgrim" 
                                           <?php echo $currentUserType === 'pilgrim' ? 'checked' : ''; ?> required>
                                    <label class="btn btn-outline-primary" for="user_type_pilgrim">
                                        <i class="fas fa-kaaba me-2"></i><?php echo __('pilgrim'); ?>
                                    </label>
                                    <input type="radio" class="btn-check" name="user_type" id="user_type_guardian" value="guardian" 
                                           <?php echo $currentUserType === 'guardian' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="user_type_guardian">
                                        <i class="fas fa-user-shield me-2"></i><?php echo __('guardian'); ?>
                                    </label>
                                    <input type="radio" class="btn-check" name="user_type" id="user_type_authority" value="authority" 
                                           <?php echo $currentUserType === 'authority' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="user_type_authority">
                                        <i class="fas fa-user-tie me-2"></i><?php echo __('authority'); ?>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="language" class="form-label"><?php echo __('preferred_language'); ?></label>
                                <select class="form-select" id="language" name="language">
                                    <option value="en" <?php echo !isset($formData['language']) || $formData['language'] == 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="ar" <?php echo isset($formData['language']) && $formData['language'] == 'ar' ? 'selected' : ''; ?>>العربية</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="profile_image" class="form-label"><?php echo __('profile_image'); ?></label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                            <small class="form-text text-muted"><?php echo __('profile_image_help'); ?></small>
                            <div id="imagePreview" class="mt-2"></div>
                        </div>
                        
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="agree_terms" required>
                            <label class="form-check-label" for="agree_terms">
                                <?php echo __('agree_terms'); ?>
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-user-plus me-2"></i><?php echo __('register_button'); ?>
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="mb-0"><?php echo __('already_have_account'); ?> 
                            <a href="<?php echo SITE_URL; ?>/auth/login.php" class="text-primary"><?php echo __('login_now'); ?></a>
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
        passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });

    // Password validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const form = document.querySelector('form');
    
    confirmPassword.addEventListener('input', function() {
        confirmPassword.setCustomValidity(
            password.value !== confirmPassword.value ? '<?php echo __("passwords_dont_match"); ?>' : ''
        );
    });

    // Preview profile image
    document.getElementById('profile_image').addEventListener('change', function(e) {
        const file = this.files[0];
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const img = document.createElement('img');
                img.src = event.target.result;
                img.className = 'img-thumbnail';
                img.style.maxWidth = '150px';
                preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        }
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