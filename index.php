<?php
require_once 'auth/session.php';
require_once 'db_conn.php';
require_admin_login();
$admin = current_admin();

// ── Date range filter ─────────────────────────────────────────
$period = $_GET['period'] ?? 'month';
switch ($period) {
    case 'week':  $from = date('Y-m-d', strtotime('-7 days'));  $label = 'Last 7 Days';  break;
    case 'year':  $from = date('Y-01-01');                       $label = 'This Year';    break;
    case 'all':   $from = '2000-01-01';                          $label = 'All Time';     break;
    default:      $from = date('Y-m-01');                        $label = 'This Month';   break;
}
$to = date('Y-m-d');

// ── KPI totals ────────────────────────────────────────────────
$kpi = $conn->query("
    SELECT
        COUNT(DISTINCT c.id)                                       AS total_patients,
        COUNT(DISTINCT ds.id)                                      AS total_services,
        COALESCE(SUM(ds.total_bill),0)                            AS gross_revenue,
        COALESCE(SUM(ds.amount_paid),0)                           AS collected,
        COALESCE(SUM(ds.total_bill)-SUM(ds.amount_paid),0)        AS outstanding,
        COUNT(CASE WHEN ds.payment_status='paid' THEN 1 END)      AS fully_paid,
        COUNT(CASE WHEN ds.payment_status='pending' THEN 1 END)   AS unpaid,
        COUNT(CASE WHEN ds.payment_status='partial' THEN 1 END)   AS partial_count
    FROM customers c
    LEFT JOIN dental_services ds ON ds.customer_id=c.id
    WHERE c.date_created >= '$from'
")->fetch();

// ── Appointments KPIs ─────────────────────────────────────────
$appt_kpi = $conn->query("
    SELECT
        COUNT(*) AS total,
        COUNT(CASE WHEN status='done' THEN 1 END)      AS done,
        COUNT(CASE WHEN status='scheduled' THEN 1 END) AS upcoming,
        COUNT(CASE WHEN status='cancelled' THEN 1 END) AS cancelled
    FROM appointments
    WHERE appointment_date >= '$from'
")->fetch();

// ── Monthly revenue (last 12 months) ─────────────────────────
$monthly = $conn->query("
    SELECT DATE_FORMAT(date_created,'%Y-%m') AS mo,
           SUM(total_bill)  AS billed,
           SUM(amount_paid) AS collected
    FROM dental_services
    WHERE date_created >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY mo ORDER BY mo ASC
")->fetchAll();

// ── Payment status breakdown ──────────────────────────────────
$status_breakdown = $conn->query("
    SELECT payment_status, COUNT(*) AS cnt, SUM(total_bill) AS total
    FROM dental_services GROUP BY payment_status
")->fetchAll();

// ── Top patients by revenue ───────────────────────────────────
$top_patients = $conn->query("
    SELECT c.customer_name, c.phone_number,
           SUM(ds.total_bill) AS total_billed,
           SUM(ds.amount_paid) AS total_paid,
           COUNT(ds.id) AS service_count
    FROM customers c
    JOIN dental_services ds ON ds.customer_id=c.id
    GROUP BY c.id ORDER BY total_billed DESC LIMIT 8
")->fetchAll();

// ── Appointment types breakdown ───────────────────────────────
$appt_types = $conn->query("
    SELECT appointment_type, COUNT(*) AS cnt
    FROM appointments GROUP BY appointment_type
")->fetchAll();

// ── Shade/size popularity ─────────────────────────────────────
$shades = $conn->query("
    SELECT tooth_shade, COUNT(*) AS cnt
    FROM dental_services WHERE tooth_shade IS NOT NULL AND tooth_shade != ''
    GROUP BY tooth_shade ORDER BY cnt DESC LIMIT 6
")->fetchAll();

// ── Recent activity ───────────────────────────────────────────
$recent_activity = $conn->query("
    (SELECT 'patient' AS type, customer_name AS title, date_created AS dt, id
     FROM customers ORDER BY date_created DESC LIMIT 5)
    UNION ALL
    (SELECT 'appointment' AS type, 
            CONCAT(c.customer_name, ' — ', a.appointment_type) AS title,
            a.date_created AS dt, a.id
     FROM appointments a JOIN customers c ON c.id=a.customer_id
     ORDER BY a.date_created DESC LIMIT 10)
")->fetchAll();

// Build chart data for JS
$chart_labels   = array_column($monthly, 'mo');
$chart_billed   = array_column($monthly, 'billed');
$chart_collected= array_column($monthly, 'collected');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics — DentalPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<div class="main-wrap">
<?php include 'partials/topbar.php'; ?>
<div class="page-content">

    <div class="page-header">
        <div>
            <h1 class="page-title">Analytics & Reports</h1>
            <p class="page-subtitle">Financial performance and clinic insights — <?php echo $label; ?></p>
        </div>
        <!-- Period filter -->
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <?php foreach(['week'=>'7 Days','month'=>'This Month','year'=>'This Year','all'=>'All Time'] as $k=>$l): ?>
            <a href="?period=<?php echo $k; ?>" 
               style="padding:8px 16px;border-radius:8px;font-size:0.82rem;font-weight:600;text-decoration:none;transition:all 0.2s;
                      <?php echo $period===$k ? 'background:var(--navy);color:white;' : 'background:white;color:var(--gray-600);border:1px solid var(--gray-100);'; ?>">
                <?php echo $l; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── KPI ROW 1 ── -->
    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
        <div class="stat-card stat-green">
            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-info">
                <span class="stat-label">Gross Revenue</span>
                <span class="stat-value">₱<?php echo number_format($kpi['gross_revenue'],0); ?></span>
            </div>
        </div>
        <div class="stat-card stat-teal">
            <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
            <div class="stat-info">
                <span class="stat-label">Collected</span>
                <span class="stat-value">₱<?php echo number_format($kpi['collected'],0); ?></span>
            </div>
        </div>
        <div class="stat-card stat-orange">
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
            <div class="stat-info">
                <span class="stat-label">Outstanding</span>
                <span class="stat-value">₱<?php echo number_format($kpi['outstanding'],0); ?></span>
            </div>
        </div>
        <div class="stat-card stat-blue">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">New Patients</span>
                <span class="stat-value"><?php echo $kpi['total_patients']; ?></span>
            </div>
        </div>
    </div>

    <!-- ── KPI ROW 2 ── -->
    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:28px;">
        <div class="stat-card stat-purple">
            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Fully Paid</span>
                <span class="stat-value"><?php echo $kpi['fully_paid']; ?></span>
            </div>
        </div>
        <div class="stat-card stat-red">
            <div class="stat-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Unpaid Cases</span>
                <span class="stat-value"><?php echo $kpi['unpaid']; ?></span>
            </div>
        </div>
        <div class="stat-card stat-teal">
            <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-info">
                <span class="stat-label">Appts Done</span>
                <span class="stat-value"><?php echo $appt_kpi['done']; ?></span>
            </div>
        </div>
        <div class="stat-card stat-blue">
            <div class="stat-icon"><i class="bi bi-calendar-event"></i></div>
            <div class="stat-info">
                <span class="stat-label">Upcoming Appts</span>
                <span class="stat-value"><?php echo $appt_kpi['upcoming']; ?></span>
            </div>
        </div>
    </div>

    <!-- ── CHARTS ROW ── -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px;">

        <!-- Revenue Chart -->
        <div class="card-dp">
            <div class="card-header-dp">
                <h3><i class="bi bi-graph-up-arrow"></i> Revenue — Last 12 Months</h3>
            </div>
            <div style="padding:20px;height:300px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Payment Status Donut -->
        <div class="card-dp">
            <div class="card-header-dp">
                <h3><i class="bi bi-pie-chart-fill"></i> Payment Status</h3>
            </div>
            <div style="padding:20px;height:300px;display:flex;align-items:center;justify-content:center;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ── BOTTOM ROW ── -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

        <!-- Top Patients Table -->
        <div class="card-dp">
            <div class="card-header-dp">
                <h3><i class="bi bi-trophy-fill"></i> Top Patients by Revenue</h3>
            </div>
            <div class="table-wrap">
                <table class="dp-table">
                    <thead><tr><th>Patient</th><th>Services</th><th>Billed</th><th>Paid</th></tr></thead>
                    <tbody>
                    <?php foreach($top_patients as $i=>$tp): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <?php if($i<3): ?><span style="font-size:1rem;"><?php echo ['🥇','🥈','🥉'][$i]; ?></span><?php endif; ?>
                                <div>
                                    <div style="font-weight:600;font-size:0.82rem;"><?php echo htmlspecialchars($tp['customer_name']); ?></div>
                                    <div style="font-size:0.7rem;color:var(--gray-400);"><?php echo htmlspecialchars($tp['phone_number']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align:center;font-weight:700;"><?php echo $tp['service_count']; ?></td>
                        <td style="font-weight:700;color:var(--success);">₱<?php echo number_format($tp['total_billed'],0); ?></td>
                        <td style="font-weight:700;color:var(--info);">₱<?php echo number_format($tp['total_paid'],0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Appointment Types + Shade breakdown -->
        <div style="display:flex;flex-direction:column;gap:20px;">
            <div class="card-dp">
                <div class="card-header-dp">
                    <h3><i class="bi bi-bar-chart-fill"></i> Appointment Types</h3>
                </div>
                <div style="padding:16px 20px;">
                    <?php 
                    $type_total = array_sum(array_column($appt_types,'cnt'));
                    foreach($appt_types as $at): 
                        $pct = $type_total > 0 ? round($at['cnt']/$type_total*100) : 0;
                    ?>
                    <div style="margin-bottom:14px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
                            <span style="font-size:0.82rem;font-weight:600;color:var(--navy);text-transform:capitalize;">
                                <?php echo str_replace('_',' ',$at['appointment_type']); ?>
                            </span>
                            <span style="font-size:0.78rem;color:var(--gray-400);"><?php echo $at['cnt']; ?> (<?php echo $pct; ?>%)</span>
                        </div>
                        <div style="height:8px;background:var(--gray-100);border-radius:4px;overflow:hidden;">
                            <div style="height:100%;width:<?php echo $pct; ?>%;background:linear-gradient(90deg,var(--teal),var(--navy-soft));border-radius:4px;transition:width 1s;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if(count($shades)): ?>
            <div class="card-dp">
                <div class="card-header-dp">
                    <h3><i class="bi bi-palette-fill"></i> Popular Tooth Shades</h3>
                </div>
                <div style="padding:16px 20px;display:flex;flex-wrap:wrap;gap:8px;">
                    <?php foreach($shades as $sh): ?>
                    <div style="background:linear-gradient(135deg,var(--navy),var(--teal));color:white;padding:6px 14px;border-radius:8px;font-size:0.82rem;font-weight:700;display:flex;align-items:center;gap:6px;">
                        <?php echo htmlspecialchars($sh['tooth_shade']); ?>
                        <span style="background:rgba(255,255,255,0.2);padding:2px 7px;border-radius:10px;font-size:0.7rem;"><?php echo $sh['cnt']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Collection Rate -->
    <?php 
    $collection_rate = $kpi['gross_revenue'] > 0 ? round($kpi['collected']/$kpi['gross_revenue']*100,1) : 0;
    ?>
    <div class="card-dp" style="margin-bottom:20px;">
        <div class="card-header-dp">
            <h3><i class="bi bi-speedometer2"></i> Collection Rate</h3>
            <span style="font-size:1.4rem;font-weight:800;color:<?php echo $collection_rate>=80?'var(--success)':($collection_rate>=50?'var(--warning)':'var(--danger)'); ?>;">
                <?php echo $collection_rate; ?>%
            </span>
        </div>
        <div style="padding:20px;">
            <div style="height:16px;background:var(--gray-100);border-radius:8px;overflow:hidden;margin-bottom:12px;">
                <div style="height:100%;width:<?php echo $collection_rate; ?>%;background:linear-gradient(90deg,var(--success),var(--teal));border-radius:8px;transition:width 1.5s;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:0.82rem;">
                <span style="color:var(--gray-400);">₱<?php echo number_format($kpi['collected'],2); ?> collected of ₱<?php echo number_format($kpi['gross_revenue'],2); ?> billed</span>
                <span style="color:var(--danger);font-weight:600;">₱<?php echo number_format($kpi['outstanding'],2); ?> remaining</span>
            </div>
        </div>
    </div>

</div>
</div>

<script src="assets/app.js"></script>
<script>
// Revenue Chart
const rCtx = document.getElementById('revenueChart');
if (rCtx) {
    new Chart(rCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_map(fn($m)=>date('M Y',strtotime($m.'-01')),$chart_labels)); ?>,
            datasets: [
                {
                    label: 'Billed',
                    data: <?php echo json_encode(array_map('floatval',$chart_billed)); ?>,
                    backgroundColor: 'rgba(15,45,74,0.7)',
                    borderRadius: 6,
                },
                {
                    label: 'Collected',
                    data: <?php echo json_encode(array_map('floatval',$chart_collected)); ?>,
                    backgroundColor: 'rgba(10,143,143,0.7)',
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => '₱'+v.toLocaleString() } }
            }
        }
    });
}

// Status Donut
const sCtx = document.getElementById('statusChart');
if (sCtx) {
    const breakdown = <?php echo json_encode($status_breakdown); ?>;
    new Chart(sCtx, {
        type: 'doughnut',
        data: {
            labels: breakdown.map(b => b.payment_status.charAt(0).toUpperCase()+b.payment_status.slice(1)),
            datasets: [{
                data: breakdown.map(b => parseInt(b.cnt)),
                backgroundColor: ['#1aaa6e','#e08b1a','#2a7fcf'],
                borderWidth: 3,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            cutout: '65%'
        }
    });
}
</script>
</body>
</html>