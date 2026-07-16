<?php
// FIX: this page was missing the admin auth/session include that every
// other admin page loads. topbar.php calls csrf_field() (for the logout
// form's hidden CSRF token) assuming it's already defined — without this
// include, that function doesn't exist yet and the page fatals before
// rendering anything: "Call to undefined function csrf_field()".
//
// Adjust the path/function name below if your other admin pages
// (dashboard.php, customers/list.php, etc.) use a different filename or
// a different login-guard function name — match whatever they already do.
require_once '../auth/session.php';
require_admin_login();

include "../db_conn.php";

// Get all customers that have messages, with unread count
$conversations = $conn->query("
    SELECT c.id, c.customer_name, c.phone_number,
           COUNT(m.id) as total_msgs,
           SUM(CASE WHEN m.is_read = 0 AND m.sender = 'customer' THEN 1 ELSE 0 END) as unread,
           MAX(m.date_sent) as last_msg_time,
           (SELECT message FROM messages WHERE customer_id = c.id ORDER BY date_sent DESC LIMIT 1) as last_message,
           (SELECT sender FROM messages WHERE customer_id = c.id ORDER BY date_sent DESC LIMIT 1) as last_sender
    FROM customers c
    LEFT JOIN messages m ON m.customer_id = c.id
    GROUP BY c.id
    ORDER BY last_msg_time DESC, c.customer_name ASC
")->fetchAll();

$total_unread = $conn->query("SELECT COUNT(*) FROM messages WHERE is_read = 0 AND sender = 'customer'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f2d4a">
    <title>Messages — DentalPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/style.css" rel="stylesheet">
</head>
<body>
<?php include '../partials/sidebar.php'; ?>
<div class="main-wrap">
<?php include '../partials/topbar.php'; ?>
<div class="page-content">

    <div class="breadcrumb-dp">
        <a href="../index.php">Dashboard</a>
        <i class="bi bi-chevron-right"></i>
        <span>Messages</span>
    </div>

    <div class="page-header">
        <div>
            <h1 class="page-title">Messages</h1>
            <p class="page-subtitle">
                <?php echo $total_unread; ?> unread message<?php echo $total_unread !== 1 ? 's' : ''; ?>
            </p>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:360px 1fr;gap:20px;align-items:start;" class="msg-layout">

        <!-- Conversations List -->
        <div class="card-dp" style="overflow:hidden;">
            <div class="card-header-dp">
                <h3><i class="bi bi-chat-dots-fill"></i> Conversations</h3>
                <?php if($total_unread > 0): ?>
                <span class="status-pill status-pending"><?php echo $total_unread; ?> new</span>
                <?php endif; ?>
            </div>

            <div style="padding:10px;border-bottom:1px solid var(--gray-100);">
                <div style="position:relative;">
                    <i class="bi bi-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:0.85rem;"></i>
                    <input type="text" id="convSearch" placeholder="Search conversations..." 
                           style="width:100%;padding:8px 12px 8px 34px;border:2px solid var(--gray-100);border-radius:8px;font-family:'Outfit',sans-serif;font-size:0.82rem;">
                </div>
            </div>

            <div style="max-height:600px;overflow-y:auto;">
                <?php if(count($conversations) > 0): ?>
                    <?php foreach($conversations as $cv):
                        $parts = explode(' ', $cv['customer_name']);
                        $initials = strtoupper(substr($parts[0],0,1)) . (isset($parts[1]) ? strtoupper(substr($parts[1],0,1)) : '');
                        $hasUnread = $cv['unread'] > 0;
                        $lastTime  = $cv['last_msg_time'] ? date('M j', strtotime($cv['last_msg_time'])) : '';
                    ?>
                    <a href="../customers/view.php?id=<?php echo $cv['id']; ?>#messages" 
                       class="conv-item <?php echo $hasUnread ? 'conv-unread' : ''; ?>"
                       style="display:flex;align-items:center;gap:12px;padding:14px 16px;text-decoration:none;color:inherit;border-bottom:1px solid var(--gray-50);transition:all 0.2s;
                              <?php echo $hasUnread ? 'background:rgba(10,143,143,0.04);' : ''; ?>">
                        <div style="position:relative;flex-shrink:0;">
                            <div class="patient-avatar" style="width:44px;height:44px;"><?php echo $initials; ?></div>
                            <?php if($hasUnread): ?>
                            <span style="position:absolute;top:-2px;right:-2px;width:16px;height:16px;background:var(--teal);border-radius:50%;border:2px solid white;display:flex;align-items:center;justify-content:center;font-size:0.55rem;color:white;font-weight:800;"><?php echo $cv['unread']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px;">
                                <span style="font-weight:<?php echo $hasUnread ? '700' : '600'; ?>;font-size:0.875rem;color:var(--navy);">
                                    <?php echo htmlspecialchars($cv['customer_name']); ?>
                                </span>
                                <span style="font-size:0.68rem;color:var(--gray-400);"><?php echo $lastTime; ?></span>
                            </div>
                            <?php if($cv['last_message']): ?>
                            <div style="font-size:0.78rem;color:var(--gray-400);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;">
                                <?php if($cv['last_sender'] === 'admin'): ?>
                                <span style="color:var(--teal);font-weight:600;">You: </span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars(substr($cv['last_message'],0,60)); ?>
                            </div>
                            <?php else: ?>
                            <div style="font-size:0.78rem;color:var(--gray-200);font-style:italic;">No messages yet</div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="empty-mini" style="padding:40px 20px;">
                    <i class="bi bi-chat" style="font-size:2.5rem;"></i>
                    <p>No conversations yet</p>
                    <a href="../customers/list.php" class="btn-primary-dp" style="font-size:0.82rem;padding:8px 16px;margin-top:8px;">
                        View Patients
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="card-dp" style="display:flex;flex-direction:column;min-height:400px;">
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px;text-align:center;color:var(--gray-400);">
                <div style="width:80px;height:80px;background:var(--gray-50);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:20px;font-size:2.2rem;">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <h3 style="font-family:'DM Serif Display',serif;font-size:1.4rem;font-weight:400;color:var(--navy);margin-bottom:8px;">Select a Conversation</h3>
                <p style="font-size:0.875rem;max-width:280px;line-height:1.6;">
                    Choose a patient from the left to view and continue your conversation.
                </p>
                <div style="margin-top:24px;display:grid;gap:10px;width:100%;max-width:300px;">
                    <?php foreach(array_slice($conversations, 0, 3) as $cv):
                        $parts = explode(' ', $cv['customer_name']);
                        $initials = strtoupper(substr($parts[0],0,1)) . (isset($parts[1]) ? strtoupper(substr($parts[1],0,1)) : '');
                    ?>
                    <a href="../customers/view.php?id=<?php echo $cv['id']; ?>#messages"
                       style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1px solid var(--gray-100);border-radius:10px;text-decoration:none;color:inherit;transition:all 0.2s;background:white;"
                       onmouseover="this.style.borderColor='var(--teal)'" onmouseout="this.style.borderColor='var(--gray-100)'">
                        <div class="patient-avatar" style="width:32px;height:32px;font-size:0.72rem;"><?php echo $initials; ?></div>
                        <div style="flex:1;text-align:left;">
                            <div style="font-weight:600;font-size:0.82rem;color:var(--navy);"><?php echo htmlspecialchars($cv['customer_name']); ?></div>
                        </div>
                        <?php if($cv['unread'] > 0): ?>
                        <span style="background:var(--teal);color:white;font-size:0.65rem;font-weight:700;padding:2px 7px;border-radius:20px;"><?php echo $cv['unread']; ?></span>
                        <?php endif; ?>
                        <i class="bi bi-arrow-right" style="color:var(--gray-400);font-size:0.8rem;"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

</div>
</div>

<style>
.conv-item:hover { background: var(--gray-50) !important; }
@media (max-width: 768px) {
    .msg-layout { grid-template-columns: 1fr !important; }
    .msg-layout > div:last-child { display: none; }
}
</style>

<script src="../assets/app.js"></script>
<script>
document.getElementById('convSearch')?.addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('.conv-item').forEach(item => {
        item.style.display = item.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
});
</script>
</body>
</html>
