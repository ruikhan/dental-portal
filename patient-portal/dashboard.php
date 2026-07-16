<?php
require_once '../auth/session.php';
require_patient_login();
require_once '../db_conn.php';

$patient = current_patient();
$customer_id = $patient['customer_id'];

$customer = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$customer->execute([$customer_id]);
$c = $customer->fetch();

// The account row exists but the customer it points to was deleted —
// treat this the same as an invalid login rather than fataling on a
// missing array key below.
if (!$c) {
    unset($_SESSION['patient_id'], $_SESSION['patient_customer_id'], $_SESSION['patient_name'], $_SESSION['patient_username']);
    header('Location: login.php?error=account');
    exit();
}

$service = $conn->prepare("SELECT * FROM dental_services WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
$service->execute([$customer_id]);
$s = $service->fetch();

$next_stmt = $conn->prepare("
    SELECT * FROM appointments
    WHERE customer_id = ? AND status = 'scheduled' AND appointment_date >= CURDATE()
    ORDER BY appointment_date ASC, appointment_time ASC LIMIT 1
");
$next_stmt->execute([$customer_id]);
$next_appt = $next_stmt->fetch();

$appts_stmt = $conn->prepare("SELECT * FROM appointments WHERE customer_id = ? ORDER BY appointment_date DESC, appointment_time DESC");
$appts_stmt->execute([$customer_id]);
$appts = $appts_stmt->fetchAll();

$parts = explode(' ', $c['customer_name']);
$initials = strtoupper(substr($parts[0], 0, 1)) . (isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '');

$bal = ($s['total_bill'] ?? 0) - ($s['amount_paid'] ?? 0);
$ps  = $s['payment_status'] ?? 'pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../partials/pwa-head.php'; ?>
    <title>My Dashboard — DentalPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/style.css" rel="stylesheet">
    <link href="../assets/odontogram.css" rel="stylesheet">
</head>
<body>
<?php include 'partials/topbar.php'; ?>

<div class="page-content" style="max-width:1000px;margin:0 auto;">

    <!-- Welcome Hero -->
    <div class="customer-hero">
        <div class="customer-hero-avatar"><?php echo $initials; ?></div>
        <div class="customer-hero-info">
            <div class="customer-hero-name">Hi, <?php echo htmlspecialchars(explode(' ', $c['customer_name'])[0]); ?> 👋</div>
            <div class="customer-hero-meta">
                <span><i class="bi bi-telephone-fill"></i> <?php echo htmlspecialchars($c['phone_number']); ?></span>
                <span><i class="bi bi-calendar3"></i> Patient since <?php echo date('M j, Y', strtotime($c['date_created'])); ?></span>
            </div>
        </div>
        <div class="customer-hero-actions">
            <a href="messages.php" class="btn-primary-dp">
                <i class="bi bi-chat-dots-fill"></i> Message Clinic
            </a>
        </div>
    </div>

    <!-- Next Appointment -->
    <?php if ($next_appt):
        $nd = new DateTime($next_appt['appointment_date']);
    ?>
    <div class="card-dp" style="margin-bottom:24px;background:linear-gradient(135deg,var(--navy),var(--navy-soft));color:white;border:none;overflow:visible;">
        <div style="padding:20px 24px;display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div style="flex:1;min-width:200px;">
                <div style="font-size:0.7rem;text-transform:uppercase;letter-spacing:1px;opacity:0.7;font-weight:700;margin-bottom:4px;">Your Next Appointment</div>
                <div style="font-family:'DM Serif Display',serif;font-size:1.3rem;">
                    <?php echo ucfirst(str_replace('_',' ',$next_appt['appointment_type'])); ?>
                    — <?php echo $nd->format('l, F j, Y'); ?>
                </div>
                <div style="font-size:0.9rem;opacity:0.85;margin-top:2px;">
                    <i class="bi bi-clock"></i> <?php echo date('h:i A', strtotime($next_appt['appointment_time'])); ?>
                    <?php if ($next_appt['notes']): ?>
                        · <?php echo htmlspecialchars($next_appt['notes']); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card-dp" style="margin-bottom:24px;">
        <div class="empty-state" style="padding:32px;">
            <div class="empty-state-icon" style="width:56px;height:56px;font-size:1.6rem;"><i class="bi bi-calendar-x"></i></div>
            <h3 style="font-size:1rem;">No Upcoming Appointment</h3>
            <p style="font-size:0.82rem;">Message the clinic if you'd like to schedule one.</p>
            <a href="messages.php" class="btn-primary-dp" style="font-size:0.82rem;padding:8px 16px;">
                <i class="bi bi-chat-dots-fill"></i> Message Clinic
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr;gap:24px;">

        <!-- Service Summary -->
        <?php if ($s): ?>
        <div class="service-card">
            <div class="service-card-header">
                <div class="service-card-title"><i class="bi bi-tooth"></i> Your Treatment Summary</div>
                <span style="font-size:0.72rem;opacity:0.7;">Updated <?php echo date('M j, Y', strtotime($s['date_created'])); ?></span>
            </div>

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

            <?php if (!empty($s['teeth_data'])): ?>
            <div class="service-spec-row" style="flex-direction:column;align-items:stretch;gap:10px;">
                <span class="spec-label"><i class="bi bi-grid-3x3-gap-fill"></i> Tooth Chart</span>
                <div id="patientOdontogram"></div>
                <div id="patientOdontogramDetails"></div>
            </div>
            <?php endif; ?>

            <?php if ($s['description']): ?>
            <div class="service-spec-row" style="flex-direction:column;align-items:flex-start;gap:6px;">
                <span class="spec-label"><i class="bi bi-card-text"></i> Notes From Your Clinic</span>
                <span style="font-size:0.875rem;color:var(--gray-600);line-height:1.6;"><?php echo nl2br(htmlspecialchars($s['description'])); ?></span>
            </div>
            <?php endif; ?>

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
        </div>
        <?php else: ?>
        <div class="card-dp">
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-tooth"></i></div>
                <h3>No Treatment Record Yet</h3>
                <p>Your clinic hasn't added service details yet.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Appointment History -->
        <div>
            <h2 style="font-family:'DM Serif Display',serif;font-size:1.3rem;font-weight:400;color:var(--navy);margin-bottom:14px;">
                <i class="bi bi-calendar3" style="color:var(--teal);margin-right:8px;font-size:1rem;"></i>
                Appointment History
            </h2>

            <?php if (count($appts) > 0): ?>
                <?php foreach ($appts as $a):
                    $ad = new DateTime($a['appointment_date']);
                ?>
                <div class="appt-card">
                    <div class="appt-date-block">
                        <div class="appt-date-day"><?php echo $ad->format('d'); ?></div>
                        <div class="appt-date-mon"><?php echo $ad->format('M'); ?></div>
                        <div class="appt-date-yr"><?php echo $ad->format('Y'); ?></div>
                    </div>
                    <div class="appt-card-divider"></div>
                    <div class="appt-card-info">
                        <div class="appt-card-type"><?php echo ucfirst(str_replace('_', ' ', $a['appointment_type'])); ?></div>
                        <div class="appt-card-time"><i class="bi bi-clock"></i> <?php echo date('h:i A', strtotime($a['appointment_time'])); ?></div>
                        <?php if ($a['notes']): ?>
                        <div class="appt-card-note"><?php echo htmlspecialchars($a['notes']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="appt-card-actions">
                        <span class="status-pill status-<?php echo $a['status']; ?>"><?php echo ucfirst($a['status']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="card-dp">
                <div class="empty-state" style="padding:32px;">
                    <div class="empty-state-icon" style="width:56px;height:56px;font-size:1.6rem;"><i class="bi bi-calendar-x"></i></div>
                    <h3 style="font-size:1rem;">No Appointments Yet</h3>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="../assets/odontogram.js"></script>
<?php if (!empty($s['teeth_data'])): ?>
<script>
renderReadOnlyOdontogramAdvanced(
    document.getElementById('patientOdontogram'),
    document.getElementById('patientOdontogramDetails'),
    <?php echo json_encode($s['teeth_data']); ?>
);
</script>
<?php endif; ?>
</body>
</html>
