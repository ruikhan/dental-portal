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

if(isset($_POST['submit'])) {
    $name    = trim($_POST['customer_name']);
    $phone   = trim($_POST['phone_number']);
    $email   = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $notes   = trim($_POST['notes'] ?? '');

    $teeth_data = trim($_POST['teeth_data'] ?? '');
    [$tooth_upper, $tooth_lower] = odonto_counts($teeth_data);
    $tooth_shade = trim($_POST['tooth_shade'] ?? '');
    $tooth_size  = trim($_POST['tooth_size'] ?? '');
    $desc        = trim($_POST['service_description'] ?? '');
    $total_bill  = (float)$_POST['total_bill'];
    $amount_paid = (float)$_POST['amount_paid'];

    if($total_bill <= $amount_paid && $amount_paid > 0) $ps = 'paid';
    elseif($amount_paid > 0) $ps = 'partial';
    else $ps = 'pending';

    if(empty($name) || empty($phone)) {
        $error = "Name and phone are required.";
    } else {
        $conn->prepare("UPDATE customers SET customer_name=?,phone_number=?,email=?,address=?,notes=? WHERE id=?")
             ->execute([$name,$phone,$email,$address,$notes,$id]);

        if($s) {
            $conn->prepare("UPDATE dental_services SET tooth_upper=?,tooth_lower=?,teeth_data=?,tooth_shade=?,tooth_size=?,description=?,total_bill=?,amount_paid=?,payment_status=? WHERE customer_id=?")
                 ->execute([$tooth_upper,$tooth_lower,$teeth_data,$tooth_shade,$tooth_size,$desc,$total_bill,$amount_paid,$ps,$id]);
        } else {
            $conn->prepare("INSERT INTO dental_services (customer_id,tooth_upper,tooth_lower,teeth_data,tooth_shade,tooth_size,description,total_bill,amount_paid,payment_status) VALUES (?,?,?,?,?,?,?,?,?,?)")
                 ->execute([$id,$tooth_upper,$tooth_lower,$teeth_data,$tooth_shade,$tooth_size,$desc,$total_bill,$amount_paid,$ps]);
        }

        header("Location: view.php?id=$id&msg=" . urlencode("Patient updated successfully!"));
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient — DentalPortal</title>
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
        <a href="view.php?id=<?php echo $id; ?>"><?php echo htmlspecialchars($c['customer_name']); ?></a>
        <i class="bi bi-chevron-right"></i>
        <span>Edit</span>
    </div>

    <div class="page-header">
        <div>
            <h1 class="page-title">Edit Patient</h1>
            <p class="page-subtitle">Update details for <?php echo htmlspecialchars($c['customer_name']); ?></p>
        </div>
    </div>

    <?php if(isset($error)): ?>
    <div class="alert-dp alert-error"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="form-wrap">

        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-person-badge"></i> Patient Information</div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">Full Name *</label>
                    <input type="text" name="customer_name" class="form-control-dp" required value="<?php echo htmlspecialchars($c['customer_name']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dp">Phone Number *</label>
                    <input type="text" name="phone_number" class="form-control-dp" required value="<?php echo htmlspecialchars($c['phone_number']); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">Email</label>
                    <input type="email" name="email" class="form-control-dp" value="<?php echo htmlspecialchars($c['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dp">Address</label>
                    <input type="text" name="address" class="form-control-dp" value="<?php echo htmlspecialchars($c['address'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label-dp">Notes</label>
                <textarea name="notes" class="form-control-dp auto-resize" rows="2"><?php echo htmlspecialchars($c['notes'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-tooth"></i> Dental Service</div>
            <div class="form-group">
                <label class="form-label-dp">Select Teeth Involved</label>
                <div id="odontogramContainer"></div>
                <input type="hidden" name="teeth_data" id="teethDataInput" value="<?php echo htmlspecialchars($s['teeth_data'] ?? ''); ?>">
                <!-- Populated by odontogram.js: live upper/lower/total counts, clear-all,
                     and a per-tooth status/shade/size/notes editor. -->
                <div id="odontogramDetails" style="margin-top:14px;"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">Overall Shade <span style="color:var(--gray-400);font-weight:400;">(optional quick-reference — set per-tooth shade above for detail)</span></label>
                    <input type="text" name="tooth_shade" class="form-control-dp" placeholder="e.g. A3" value="<?php echo htmlspecialchars($s['tooth_shade'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dp">Overall Size / Code</label>
                    <input type="text" name="tooth_size" class="form-control-dp" placeholder="e.g. 64" value="<?php echo htmlspecialchars($s['tooth_size'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label-dp">Service Description</label>
                <textarea name="service_description" class="form-control-dp auto-resize" rows="2"><?php echo htmlspecialchars($s['description'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-cash-stack"></i> Billing</div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">Total Bill (₱)</label>
                    <div class="input-prefix">
                        <span class="input-prefix-text">₱</span>
                        <input type="number" name="total_bill" step="0.01" class="form-control-dp" id="totalBill" value="<?php echo $s['total_bill'] ?? 0; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label-dp">Amount Paid (₱)</label>
                    <div class="input-prefix">
                        <span class="input-prefix-text">₱</span>
                        <input type="number" name="amount_paid" step="0.01" class="form-control-dp" id="amountPaid" value="<?php echo $s['amount_paid'] ?? 0; ?>">
                    </div>
                </div>
            </div>
            <div style="background:var(--gray-50);border-radius:10px;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;border:1px solid var(--gray-100);">
                <span style="font-weight:600;color:var(--gray-600);font-size:0.875rem;display:flex;align-items:center;gap:8px;">
                    <i class="bi bi-calculator" style="color:var(--teal);"></i> Remaining Balance
                </span>
                <span id="balanceDisplay" style="font-size:1.3rem;font-weight:800;color:var(--navy);">₱0.00</span>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="submit" class="btn-primary-dp">
                <i class="bi bi-check-circle-fill"></i> Update Patient
            </button>
            <a href="view.php?id=<?php echo $id; ?>" class="btn-outline-dp">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
        </div>
    </form>

</div>
</div>
<script src="../assets/app.js"></script>
<script src="../assets/odontogram.js"></script>
<script>
initOdontogramAdvanced(
    document.getElementById('odontogramContainer'),
    document.getElementById('odontogramDetails'),
    document.getElementById('teethDataInput').value,
    document.getElementById('teethDataInput')
);

const totalBill = document.getElementById('totalBill');
const amountPaid = document.getElementById('amountPaid');
const balanceDisplay = document.getElementById('balanceDisplay');
function updateBalance() {
    const b = (parseFloat(totalBill.value)||0) - (parseFloat(amountPaid.value)||0);
    balanceDisplay.textContent = '₱' + b.toFixed(2);
    balanceDisplay.style.color = b > 0 ? 'var(--danger)' : 'var(--success)';
}
totalBill.addEventListener('input', updateBalance);
amountPaid.addEventListener('input', updateBalance);
updateBalance();
</script>
</body>
</html>
