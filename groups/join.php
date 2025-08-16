<?php
// Require authentication
$requiredRole = 'pilgrim';
require_once '../includes/auth_check.php';

// Set page title
$pageTitle = __('join_group');

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
        $inviteCode = filter_input(INPUT_POST, 'invite_code', FILTER_SANITIZE_STRING);

        // Validate input
        if (empty($inviteCode)) {
            $error = __('invite_code_required');
        } else {
            // Find group by invite code
            $group = fetchRow("SELECT id FROM groups WHERE invite_code = ?", [$inviteCode]);

            if (!$group) {
                $error = __('invalid_invite_code');
            } else {
                // Check if user is already a member
                $isMember = fetchRow(
                    "SELECT id FROM group_members WHERE group_id = ? AND user_id = ?",
                    [$group['id'], $currentUser['id']]
                );

                if ($isMember) {
                    $error = __('already_group_member');
                } else {
                    // Add user to group
                    $result = executeQuery(
                        "INSERT INTO group_members (group_id, user_id, joined_at) VALUES (?, ?, NOW())",
                        [$group['id'], $currentUser['id']]
                    );

                    if ($result) {
                        $success = __('joined_group_successfully');
                        header("Location: manage.php?group_id={$group['id']}");
                        exit;
                    } else {
                        $error = __('join_group_failed');
                    }
                }
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
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i> <?php echo __('join_group'); ?></h5>
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
                            <label for="invite_code" class="form-label"><?php echo __('invite_code'); ?></label>
                            <input type="text" class="form-control" id="invite_code" name="invite_code" required maxlength="8">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i> <?php echo __('join_group'); ?>
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