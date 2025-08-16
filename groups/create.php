<?php
// Require authentication
$requiredRole = 'pilgrim';
require_once '../includes/auth_check.php';

// Set page title
$pageTitle = __('create_group');

// Include header
require_once '../includes/header.php';

// Generate CSRF token
//$csrfToken = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = __('invalid_csrf_token');
    } else {
        // Sanitize input
        $groupName = filter_input(INPUT_POST, 'group_name', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

        // Validate input
        if (empty($groupName)) {
            $error = __('group_name_required');
        } else {
            // Generate unique invite code
            $inviteCode = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);

            // Insert group into database
            $result = executeQuery(
                "INSERT INTO groups (name, description, creator_id, invite_code, created_at) VALUES (?, ?, ?, ?, NOW())",
                [$groupName, $description, $currentUser['id'], $inviteCode]
            );

            if ($result) {
                $groupId = $pdo->lastInsertId();

                // Add creator as a group member
                executeQuery(
                    "INSERT INTO group_members (group_id, user_id, joined_at) VALUES (?, ?, NOW())",
                    [$groupId, $currentUser['id']]
                );

                $success = __('group_created_successfully');
                // Redirect to invite page
                header("Location: invite.php?group_id=$groupId");
                exit;
            } else {
                $error = __('group_creation_failed');
            }
        }
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i> <?php echo __('create_new_group'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <div class="mb-3">
                            <label for="group_name" class="form-label"><?php echo __('group_name'); ?></label>
                            <input type="text" class="form-control" id="group_name" name="group_name" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label"><?php echo __('description'); ?></label>
                            <textarea class="form-control" id="description" name="description" rows="4" maxlength="500"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> <?php echo __('create_group'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>