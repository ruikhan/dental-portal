<?php
include "../db_conn.php";

if (isset($_POST['submit'])) {
    $name    = trim($_POST['customer_name']);
    $phone   = trim($_POST['phone_number']);
    $email   = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $notes   = trim($_POST['notes'] ?? '');

    // Service fields
    $tooth_upper = (int)($_POST['tooth_upper'] ?? 0);
    $tooth_lower = (int)($_POST['tooth_lower'] ?? 0);
    $tooth_shade = trim($_POST['tooth_shade'] ?? '');
    $tooth_size  = trim($_POST['tooth_size'] ?? '');
    $desc        = trim($_POST['service_description'] ?? '');
    $total_bill  = (float)($_POST['total_bill'] ?? 0);
    $amount_paid = (float)($_POST['amount_paid'] ?? 0);

    if ($total_bill <= $amount_paid && $amount_paid > 0) $ps = 'paid';
    elseif ($amount_paid > 0) $ps = 'partial';
    else $ps = 'pending';

    if (empty($name) || empty($phone)) {
        $error = "Patient name and phone number are required.";
    } else {
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("INSERT INTO customers (customer_name, phone_number, email, address, notes) VALUES (?,?,?,?,?)");
            $stmt->execute([$name, $phone, $email, $address, $notes]);
            $customer_id = $conn->lastInsertId();

            $stmt2 = $conn->prepare("INSERT INTO dental_services (customer_id, tooth_upper, tooth_lower, tooth_shade, tooth_size, description, total_bill, amount_paid, payment_status) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt2->execute([$customer_id, $tooth_upper, $tooth_lower, $tooth_shade, $tooth_size, $desc, $total_bill, $amount_paid, $ps]);

            $conn->commit();
            header("Location: view.php?id=$customer_id&msg=" . urlencode("Patient added successfully!"));
            exit();
        } catch(Exception $e) {
            $conn->rollBack();
            $error = "Failed to save: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f2d4a">
    <title>Add Patient — DentalPortal</title>
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
        <a href="list.php">Patients</a>
        <i class="bi bi-chevron-right"></i>
        <span>Add Patient</span>
    </div>

    <div class="page-header">
        <div>
            <h1 class="page-title">Add New Patient</h1>
            <p class="page-subtitle">Fill in the details below to register a new patient</p>
        </div>
    </div>

    <?php if(isset($error)): ?>
    <div class="alert-dp alert-error"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="form-wrap">

        <!-- Patient Info -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="bi bi-person-badge"></i> Patient Information
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">Full Name <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="customer_name" class="form-control-dp" placeholder="e.g. John Doe" required value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dp">Phone Number <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="phone_number" class="form-control-dp" placeholder="e.g. 09506574600" required value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">Email Address</label>
                    <input type="email" name="email" class="form-control-dp" placeholder="optional" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dp">Address</label>
                    <input type="text" name="address" class="form-control-dp" placeholder="optional" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label-dp">General Notes</label>
                <textarea name="notes" class="form-control-dp auto-resize" rows="2" placeholder="Any general notes about this patient..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Dental Service -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="bi bi-tooth"></i> Dental Service Details
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">Number of Upper Teeth</label>
                    <input type="number" name="tooth_upper" class="form-control-dp" min="0" max="32" placeholder="0" value="<?php echo htmlspecialchars($_POST['tooth_upper'] ?? 0); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dp">Number of Lower Teeth</label>
                    <input type="number" name="tooth_lower" class="form-control-dp" min="0" max="32" placeholder="0" value="<?php echo htmlspecialchars($_POST['tooth_lower'] ?? 0); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">Tooth Shade</label>
                    <input type="text" name="tooth_shade" class="form-control-dp" placeholder="e.g. A3, B2" value="<?php echo htmlspecialchars($_POST['tooth_shade'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dp">Tooth Size / Code</label>
                    <input type="text" name="tooth_size" class="form-control-dp" placeholder="e.g. 64, 52" value="<?php echo htmlspecialchars($_POST['tooth_size'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label-dp">Service Description</label>
                <textarea name="service_description" class="form-control-dp auto-resize" rows="2" placeholder="Describe the dental service to be performed..."><?php echo htmlspecialchars($_POST['service_description'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Billing -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="bi bi-cash-stack"></i> Billing Details
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">Total Bill (₱)</label>
                    <div class="input-prefix">
                        <span class="input-prefix-text">₱</span>
                        <input type="number" name="total_bill" step="0.01" min="0" class="form-control-dp" placeholder="0.00" id="totalBill" value="<?php echo htmlspecialchars($_POST['total_bill'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label-dp">Amount Paid (₱)</label>
                    <div class="input-prefix">
                        <span class="input-prefix-text">₱</span>
                        <input type="number" name="amount_paid" step="0.01" min="0" class="form-control-dp" placeholder="0.00" id="amountPaid" value="<?php echo htmlspecialchars($_POST['amount_paid'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Balance Preview -->
            <div style="background:var(--gray-50);border-radius:10px;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;border:1px solid var(--gray-100);">
                <span style="font-weight:600;color:var(--gray-600);font-size:0.875rem;display:flex;align-items:center;gap:8px;">
                    <i class="bi bi-calculator" style="color:var(--teal);"></i> Remaining Balance
                </span>
                <span id="balanceDisplay" style="font-size:1.3rem;font-weight:800;color:var(--navy);">₱0.00</span>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="submit" class="btn-primary-dp">
                <i class="bi bi-check-circle-fill"></i> Save Patient
            </button>
            <a href="list.php" class="btn-outline-dp">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
        </div>

    </form>
</div>
</div>

<script src="../assets/app.js"></script>
<script>
const totalBill  = document.getElementById('totalBill');
const amountPaid = document.getElementById('amountPaid');
const balanceDisplay = document.getElementById('balanceDisplay');

function updateBalance() {
    const t = parseFloat(totalBill.value) || 0;
    const p = parseFloat(amountPaid.value) || 0;
    const b = t - p;
    balanceDisplay.textContent = '₱' + b.toFixed(2);
    balanceDisplay.style.color = b > 0 ? 'var(--danger)' : b < 0 ? 'var(--info)' : 'var(--success)';
}
totalBill.addEventListener('input', updateBalance);
amountPaid.addEventListener('input', updateBalance);
updateBalance();
</script>
</body>
</html>
