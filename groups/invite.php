<?php
// Require authentication
$requiredRole = 'pilgrim';
require_once '../includes/auth_check.php';

// Set page title
$pageTitle = __('invite_members');

// Include header
require_once '../includes/header.php';

// Validate group ID
$groupId = filter_input(INPUT_GET, 'group_id', FILTER_VALIDATE_INT);
if (!$groupId) {
    header('Location: manage.php');
    exit;
}

// Fetch group details (only if user is the creator)
$group = fetchRow(
    "SELECT id, name, invite_code FROM groups WHERE id = ? AND creator_id = ?",
    [$groupId, $currentUser['id']]
);

if (!$group) {
    $error = __('group_not_found_or_unauthorized');
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i> <?php echo __('invite_to_group'); ?> - <?php echo htmlspecialchars($group['name'] ?? ''); ?></h5>
                </div>
                <div class="card-body text-center">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php elseif ($group): ?>
                        <p><?php echo __('share_invite_code'); ?></p>
                        <h3 class="my-4"><?php echo htmlspecialchars($group['invite_code']); ?></h3>
                        <button class="btn btn-primary" onclick="copyInviteCode('<?php echo htmlspecialchars($group['invite_code']); ?>')">
                            <i class="fas fa-copy me-2"></i> <?php echo __('copy_code'); ?>
                        </button>
                        <a href="manage.php?group_id=<?php echo $groupId; ?>" class="btn btn-secondary mt-3">
                            <i class="fas fa-users me-2"></i> <?php echo __('manage_group'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyInviteCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        alert('<?php echo __('code_copied'); ?>');
    }).catch(() => {
        alert('<?php echo __('copy_failed'); ?>');
    });
}
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>