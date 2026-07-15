<?php
include "../db_conn.php";

$customers = $conn->query("
    SELECT c.*, 
           ds.payment_status, ds.total_bill, ds.amount_paid,
           ds.tooth_upper, ds.tooth_lower,
           (SELECT COUNT(*) FROM appointments a WHERE a.customer_id = c.id) as appt_count
    FROM customers c
    LEFT JOIN dental_services ds ON ds.customer_id = c.id
    ORDER BY c.date_created DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f2d4a">
    <title>All Patients — DentalPortal</title>
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
        <span>Patients</span>
    </div>

    <div class="page-header">
        <div>
            <h1 class="page-title">All Patients</h1>
            <p class="page-subtitle"><?php echo count($customers); ?> registered patients</p>
        </div>
        <a href="create.php" class="btn-primary-dp">
            <i class="bi bi-person-plus-fill"></i> Add Patient
        </a>
    </div>

    <div class="controls-bar">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" id="tableSearch" placeholder="Search by name, phone...">
        </div>
        <select class="form-control-dp" id="statusFilter" style="width:auto;padding:9px 14px;">
            <option value="">All Status</option>
            <option value="paid">Paid</option>
            <option value="partial">Partial</option>
            <option value="pending">Pending</option>
        </select>
    </div>

    <div class="card-dp">
        <!-- Desktop Table -->
        <div class="table-wrap">
            <table class="dp-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Phone</th>
                        <th>Tooth (U/L)</th>
                        <th>Total Bill</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(count($customers) > 0): ?>
                    <?php foreach($customers as $c):
                        $bal = ($c['total_bill'] ?? 0) - ($c['amount_paid'] ?? 0);
                        $ps  = $c['payment_status'] ?? 'pending';
                        $parts = explode(' ', $c['customer_name']);
                        $initials = strtoupper(substr($parts[0],0,1)) . (isset($parts[1]) ? strtoupper(substr($parts[1],0,1)) : '');
                    ?>
                    <tr class="data-row" data-status="<?php echo $ps; ?>">
                        <td><span class="id-badge-small"><?php echo $c['id']; ?></span></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="patient-avatar" style="width:34px;height:34px;font-size:0.75rem;"><?php echo $initials; ?></div>
                                <div>
                                    <div style="font-weight:600;font-size:0.875rem;color:var(--navy);"><?php echo htmlspecialchars($c['customer_name']); ?></div>
                                    <div style="font-size:0.72rem;color:var(--gray-400);">Added <?php echo date('M j', strtotime($c['date_created'])); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--gray-600);"><?php echo htmlspecialchars($c['phone_number']); ?></td>
                        <td>
                            <span style="font-weight:700;color:var(--navy);"><?php echo ($c['tooth_upper'] ?? 0); ?></span>
                            <span style="color:var(--gray-400);font-size:0.8rem;"> upper / </span>
                            <span style="font-weight:700;color:var(--navy);"><?php echo ($c['tooth_lower'] ?? 0); ?></span>
                            <span style="color:var(--gray-400);font-size:0.8rem;"> lower</span>
                        </td>
                        <td><strong style="color:var(--success);">₱<?php echo number_format($c['total_bill'] ?? 0, 2); ?></strong></td>
                        <td>
                            <strong style="color:<?php echo $bal > 0 ? 'var(--danger)' : 'var(--success)'; ?>">
                                ₱<?php echo number_format($bal, 2); ?>
                            </strong>
                        </td>
                        <td><span class="status-pill status-<?php echo $ps; ?>"><?php echo ucfirst($ps); ?></span></td>
                        <td style="color:var(--gray-400);font-size:0.8rem;"><?php echo date('M j, Y', strtotime($c['date_created'])); ?></td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <a href="view.php?id=<?php echo $c['id']; ?>" class="btn-outline-dp" style="padding:6px 12px;font-size:0.78rem;">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <a href="edit.php?id=<?php echo $c['id']; ?>" class="btn-primary-dp" style="padding:6px 12px;font-size:0.78rem;">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="delete.php?id=<?php echo $c['id']; ?>" class="btn-danger-dp confirm-delete" style="padding:6px 12px;font-size:0.78rem;">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
                            <h3>No Patients Yet</h3>
                            <p>Start by adding your first patient to the system</p>
                            <a href="create.php" class="btn-primary-dp">
                                <i class="bi bi-person-plus-fill"></i> Add Patient
                            </a>
                        </div>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="mobile-card-list" style="padding:12px;">
            <?php foreach($customers as $c):
                $bal = ($c['total_bill'] ?? 0) - ($c['amount_paid'] ?? 0);
                $ps  = $c['payment_status'] ?? 'pending';
                $parts = explode(' ', $c['customer_name']);
                $initials = strtoupper(substr($parts[0],0,1)) . (isset($parts[1]) ? strtoupper(substr($parts[1],0,1)) : '');
            ?>
            <div class="mobile-client-card" style="background:white;border:1px solid var(--gray-100);border-radius:12px;padding:16px;margin-bottom:12px;box-shadow:var(--shadow);">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--gray-100);">
                    <div class="patient-avatar"><?php echo $initials; ?></div>
                    <div style="flex:1;">
                        <div style="font-weight:700;color:var(--navy);"><?php echo htmlspecialchars($c['customer_name']); ?></div>
                        <div style="font-size:0.78rem;color:var(--gray-400);display:flex;align-items:center;gap:4px;margin-top:2px;">
                            <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($c['phone_number']); ?>
                        </div>
                    </div>
                    <span class="status-pill status-<?php echo $ps; ?>"><?php echo ucfirst($ps); ?></span>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
                    <div>
                        <div style="font-size:0.68rem;color:var(--gray-400);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;margin-bottom:3px;">Teeth</div>
                        <div style="font-weight:700;color:var(--navy);"><?php echo ($c['tooth_upper']??0); ?>U / <?php echo ($c['tooth_lower']??0); ?>L</div>
                    </div>
                    <div>
                        <div style="font-size:0.68rem;color:var(--gray-400);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;margin-bottom:3px;">Balance</div>
                        <div style="font-weight:700;color:<?php echo $bal > 0 ? 'var(--danger)':'var(--success)'; ?>;">₱<?php echo number_format($bal,2); ?></div>
                    </div>
                </div>
                <div style="display:flex;gap:8px;">
                    <a href="view.php?id=<?php echo $c['id']; ?>" class="btn-outline-dp" style="flex:1;justify-content:center;padding:8px;">
                        <i class="bi bi-eye"></i> View
                    </a>
                    <a href="edit.php?id=<?php echo $c['id']; ?>" class="btn-primary-dp" style="flex:1;justify-content:center;padding:8px;">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>
</div>

<!-- PWA Install Banner -->
<div class="pwa-banner">
    <div class="pwa-banner-text">
        <strong>Install DentalPortal</strong>
        Add to your home screen for quick access
    </div>
    <button class="pwa-install-btn">Install</button>
    <button class="pwa-dismiss"><i class="bi bi-x"></i></button>
</div>

<script src="../assets/app.js"></script>
<script>
// Status filter
document.getElementById('statusFilter')?.addEventListener('change', function() {
    const val = this.value;
    document.querySelectorAll('.data-row').forEach(row => {
        if (!val || row.dataset.status === val) row.style.display = '';
        else row.style.display = 'none';
    });
    document.querySelectorAll('.mobile-client-card').forEach(card => {
        if (!val) card.style.display = '';
        else card.style.display = card.textContent.toLowerCase().includes(val) ? '' : 'none';
    });
});
</script>
</body>
</html>
