<?php
require_once '../auth/session.php';
require_admin_login();
include "../db_conn.php";

if(!isset($_GET['id'])) { header("Location: list.php"); exit(); }
$id = (int)$_GET['id'];

$customer = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$customer->execute([$id]);
$c = $customer->fetch();
if(!$c) { header("Location: list.php"); exit(); }

$service = $conn->prepare("SELECT * FROM dental_services WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
$service->execute([$id]);
$s = $service->fetch();

$appointments = $conn->prepare("SELECT * FROM appointments WHERE customer_id = ? ORDER BY appointment_date DESC, appointment_time DESC");
$appointments->execute([$id]);
$appts = $appointments->fetchAll();

$messages = $conn->prepare("SELECT * FROM messages WHERE customer_id = ? ORDER BY date_sent ASC");
$messages->execute([$id]);
$msgs = $messages->fetchAll();

// Mark customer messages as read
$conn->prepare("UPDATE messages SET is_read = 1 WHERE customer_id = ? AND sender = 'customer'")->execute([$id]);

// Patient portal account (if any) for this customer
$portal_stmt = $conn->prepare("SELECT * FROM patient_portal_users WHERE customer_id = ?");
$portal_stmt->execute([$id]);
$portal_acct = $portal_stmt->fetch();

// One-time credential reveal: read once, then destroy immediately so a
// page refresh or later visit never re-shows the plaintext password.
$revealed_creds = null;
if (!empty($_SESSION['portal_credentials'])) {
    $revealed_creds = $_SESSION['portal_credentials'];
    unset($_SESSION['portal_credentials']);
}

$parts = explode(' ', $c['customer_name']);
$initials = strtoupper(substr($parts[0],0,1)) . (isset($parts[1]) ? strtoupper(substr($parts[1],0,1)) : '');

$bal = ($s['total_bill'] ?? 0) - ($s['amount_paid'] ?? 0);
$ps  = $s['payment_status'] ?? 'pending';

// Handle send message
if(isset($_POST['send_message'])) {
    $msg = trim($_POST['message']);
    if(!empty($msg)) {
        $conn->prepare("INSERT INTO messages (customer_id, sender, message) VALUES (?, 'admin', ?)")->execute([$id, $msg]);
        header("Location: view.php?id=$id#messages");
        exit();
    }
}

// Handle mark appointment done
if(isset($_GET['mark_done'])) {
    $appt_id = (int)$_GET['mark_done'];
    $conn->prepare("UPDATE appointments SET status = 'done' WHERE id = ? AND customer_id = ?")->execute([$appt_id, $id]);
    header("Location: view.php?id=$id&msg=" . urlencode("Appointment marked as done!"));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f2d4a">
    <title><?php echo htmlspecialchars($c['customer_name']); ?> — DentalPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/style.css" rel="stylesheet">
    <link href="../assets/odontogram.css" rel="stylesheet">
</head>
<body>
<?php include '../partials/sidebar.php'; ?>
<div class="main-wrap">
<?php include '../partials/topbar.php'; ?>
<div class="page-content">

    <div class="breadcrumb-dp">
        <a href="../index.php">Dashboard</a>
        <i class="bi bi-chevron-right"></i>
        <a href="list.php">Patients</a>
        <i class="bi bi-chevron-right"></i>
        <span><?php echo htmlspecialchars($c['customer_name']); ?></span>
    </div>

    <!-- Customer Hero -->
    <div class="customer-hero">
        <div class="customer-hero-avatar"><?php echo $initials; ?></div>
        <div class="customer-hero-info">
            <div class="customer-hero-name"><?php echo htmlspecialchars($c['customer_name']); ?></div>
            <div class="customer-hero-meta">
                <span><i class="bi bi-telephone-fill"></i> <?php echo htmlspecialchars($c['phone_number']); ?></span>
                <?php if($c['email']): ?>
                <span><i class="bi bi-envelope-fill"></i> <?php echo htmlspecialchars($c['email']); ?></span>
                <?php endif; ?>
                <span><i class="bi bi-calendar3"></i> Since <?php echo date('M j, Y', strtotime($c['date_created'])); ?></span>
            </div>
        </div>
        <div class="customer-hero-actions">
            <a href="edit.php?id=<?php echo $id; ?>" class="btn-outline-dp" style="border-color:rgba(255,255,255,0.3);color:white;">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="../appointments/create.php?customer_id=<?php echo $id; ?>" class="btn-primary-dp">
                <i class="bi bi-calendar-plus"></i> Schedule
            </a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start;" class="view-layout">

        <!-- LEFT COLUMN -->
        <div>

            <!-- Service Card -->
            <?php if($s): ?>
            <div class="service-card">
                <div class="service-card-header">
                    <div class="service-card-title">
                        <i class="bi bi-tooth"></i>
                        Dental Service Record
                    </div>
                    <span style="font-size:0.72rem;opacity:0.7;">Added <?php echo date('M j, Y', strtotime($s['date_created'])); ?></span>
                </div>

                <!-- Tooth Count Visual -->
                <div class="service-tooth-info">
                    <div class="tooth-block">
                        <div class="tooth-block-label"><i class="bi bi-arrow-up"></i> Upper Teeth</div>
                        <div class="tooth-block-value"><?php echo $s['tooth_upper']; ?></div>
                        <div class="tooth-block-sub">tooth/teeth</div>
                    </div>
                    <div class="tooth-block">
                        <div class="tooth-block-label"><i class="bi bi-arrow-down"></i> Lower Teeth</div>
                        <div class="tooth-block-value"><?php echo $s['tooth_lower']; ?></div>
                        <div class="tooth-block-sub">tooth/teeth</div>
                    </div>
                </div>

                <?php if(!empty($s['teeth_data'])): ?>
                <div class="service-spec-row" style="flex-direction:column;align-items:stretch;gap:10px;">
                    <span class="spec-label"><i class="bi bi-grid-3x3-gap-fill"></i> Tooth Chart</span>
                    <div id="viewOdontogram"></div>
                    <div id="viewOdontogramDetails"></div>
                </div>
                <?php endif; ?>

                <!-- Specifications -->
                <?php if($s['tooth_shade'] || $s['tooth_size']): ?>
                <div class="service-spec-row">
                    <span class="spec-label"><i class="bi bi-palette"></i> Overall Spec</span>
                    <?php if($s['tooth_shade']): ?>
                    <span class="spec-tag">Shade: <?php echo htmlspecialchars($s['tooth_shade']); ?></span>
                    <?php endif; ?>
                    <?php if($s['tooth_size']): ?>
                    <span class="spec-tag" style="margin-left:6px;">Size: <?php echo htmlspecialchars($s['tooth_size']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if($s['description']): ?>
                <div class="service-spec-row" style="flex-direction:column;align-items:flex-start;gap:6px;">
                    <span class="spec-label"><i class="bi bi-card-text"></i> Service Notes</span>
                    <span style="font-size:0.875rem;color:var(--gray-600);line-height:1.6;"><?php echo nl2br(htmlspecialchars($s['description'])); ?></span>
                </div>
                <?php endif; ?>

                <!-- Billing -->
                <div class="billing-row">
                    <span class="billing-label"><i class="bi bi-receipt" style="color:var(--teal);margin-right:6px;"></i> Total Bill</span>
                    <span class="billing-amount">₱<?php echo number_format($s['total_bill'], 2); ?></span>
                </div>
                <div class="billing-row">
                    <span class="billing-label"><i class="bi bi-wallet2" style="color:var(--success);margin-right:6px;"></i> Amount Paid</span>
                    <span class="billing-amount" style="color:var(--success);">₱<?php echo number_format($s['amount_paid'], 2); ?></span>
                </div>
                <div class="billing-row">
                    <span class="billing-label"><i class="bi bi-calculator" style="color:var(--warning);margin-right:6px;"></i> Balance</span>
                    <span class="billing-amount <?php echo $bal > 0 ? 'billing-balance' : 'billing-paid-full'; ?>">
                        <?php echo $bal > 0 ? '₱' . number_format($bal, 2) : 'FULLY PAID ✓'; ?>
                    </span>
                </div>
                <div class="billing-row" style="background:var(--gray-50);">
                    <span class="billing-label">Status</span>
                    <span class="status-pill status-<?php echo $ps; ?>"><?php echo ucfirst($ps); ?></span>
                </div>

                <div style="padding:14px 22px;display:flex;gap:10px;">
                    <a href="edit.php?id=<?php echo $id; ?>" class="btn-outline-dp" style="font-size:0.82rem;padding:8px 16px;">
                        <i class="bi bi-pencil"></i> Edit Service
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="card-dp">
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="bi bi-tooth"></i></div>
                    <h3>No Service Record</h3>
                    <p>Add dental service details for this patient</p>
                    <a href="edit.php?id=<?php echo $id; ?>" class="btn-primary-dp">Add Service Details</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Patient Portal Access -->
            <div class="card-dp" style="margin-top:24px;">
                <div class="service-card-header" style="background:var(--navy);">
                    <div class="service-card-title">
                        <i class="bi bi-shield-lock"></i> Patient Portal Access
                    </div>
                </div>

                <div style="padding:18px 22px;">

                    <?php if ($revealed_creds): ?>
                    <div class="alert-dp" style="background:rgba(212,168,67,0.1);border:1px solid rgba(212,168,67,0.3);color:#8a6b1f;margin-bottom:16px;">
                        <i class="bi bi-key-fill"></i>
                        <strong>New credentials — write these down now, they won't be shown again:</strong><br>
                        Username: <strong><?php echo htmlspecialchars($revealed_creds['username']); ?></strong><br>
                        Password: <strong><?php echo htmlspecialchars($revealed_creds['password']); ?></strong>
                    </div>
                    <?php endif; ?>

                    <?php if (!$portal_acct): ?>
                        <p style="font-size:0.875rem;color:var(--gray-400);margin-bottom:14px;">
                            This patient doesn't have portal access yet.
                        </p>
                        <form method="POST" action="portal_access.php">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="customer_id" value="<?php echo $id; ?>">
                            <input type="hidden" name="action" value="create">
                            <button type="submit" class="btn-primary-dp" style="font-size:0.82rem;padding:8px 16px;">
                                <i class="bi bi-person-plus-fill"></i> Create Portal Access
                            </button>
                        </form>

                    <?php else: ?>
                        <div class="billing-row">
                            <span class="billing-label">Username</span>
                            <span class="billing-amount" style="font-size:1rem;"><?php echo htmlspecialchars($portal_acct['username']); ?></span>
                        </div>
                        <div class="billing-row">
                            <span class="billing-label">Status</span>
                            <span class="status-pill status-<?php echo $portal_acct['is_active'] ? 'paid' : 'pending'; ?>">
                                <?php echo $portal_acct['is_active'] ? 'Active' : 'Disabled'; ?>
                            </span>
                        </div>
                        <?php if ($portal_acct['last_login']): ?>
                        <div class="billing-row">
                            <span class="billing-label">Last Login</span>
                            <span class="billing-amount" style="font-size:0.85rem;"><?php echo date('M j, Y h:i A', strtotime($portal_acct['last_login'])); ?></span>
                        </div>
                        <?php endif; ?>

                        <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;">
                            <form method="POST" action="portal_access.php" onsubmit="return confirm('Reset password? The current password will stop working immediately.');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="customer_id" value="<?php echo $id; ?>">
                                <input type="hidden" name="action" value="reset">
                                <button type="submit" class="btn-outline-dp" style="font-size:0.82rem;padding:8px 16px;">
                                    <i class="bi bi-arrow-repeat"></i> Reset Password
                                </button>
                            </form>

                            <?php if ($portal_acct['is_active']): ?>
                            <form method="POST" action="portal_access.php" onsubmit="return confirm('Disable portal access for this patient?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="customer_id" value="<?php echo $id; ?>">
                                <input type="hidden" name="action" value="deactivate">
                                <button type="submit" class="btn-danger-dp" style="font-size:0.82rem;padding:8px 16px;">
                                    <i class="bi bi-slash-circle"></i> Disable Access
                                </button>
                            </form>
                            <?php else: ?>
                            <form method="POST" action="portal_access.php">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="customer_id" value="<?php echo $id; ?>">
                                <input type="hidden" name="action" value="reactivate">
                                <button type="submit" class="btn-success-dp" style="font-size:0.82rem;padding:8px 16px;">
                                    <i class="bi bi-check-circle"></i> Re-enable Access
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Appointments -->
            <div style="margin-top:24px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                    <h2 style="font-family:'DM Serif Display',serif;font-size:1.3rem;font-weight:400;color:var(--navy);">
                        <i class="bi bi-calendar3" style="color:var(--teal);margin-right:8px;font-size:1rem;"></i>
                        Appointments
                    </h2>
                    <a href="../appointments/create.php?customer_id=<?php echo $id; ?>" class="btn-primary-dp" style="padding:8px 16px;font-size:0.82rem;">
                        <i class="bi bi-calendar-plus"></i> Schedule
                    </a>
                </div>

                <?php if(count($appts) > 0): ?>
                    <?php foreach($appts as $a): 
                        $appt_date = new DateTime($a['appointment_date']);
                    ?>
                    <div class="appt-card">
                        <div class="appt-date-block">
                            <div class="appt-date-day"><?php echo $appt_date->format('d'); ?></div>
                            <div class="appt-date-mon"><?php echo $appt_date->format('M'); ?></div>
                            <div class="appt-date-yr"><?php echo $appt_date->format('Y'); ?></div>
                        </div>
                        <div class="appt-card-divider"></div>
                        <div class="appt-card-info">
                            <div class="appt-card-type"><?php echo ucfirst(str_replace('_',' ',$a['appointment_type'])); ?></div>
                            <div class="appt-card-time">
                                <i class="bi bi-clock"></i>
                                <?php echo date('h:i A', strtotime($a['appointment_time'])); ?>
                            </div>
                            <?php if($a['notes']): ?>
                            <div class="appt-card-note"><?php echo htmlspecialchars($a['notes']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="appt-card-actions" style="flex-direction:column;gap:6px;align-items:flex-end;">
                            <span class="status-pill status-<?php echo $a['status']; ?>"><?php echo ucfirst($a['status']); ?></span>
                            <?php if($a['status'] === 'scheduled'): ?>
                            <a href="view.php?id=<?php echo $id; ?>&mark_done=<?php echo $a['id']; ?>" 
                               class="btn-success-dp" style="font-size:0.75rem;padding:5px 12px;">
                                <i class="bi bi-check2"></i> Mark Done
                            </a>
                            <?php endif; ?>
                            <a href="../appointments/delete.php?id=<?php echo $a['id']; ?>&redirect=<?php echo $id; ?>" 
                               class="btn-danger-dp confirm-delete" style="font-size:0.75rem;padding:5px 12px;">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="card-dp">
                    <div class="empty-state" style="padding:32px;">
                        <div class="empty-state-icon" style="width:56px;height:56px;font-size:1.6rem;"><i class="bi bi-calendar-x"></i></div>
                        <h3 style="font-size:1rem;">No Appointments</h3>
                        <p style="font-size:0.82rem;">Schedule the first appointment for this patient</p>
                        <a href="../appointments/create.php?customer_id=<?php echo $id; ?>" class="btn-primary-dp" style="font-size:0.82rem;padding:8px 16px;">
                            <i class="bi bi-calendar-plus"></i> Schedule
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- RIGHT COLUMN — Messages -->
        <div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <h2 style="font-family:'DM Serif Display',serif;font-size:1.3rem;font-weight:400;color:var(--navy);" id="messages">
                    <i class="bi bi-chat-dots-fill" style="color:var(--teal);margin-right:8px;font-size:1rem;"></i>
                    Conversation
                </h2>
            </div>

            <div class="card-dp" style="display:flex;flex-direction:column;height:600px;">
                <div class="chat-messages" id="chatMessages">
                    <?php if(count($msgs) > 0): ?>
                        <?php foreach($msgs as $m): ?>
                        <div class="chat-bubble <?php echo $m['sender']; ?>">
                            <div class="bubble-body"><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
                            <div class="bubble-meta">
                                <?php echo $m['sender'] === 'admin' ? 'You' : htmlspecialchars($c['customer_name']); ?>
                                · <?php echo date('M j, h:i A', strtotime($m['date_sent'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;color:var(--gray-400);gap:10px;">
                        <i class="bi bi-chat" style="font-size:2.5rem;"></i>
                        <p style="font-size:0.875rem;">No messages yet. Start the conversation!</p>
                    </div>
                    <?php endif; ?>
                </div>

                <form method="POST" class="chat-input-wrap">
                    <textarea name="message" class="chat-input auto-resize" placeholder="Type a message..." rows="1"></textarea>
                    <button type="submit" name="send_message" class="chat-send-btn">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>
</div>

<!-- PWA Banner -->
<div class="pwa-banner">
    <div class="pwa-banner-text">
        <strong>Install DentalPortal</strong>
        Add to your home screen for quick access
    </div>
    <button class="pwa-install-btn">Install</button>
    <button class="pwa-dismiss"><i class="bi bi-x"></i></button>
</div>

<style>
@media (max-width: 900px) {
    .view-layout { grid-template-columns: 1fr !important; }
}
</style>

<script src="../assets/app.js"></script>
<script src="../assets/odontogram.js"></script>
<script>
// Scroll chat to bottom
const chatMessages = document.getElementById('chatMessages');
if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;

<?php if(!empty($s['teeth_data'])): ?>
// json_encode (not addslashes+single-quotes) so any special characters in
// notes/shade fields can't break out of the string literal.
renderReadOnlyOdontogramAdvanced(
    document.getElementById('viewOdontogram'),
    document.getElementById('viewOdontogramDetails'),
    <?php echo json_encode($s['teeth_data']); ?>
);
<?php endif; ?>
</script>
</body>
</html>