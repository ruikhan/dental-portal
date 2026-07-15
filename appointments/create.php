<?php
include "../db_conn.php";

// Pre-select customer if passed
$pre_customer = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

// Fetch all customers for dropdown
$customers_list = $conn->query("SELECT id, customer_name, phone_number FROM customers ORDER BY customer_name ASC")->fetchAll();

if(isset($_POST['submit'])) {
    $customer_id = (int)$_POST['customer_id'];
    $type        = $_POST['appointment_type'];
    $date        = $_POST['appointment_date'];
    $time        = $_POST['appointment_time'];
    $notes       = trim($_POST['notes'] ?? '');

    if(!$customer_id || !$date || !$time) {
        $error = "Patient, date, and time are required.";
    } else {
        // Get service_id
        $svc = $conn->prepare("SELECT id FROM dental_services WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
        $svc->execute([$customer_id]);
        $svc_row = $svc->fetch();
        $service_id = $svc_row ? $svc_row['id'] : null;

        $conn->prepare("INSERT INTO appointments (customer_id, service_id, appointment_type, appointment_date, appointment_time, notes) VALUES (?,?,?,?,?,?)")
             ->execute([$customer_id, $service_id, $type, $date, $time, $notes]);

        $redirect = $pre_customer ? "../customers/view.php?id=$pre_customer&msg=" . urlencode("Appointment scheduled!") : "list.php?msg=" . urlencode("Appointment scheduled!");
        header("Location: $redirect");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Appointment — DentalPortal</title>
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
        <a href="list.php">Appointments</a>
        <i class="bi bi-chevron-right"></i>
        <span>New Appointment</span>
    </div>

    <div class="page-header">
        <div>
            <h1 class="page-title">Schedule Appointment</h1>
            <p class="page-subtitle">Set up a new appointment for a patient</p>
        </div>
    </div>

    <?php if(isset($error)): ?>
    <div class="alert-dp alert-error"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="form-wrap">

        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-person-check"></i> Patient & Type</div>
            <div class="form-group">
                <label class="form-label-dp">Select Patient *</label>
                <select name="customer_id" class="form-control-dp" required>
                    <option value="">— Select Patient —</option>
                    <?php foreach($customers_list as $cust): ?>
                    <option value="<?php echo $cust['id']; ?>" <?php echo ($pre_customer == $cust['id'] || (isset($_POST['customer_id']) && $_POST['customer_id'] == $cust['id'])) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cust['customer_name']); ?> — <?php echo htmlspecialchars($cust['phone_number']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label-dp">Appointment Type *</label>
                <select name="appointment_type" class="form-control-dp" required>
                    <option value="trial" <?php echo (($_POST['appointment_type']??'trial') === 'trial') ? 'selected' : ''; ?>>Trial Fitting</option>
                    <option value="follow_up" <?php echo (($_POST['appointment_type']??'') === 'follow_up') ? 'selected' : ''; ?>>Follow-up</option>
                    <option value="final" <?php echo (($_POST['appointment_type']??'') === 'final') ? 'selected' : ''; ?>>Final Delivery</option>
                    <option value="consultation" <?php echo (($_POST['appointment_type']??'') === 'consultation') ? 'selected' : ''; ?>>Consultation</option>
                </select>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-calendar-event"></i> Date & Time</div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">Date *</label>
                    <input type="date" name="appointment_date" class="form-control-dp" required
                           min="<?php echo date('Y-m-d'); ?>"
                           value="<?php echo htmlspecialchars($_POST['appointment_date'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dp">Time *</label>
                    <input type="time" name="appointment_time" class="form-control-dp" required
                           value="<?php echo htmlspecialchars($_POST['appointment_time'] ?? '10:00'); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label-dp">Notes / Remarks</label>
                <textarea name="notes" class="form-control-dp auto-resize" rows="2" 
                          placeholder="Any notes for this appointment..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Preview -->
        <div class="form-section" style="background:linear-gradient(135deg,var(--navy),var(--navy-soft));color:white;border:none;">
            <div class="form-section-title" style="color:white;border-bottom-color:rgba(255,255,255,0.15);">
                <i class="bi bi-eye"></i> Appointment Preview
            </div>
            <div style="display:grid;gap:8px;font-size:0.875rem;">
                <div style="display:flex;gap:8px;align-items:center;opacity:0.9;">
                    <i class="bi bi-calendar3"></i>
                    <span id="previewDate">Select a date</span>
                </div>
                <div style="display:flex;gap:8px;align-items:center;opacity:0.9;">
                    <i class="bi bi-clock"></i>
                    <span id="previewTime">Select a time</span>
                </div>
                <div style="display:flex;gap:8px;align-items:center;opacity:0.9;">
                    <i class="bi bi-tag"></i>
                    <span id="previewType">Trial Fitting</span>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="submit" class="btn-primary-dp">
                <i class="bi bi-calendar-check"></i> Schedule Appointment
            </button>
            <a href="list.php" class="btn-outline-dp"><i class="bi bi-x-circle"></i> Cancel</a>
        </div>
    </form>
</div>
</div>
<script src="../assets/app.js"></script>
<script>
const dateInput = document.querySelector('[name="appointment_date"]');
const timeInput = document.querySelector('[name="appointment_time"]');
const typeSelect = document.querySelector('[name="appointment_type"]');
const previewDate = document.getElementById('previewDate');
const previewTime = document.getElementById('previewTime');
const previewType = document.getElementById('previewType');

function updatePreview() {
    if(dateInput.value) {
        const d = new Date(dateInput.value + 'T00:00:00');
        previewDate.textContent = d.toLocaleDateString('en-PH', {weekday:'long',year:'numeric',month:'long',day:'numeric'});
    }
    if(timeInput.value) {
        const [h,m] = timeInput.value.split(':');
        const d = new Date();
        d.setHours(h,m);
        previewTime.textContent = d.toLocaleTimeString('en-PH', {hour:'numeric',minute:'2-digit',hour12:true});
    }
    previewType.textContent = typeSelect.options[typeSelect.selectedIndex].text;
}
dateInput.addEventListener('change', updatePreview);
timeInput.addEventListener('change', updatePreview);
typeSelect.addEventListener('change', updatePreview);
updatePreview();
</script>
</body>
</html>
