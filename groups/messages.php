<?php
// Require authentication
$requiredRole = 'pilgrim';
require_once '../includes/auth_check.php';

// Set page title
$pageTitle = __('group_messages');

// Include header
require_once '../includes/header.php';

// Validate group ID
$groupId = filter_input(INPUT_GET, 'group_id', FILTER_VALIDATE_INT);
if (!$groupId) {
    header('Location: ../dashboard/pilgrim.php');
    exit;
}

// Fetch group details
$group = fetchRow("SELECT id, name FROM groups WHERE id = ?", [$groupId]);

if (!$group) {
    $error = __('group_not_found');
} else {
    // Check if user is a member
    $isMember = fetchRow(
        "SELECT id FROM group_members WHERE group_id = ? AND user_id = ?",
        [$groupId, $currentUser['id']]
    );

    if (!$isMember) {
        $error = __('not_group_member');
    } else {
        // Fetch group messages
        $messages = fetchAll("
            SELECT m.id, m.message, m.created_at, u.full_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.group_id = ?
            ORDER BY m.created_at DESC
            LIMIT 50
        ", [$groupId]);
    }
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isMember) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    if ($csrfToken !== $_SESSION['csrf_token']) {
        $error = __('invalid_csrf_token');
    } elseif (empty($message)) {
        $error = __('message_required');
    } else {
        $result = executeQuery(
            "INSERT INTO messages (group_id, sender_id, message, created_at) VALUES (?, ?, ?, NOW())",
            [$groupId, $currentUser['id'], $message]
        );

        if ($result) {
            $success = __('message_sent_successfully');
            header("Location: messages.php?group_id=$groupId");
            exit;
        } else {
            $error = __('message_send_failed');
        }
    }
}
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-comments me-2"></i> <?php echo __('group_messages'); ?> - <?php echo htmlspecialchars($group['name'] ?? ''); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php elseif (isset($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <?php if ($group && $isMember): ?>
                        <!-- Send Message Form -->
                        <form method="POST" action="" class="mb-4">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="mb-3">
                                <label for="message" class="form-label"><?php echo __('your_message'); ?></label>
                                <textarea class="form-control" id="message" name="message" rows="4" required maxlength="1000"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i> <?php echo __('send_message'); ?>
                            </button>
                        </form>
                        <!-- Messages List -->
                        <h6><?php echo __('recent_messages'); ?></h6>
                        <?php if (empty($messages)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-comments text-muted mb-3" style="font-size: 2rem;"></i>
                                <p><?php echo __('no_messages'); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="chat-messages">
                                <?php foreach ($messages as $message): ?>
                                    <div class="message mb-3 p-3 border rounded <?php echo $message['sender_id'] === $currentUser['id'] ? 'bg-light' : ''; ?>">
                                        <div class="d-flex justify-content-between">
                                            <strong><?php echo htmlspecialchars($message['full_name']); ?></strong>
                                            <small class="text-muted"><?php echo formatDate($message['created_at']); ?></small>
                                        </div>
                                        <p class="mb-0"><?php echo htmlspecialchars($message['message']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .chat-messages {
        max-height: 400px;
        overflow-y: auto;
    }
    .message {
        transition: background-color 0.2s;
    }
</style>

<?php
// Include footer
require_once '../includes/footer.php';
?>