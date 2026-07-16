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

// --- Quick-stats derived values ---
$total_bill_val  = (float)($s['total_bill'] ?? 0);
$amount_paid_val = (float)($s['amount_paid'] ?? 0);
$paid_pct = $total_bill_val > 0 ? min(100, round(($amount_paid_val / $total_bill_val) * 100)) : ($s ? 100 : 0);

$completed_count = count(array_filter($appts, fn($a) => $a['status'] === 'completed'));
$total_appts     = count($appts);

// Map appointment status -> a color token used for the timeline accent,
// and a matching "calendar icon" header tint (mirrors macOS Calendar's
// per-context colored header strip).
function dp_status_color($status) {
    return match ($status) {
        'completed' => 'var(--success, #22a06b)',
        'scheduled' => 'var(--teal, #14b8a6)',
        'cancelled', 'canceled' => 'var(--gray-300, #c7ccd4)',
        'no_show' => 'var(--warning, #e0a339)',
        default => 'var(--gray-300, #c7ccd4)',
    };
}
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
    <link href="../assets/patient-portal.css" rel="stylesheet">

    <!--
        Premium "macOS" pass for this page only.
        Scoped under body/.page-content so nothing here leaks into other
        pages that reuse patient-portal.css. Layers translucency + soft
        multi-stop shadows over the existing tokens (--navy, --teal,
        --success, --warning, --gray-*) rather than replacing the palette,
        so it stays visually consistent with the rest of the portal.
    -->
    <style>
        :root {
            --mac-radius-lg: 20px;
            --mac-radius-md: 14px;
            --mac-radius-sm: 10px;
            --mac-glass: rgba(255, 255, 255, 0.72);
            --mac-glass-strong: rgba(255, 255, 255, 0.85);
            --mac-border: rgba(15, 45, 74, 0.08);
            --mac-shadow-ambient: 0 1px 2px rgba(15, 45, 74, 0.04), 0 12px 28px -8px rgba(15, 45, 74, 0.14);
            --mac-shadow-hover: 0 2px 4px rgba(15, 45, 74, 0.06), 0 20px 40px -10px rgba(15, 45, 74, 0.20);
            --mac-ease: cubic-bezier(0.22, 1, 0.36, 1);
        }

        @media (prefers-reduced-motion: reduce) {
            * { transition-duration: 0.01ms !important; animation-duration: 0.01ms !important; }
        }

        body {
            background:
                radial-gradient(1200px 600px at 15% -10%, rgba(20, 184, 166, 0.07), transparent 60%),
                radial-gradient(1000px 700px at 100% 0%, rgba(15, 45, 74, 0.06), transparent 55%),
                var(--gray-50, #f5f6f8);
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Outfit", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .page-content { padding-top: 28px; padding-bottom: 40px; }

        /* ---------- Hero ---------- */
        .customer-hero {
            background: var(--mac-glass-strong);
            backdrop-filter: saturate(180%) blur(20px);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
            border: 1px solid var(--mac-border);
            border-radius: var(--mac-radius-lg);
            box-shadow: var(--mac-shadow-ambient);
            padding: 22px 26px;
            margin-bottom: 24px;
            transition: box-shadow 0.35s var(--mac-ease);
        }
        .customer-hero-avatar {
            box-shadow: 0 6px 16px -4px rgba(15, 45, 74, 0.28), inset 0 1px 0 rgba(255,255,255,0.25);
        }
        /* The glass hero is light, so text needs an explicit dark color —
           it can't inherit whatever white/light value the base stylesheet
           set for what was presumably a dark card background before. */
        .customer-hero-name {
            letter-spacing: -0.01em;
            color: var(--navy, #0f2d4a) !important;
        }
        .customer-hero-meta {
            color: var(--gray-600, #5a6472) !important;
        }
        .customer-hero-meta span {
            color: inherit !important;
        }
        .customer-hero-meta i {
            color: var(--teal, #14b8a6);
        }
        .btn-primary-dp {
            border-radius: 980px !important; /* Apple's signature pill button */
            font-weight: 600;
            letter-spacing: -0.01em;
            box-shadow: 0 1px 1px rgba(15,45,74,0.06), 0 8px 18px -6px rgba(20,184,166,0.45);
            transition: transform 0.18s var(--mac-ease), box-shadow 0.18s var(--mac-ease), filter 0.18s var(--mac-ease);
        }
        .btn-primary-dp:hover {
            transform: translateY(-1px);
            filter: brightness(1.04);
            box-shadow: 0 2px 4px rgba(15,45,74,0.08), 0 12px 24px -6px rgba(20,184,166,0.55);
        }
        .btn-primary-dp:active { transform: translateY(0) scale(0.98); }

        /* ---------- Quick stats ---------- */
        .dp-quickstats { gap: 14px; }
        .dp-stat-card {
            background: var(--mac-glass);
            backdrop-filter: saturate(180%) blur(16px);
            -webkit-backdrop-filter: saturate(180%) blur(16px);
            border: 1px solid var(--mac-border);
            border-radius: var(--mac-radius-md);
            box-shadow: var(--mac-shadow-ambient);
            transition: transform 0.28s var(--mac-ease), box-shadow 0.28s var(--mac-ease), background 0.28s var(--mac-ease);
        }
        .dp-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--mac-shadow-hover);
            background: var(--mac-glass-strong);
        }
        .dp-stat-icon {
            border-radius: 12px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.4), 0 4px 10px -3px rgba(15,45,74,0.18);
        }
        .dp-stat-value { letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }

        /* ---------- Generic glass card shell ---------- */
        .card-dp, .service-card {
            background: var(--mac-glass-strong) !important;
            backdrop-filter: saturate(180%) blur(20px);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
            border: 1px solid var(--mac-border) !important;
            border-radius: var(--mac-radius-lg) !important;
            box-shadow: var(--mac-shadow-ambient) !important;
        }

        /* "Next Appointment" hero banner keeps its navy gradient but
           picks up window chrome + a softer, layered shadow. */
        .card-dp[style*="linear-gradient"] {
            border-radius: var(--mac-radius-lg) !important;
            box-shadow: 0 1px 2px rgba(15,45,74,0.1), 0 24px 48px -16px rgba(15,45,74,0.45) !important;
            border: 1px solid rgba(255,255,255,0.08) !important;
        }

        /* ---------- macOS window-chrome signature ----------
           A quiet nod to the OS: every panel header carries the three
           traffic-light dots, desaturated so they read as texture/craft
           rather than literal UI chrome. */
        .mac-dots { display: inline-flex; gap: 6px; margin-right: 12px; vertical-align: middle; }
        .mac-dots span {
            width: 9px; height: 9px; border-radius: 50%; display: inline-block;
            box-shadow: inset 0 0.5px 1px rgba(0,0,0,0.15);
        }
        .mac-dots span:nth-child(1) { background: #ff6159; }
        .mac-dots span:nth-child(2) { background: #ffbd2e; }
        .mac-dots span:nth-child(3) { background: #28c840; }

        .service-card-header {
            display: flex;
            align-items: center;
        }

        /* ---------- Payment progress ---------- */
        .payment-progress-track {
            border-radius: 999px;
            overflow: hidden;
            background: var(--gray-100, #edf0f3);
        }
        .payment-progress-fill {
            border-radius: 999px;
            background: linear-gradient(90deg, var(--teal, #14b8a6), #0a8f8f);
            transition: width 0.6s var(--mac-ease);
        }

        /* ---------- Status pills -> macOS segmented-control feel ---------- */
        .status-pill {
            border-radius: 999px !important;
            font-weight: 600;
            letter-spacing: -0.01em;
            padding: 4px 12px !important;
            border: 1px solid rgba(15,45,74,0.06);
        }

        /* ---------- Appointment timeline ---------- */
        .appt-timeline { display: flex; flex-direction: column; gap: 10px; }
        .appt-card {
            background: var(--mac-glass-strong);
            backdrop-filter: saturate(180%) blur(16px);
            -webkit-backdrop-filter: saturate(180%) blur(16px);
            border-radius: var(--mac-radius-md) !important;
            box-shadow: var(--mac-shadow-ambient);
            transition: transform 0.25s var(--mac-ease), box-shadow 0.25s var(--mac-ease);
        }
        .appt-card:hover {
            transform: translateY(-2px) translateX(2px);
            box-shadow: var(--mac-shadow-hover);
        }
        .appt-card-divider { background: var(--mac-border); }

        /* Date block restyled as a miniature macOS Calendar app icon:
           colored month strip + a bold day number on a white face. */
        .appt-date-block {
            width: 56px;
            padding: 0 !important;
            border-radius: 13px;
            overflow: hidden;
            box-shadow: 0 4px 10px -4px rgba(15,45,74,0.35), 0 1px 0 rgba(255,255,255,0.5) inset;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            text-align: center;
            background: #fff;
        }
        .appt-date-mon {
            background: linear-gradient(180deg, #ff6b57, #ee4a3e);
            color: #fff;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 3px 0;
            order: -1; /* month strip on top, like the Calendar icon */
        }
        .appt-date-day {
            font-size: 1.4rem;
            font-weight: 700;
            line-height: 1.15;
            color: var(--navy, #0f2d4a);
            padding-top: 3px;
            letter-spacing: -0.02em;
        }
        .appt-date-yr {
            font-size: 0.6rem;
            color: var(--gray-400, #98a2b3);
            padding-bottom: 4px;
            font-weight: 600;
        }

        /* ---------- Section headings ---------- */
        h2[style*="DM Serif Display"] { letter-spacing: -0.01em; }
    </style>
</head>
<body>
<!-- // <?php include 'partials/topbar.php'; ?> -->

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

    <!-- Quick Stats -->
    <div class="dp-quickstats">
        <div class="dp-stat-card">
            <div class="dp-stat-icon teal"><i class="bi bi-calendar-event"></i></div>
            <div class="dp-stat-label">Next Visit</div>
            <div class="dp-stat-value">
                <?php echo $next_appt ? (new DateTime($next_appt['appointment_date']))->format('M j') : '—'; ?>
            </div>
        </div>
        <div class="dp-stat-card">
            <div class="dp-stat-icon <?php echo $bal > 0 ? 'warning' : 'success'; ?>">
                <i class="bi bi-cash-coin"></i>
            </div>
            <div class="dp-stat-label">Balance Due</div>
            <div class="dp-stat-value"><?php echo $bal > 0 ? '₱' . number_format($bal, 2) : '₱0.00'; ?></div>
        </div>
        <div class="dp-stat-card">
            <div class="dp-stat-icon navy"><i class="bi bi-clipboard2-check"></i></div>
            <div class="dp-stat-label">Total Visits</div>
            <div class="dp-stat-value"><?php echo $total_appts; ?></div>
        </div>
        <div class="dp-stat-card">
            <div class="dp-stat-icon <?php echo $ps === 'paid' ? 'success' : ($ps === 'partial' ? 'warning' : 'gray'); ?>">
                <i class="bi bi-receipt-cutoff"></i>
            </div>
            <div class="dp-stat-label">Payment Status</div>
            <div class="dp-stat-value" style="font-size:1.05rem;"><?php echo ucfirst($ps); ?></div>
        </div>
    </div>

    <!-- Next Appointment -->
    <?php if ($next_appt):
        $nd = new DateTime($next_appt['appointment_date']);
    ?>
    <div class="card-dp" style="margin-bottom:24px;background:linear-gradient(135deg,var(--navy),var(--navy-soft));color:white;border:none;overflow:visible;">
        <div style="padding:20px 24px;display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;box-shadow:inset 0 1px 0 rgba(255,255,255,0.2), 0 6px 14px -6px rgba(0,0,0,0.4);">
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
                <span class="mac-dots"><span></span><span></span><span></span></span>
                <div class="service-card-title"><i class="bi bi-tooth"></i> Your Treatment Summary</div>
                <span style="font-size:0.72rem;opacity:0.7;margin-left:auto;">Updated <?php echo date('M j, Y', strtotime($s['date_created'])); ?></span>
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

            <!-- Payment progress -->
            <div class="service-spec-row" style="flex-direction:column;align-items:stretch;gap:6px;">
                <div class="payment-progress-wrap">
                    <div class="payment-progress-track">
                        <div class="payment-progress-fill" style="width:<?php echo $paid_pct; ?>%;"></div>
                    </div>
                    <div class="payment-progress-label">
                        <span><?php echo $paid_pct; ?>% paid</span>
                        <span><?php echo $bal > 0 ? '₱' . number_format($bal, 2) . ' remaining' : 'Fully settled'; ?></span>
                    </div>
                </div>
            </div>

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
            <h2 style="font-family:'DM Serif Display',serif;font-size:1.3rem;font-weight:400;color:var(--navy);margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <span><i class="bi bi-calendar3" style="color:var(--teal);margin-right:8px;font-size:1rem;"></i>Appointment History</span>
                <?php if ($total_appts > 0): ?>
                <span style="font-family:'Outfit',sans-serif;font-size:0.72rem;font-weight:600;color:var(--gray-500);text-transform:none;">
                    <?php echo $completed_count; ?> completed of <?php echo $total_appts; ?>
                </span>
                <?php endif; ?>
            </h2>

            <?php if (count($appts) > 0): ?>
                <div class="appt-timeline">
                <?php foreach ($appts as $a):
                    $ad = new DateTime($a['appointment_date']);
                ?>
                <div class="appt-card" style="border-left:4px solid <?php echo dp_status_color($a['status']); ?>;">
                    <div class="appt-date-block">
                        <div class="appt-date-mon"><?php echo $ad->format('M'); ?></div>
                        <div class="appt-date-day"><?php echo $ad->format('d'); ?></div>
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
                </div>
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
