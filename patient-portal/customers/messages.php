<?php
require_once '../auth/session.php';
require_patient_login();
require_once '../db_conn.php';

$patient = current_patient();
$customer_id = $patient['customer_id'];

$customer = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$customer->execute([$customer_id]);
$c = $customer->fetch();
if (!$c) {
    unset($_SESSION['patient_id'], $_SESSION['patient_customer_id'], $_SESSION['patient_name'], $_SESSION['patient_username']);
    header('Location: login.php?error=account');
    exit();
}

if (isset($_POST['send_message'])) {
    $msg = trim($_POST['message'] ?? '');
    if ($msg !== '') {
        $conn->prepare("INSERT INTO messages (customer_id, sender, message) VALUES (?, 'customer', ?)")->execute([$customer_id, $msg]);
        header("Location: messages.php");
        exit();
    }
}

// NOTE: deliberately NOT marking anything as read here. The `is_read`
// column on `messages` is already used by the admin side to mean "has the
// ADMIN read the customer's messages" (see customers/view.php). Reusing
// the same column here to mean "has the PATIENT read the admin's
// messages" would corrupt that — a message could get marked read by one
// side's visit and silently stop showing as unread for the other. A
// correct unread-badge system needs a second column (e.g.
// `read_by_customer`) — Phase 2 item, tracked in CHANGES.md. For now,
// this page just shows the full thread without touching read state.
$messages = $conn->prepare("SELECT * FROM messages WHERE customer_id = ? ORDER BY date_sent ASC");
$messages->execute([$customer_id]);
$msgs = $messages->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../partials/pwa-head.php'; ?>
    <title>Messages — DentalPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/style.css" rel="stylesheet">
    <link href="../assets/patient-portal.css" rel="stylesheet">
</head>
<body>
<?php include 'partials/topbar.php'; ?>

<div class="page-content" style="max-width:760px;margin:0 auto;">

    <div class="page-header">
        <div>
            <h1 class="page-title">Messages</h1>
            <p class="page-subtitle">Your conversation with your clinic</p>
        </div>
    </div>

    <div class="card-dp" style="display:flex;flex-direction:column;height:calc(100vh - 220px);min-height:420px;">
        <div class="chat-messages" id="chatMessages">
            <?php if (count($msgs) > 0): ?>
                <?php foreach ($msgs as $m):
                    $mine = $m['sender'] === 'customer';
                ?>
                <div class="chat-bubble <?php echo $mine ? 'me' : 'them'; ?>">
                    <div class="bubble-body"><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
                    <div class="bubble-meta">
                        <?php echo $mine ? 'You' : 'Clinic'; ?>
                        · <?php echo date('M j, h:i A', strtotime($m['date_sent'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;color:var(--gray-400);gap:10px;">
                <i class="bi bi-chat" style="font-size:2.5rem;"></i>
                <p style="font-size:0.875rem;">No messages yet. Say hello to your clinic!</p>
            </div>
            <?php endif; ?>
        </div>

        <form method="POST" class="chat-input-wrap">
            <textarea name="message" class="chat-input auto-resize" placeholder="Type a message..." rows="1" required></textarea>
            <button type="submit" name="send_message" class="chat-send-btn">
                <i class="bi bi-send-fill"></i>
            </button>
        </form>
    </div>
</div>

<script>
const chatMessages = document.getElementById('chatMessages');
if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;

document.querySelectorAll('textarea.auto-resize').forEach(ta => {
    ta.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 200) + 'px';
    });
});
</script>
</body>
</html>
