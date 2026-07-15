<?php
require_once 'auth/session.php';
require_once 'db_conn.php';
require_admin_login();
$admin = current_admin();

// Load all settings
$raw = $conn->query("SELECT setting_key, setting_value FROM clinic_settings")->fetchAll();
$settings = [];
foreach ($raw as $r) $settings[$r['setting_key']] = $r['setting_value'];

// Load admin users
$users = $conn->query("SELECT * FROM admin_users ORDER BY created_at ASC")->fetchAll();

$flash = get_flash();

// ── Save clinic settings ──────────────────────────────────────
if (isset($_POST['save_clinic'])) {
    $keys = ['clinic_name','clinic_address','clinic_phone','clinic_email',
             'invoice_prefix','invoice_footer','primary_color','accent_color'];
    foreach ($keys as $k) {
        $val = trim($_POST[$k] ?? '');
        $conn->prepare("INSERT INTO clinic_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?, updated_at=NOW()")
             ->execute([$k, $val, $val]);
    }
    // Logo upload
    if (!empty($_FILES['clinic_logo']['name'])) {
        $ext  = strtolower(pathinfo($_FILES['clinic_logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['png','jpg','jpeg','svg','webp'];
        if (in_array($ext, $allowed)) {
            $dest = 'uploads/logo.' . $ext;
            if (move_uploaded_file($_FILES['clinic_logo']['tmp_name'], $dest)) {
                $conn->prepare("INSERT INTO clinic_settings (setting_key,setting_value) VALUES ('clinic_logo_path',?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$dest, $dest]);
            }
        }
    }
    set_flash('Clinic settings saved successfully.', 'success');
    header('Location: settings.php'); exit();
}

// ── Save SMTP settings ────────────────────────────────────────
if (isset($_POST['save_smtp'])) {
    $smtp_keys = ['smtp_host','smtp_port','smtp_username','smtp_password',
                  'smtp_from_name','smtp_from_email','sms_api_key','sms_sender_id'];
    foreach ($smtp_keys as $k) {
        $val = trim($_POST[$k] ?? '');
        $conn->prepare("INSERT INTO clinic_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?, updated_at=NOW()")
             ->execute([$k, $val, $val]);
    }
    set_flash('Notification settings saved.', 'success');
    header('Location: settings.php'); exit();
}

// ── Add admin user ────────────────────────────────────────────
if (isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $username  = trim($_POST['username']);
    $pw        = trim($_POST['new_password']);
    $role      = $_POST['role'] ?? 'admin';
    if ($full_name && $email && $username && $pw) {
        $hash = password_hash($pw, PASSWORD_BCRYPT);
        try {
            $conn->prepare("INSERT INTO admin_users (full_name,email,username,password_hash,role) VALUES (?,?,?,?,?)")
                 ->execute([$full_name, $email, $username, $hash, $role]);
            set_flash("Admin user '$username' created.", 'success');
        } catch (Exception $e) {
            set_flash('Username or email already exists.', 'error');
        }
    }
    header('Location: settings.php'); exit();
}

// ── Change own password ───────────────────────────────────────
if (isset($_POST['change_password'])) {
    $old = trim($_POST['old_password']);
    $new = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);
    $me = $conn->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
    $me->execute([$admin['id']]);
    $me = $me->fetch();
    if (!password_verify($old, $me['password_hash'])) {
        set_flash('Current password is incorrect.', 'error');
    } elseif ($new !== $confirm) {
        set_flash('New passwords do not match.', 'error');
    } elseif (strlen($new) < 8) {
        set_flash('Password must be at least 8 characters.', 'error');
    } else {
        $conn->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?")
             ->execute([password_hash($new, PASSWORD_BCRYPT), $admin['id']]);
        set_flash('Password changed successfully.', 'success');
    }
    header('Location: settings.php'); exit();
}

// ── Delete admin user ─────────────────────────────────────────
if (isset($_GET['delete_user'])) {
    $del_id = (int)$_GET['delete_user'];
    if ($del_id !== $admin['id']) {
        $conn->prepare("DELETE FROM admin_users WHERE id = ?")->execute([$del_id]);
        set_flash('User removed.', 'success');
    }
    header('Location: settings.php'); exit();
}

$tab = $_GET['tab'] ?? 'clinic';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — DentalPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<div class="main-wrap">
<?php include 'partials/topbar.php'; ?>
<div class="page-content">

    <div class="breadcrumb-dp">
        <a href="index.php">Dashboard</a>
        <i class="bi bi-chevron-right"></i><span>Settings</span>
    </div>
    <div class="page-header">
        <div>
            <h1 class="page-title">Settings</h1>
            <p class="page-subtitle">Manage clinic branding, notifications, and admin accounts</p>
        </div>
    </div>

    <?php if ($f = $flash): ?>
    <div class="alert-dp alert-<?php echo $f['type'] === 'error' ? 'error' : 'success'; ?>" style="margin-bottom:20px;">
        <i class="bi bi-<?php echo $f['type'] === 'error' ? 'exclamation-triangle' : 'check-circle'; ?>-fill"></i>
        <?php echo htmlspecialchars($f['msg']); ?>
    </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div style="display:flex;gap:4px;margin-bottom:24px;background:white;padding:6px;border-radius:12px;box-shadow:var(--shadow);border:1px solid var(--gray-100);width:fit-content;">
        <?php foreach (['clinic'=>'Clinic Branding','notifications'=>'Notifications','users'=>'Admin Users','password'=>'Password'] as $key=>$label): ?>
        <a href="?tab=<?php echo $key; ?>" 
           style="padding:8px 20px;border-radius:8px;font-size:0.85rem;font-weight:600;text-decoration:none;transition:all 0.2s;
                  <?php echo $tab===$key ? 'background:var(--navy);color:white;' : 'color:var(--gray-600);'; ?>">
            <?php echo $label; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($tab === 'clinic'): ?>
    <!-- ── CLINIC BRANDING ── -->
    <form method="POST" enctype="multipart/form-data" class="form-wrap">
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-building"></i> Clinic Information</div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">Clinic Name</label>
                    <input type="text" name="clinic_name" class="form-control-dp" value="<?php echo htmlspecialchars($settings['clinic_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dp">Clinic Phone</label>
                    <input type="text" name="clinic_phone" class="form-control-dp" value="<?php echo htmlspecialchars($settings['clinic_phone'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">Clinic Email</label>
                    <input type="email" name="clinic_email" class="form-control-dp" value="<?php echo htmlspecialchars($settings['clinic_email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dp">Address</label>
                    <input type="text" name="clinic_address" class="form-control-dp" value="<?php echo htmlspecialchars($settings['clinic_address'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label-dp">Clinic Logo (PNG/JPG/SVG)</label>
                <?php if(!empty($settings['clinic_logo_path'])): ?>
                <div style="margin-bottom:10px;"><img src="<?php echo htmlspecialchars($settings['clinic_logo_path']); ?>" style="height:48px;border-radius:6px;border:1px solid var(--gray-100);padding:4px;background:white;"></div>
                <?php endif; ?>
                <input type="file" name="clinic_logo" class="form-control-dp" accept="image/*" style="padding:8px;">
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-palette"></i> Branding & Invoice</div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">Invoice Prefix</label>
                    <input type="text" name="invoice_prefix" class="form-control-dp" value="<?php echo htmlspecialchars($settings['invoice_prefix'] ?? 'INV'); ?>" placeholder="e.g. INV, DC, DENT">
                </div>
                <div class="form-group">
                    <label class="form-label-dp">Brand Colors</label>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <div>
                            <div style="font-size:0.72rem;color:var(--gray-400);margin-bottom:4px;">Primary</div>
                            <input type="color" name="primary_color" value="<?php echo $settings['primary_color'] ?? '#0f2d4a'; ?>" style="width:50px;height:38px;border-radius:8px;border:2px solid var(--gray-100);cursor:pointer;padding:2px;">
                        </div>
                        <div>
                            <div style="font-size:0.72rem;color:var(--gray-400);margin-bottom:4px;">Accent</div>
                            <input type="color" name="accent_color" value="<?php echo $settings['accent_color'] ?? '#0a8f8f'; ?>" style="width:50px;height:38px;border-radius:8px;border:2px solid var(--gray-100);cursor:pointer;padding:2px;">
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label-dp">Invoice Footer Text</label>
                <textarea name="invoice_footer" class="form-control-dp" rows="2"><?php echo htmlspecialchars($settings['invoice_footer'] ?? ''); ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" name="save_clinic" class="btn-primary-dp">
                <i class="bi bi-check-circle-fill"></i> Save Clinic Settings
            </button>
        </div>
    </form>

    <?php elseif ($tab === 'notifications'): ?>
    <!-- ── NOTIFICATIONS ── -->
    <form method="POST" class="form-wrap">
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-envelope-fill"></i> Email (SMTP)</div>
            <div class="alert-dp alert-info" style="margin-bottom:20px;">
                <i class="bi bi-info-circle"></i> For Gmail: use <strong>smtp.gmail.com</strong>, port <strong>587</strong>, and an <strong>App Password</strong> (not your Gmail password).
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">SMTP Host</label>
                    <input type="text" name="smtp_host" class="form-control-dp" placeholder="smtp.gmail.com" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dp">SMTP Port</label>
                    <input type="text" name="smtp_port" class="form-control-dp" placeholder="587" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">SMTP Username / Email</label>
                    <input type="text" name="smtp_username" class="form-control-dp" placeholder="your@gmail.com" value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dp">SMTP Password / App Password</label>
                    <input type="password" name="smtp_password" class="form-control-dp" placeholder="••••••••••••" value="<?php echo htmlspecialchars($settings['smtp_password'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">From Name</label>
                    <input type="text" name="smtp_from_name" class="form-control-dp" placeholder="DentalCare Clinic" value="<?php echo htmlspecialchars($settings['smtp_from_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dp">From Email</label>
                    <input type="email" name="smtp_from_email" class="form-control-dp" placeholder="noreply@clinic.com" value="<?php echo htmlspecialchars($settings['smtp_from_email'] ?? ''); ?>">
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-phone"></i> SMS (Semaphore / any REST API)</div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label-dp">SMS API Key</label>
                    <input type="text" name="sms_api_key" class="form-control-dp" placeholder="Your SMS API key" value="<?php echo htmlspecialchars($settings['sms_api_key'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dp">Sender ID / Name</label>
                    <input type="text" name="sms_sender_id" class="form-control-dp" placeholder="DentalCare" value="<?php echo htmlspecialchars($settings['sms_sender_id'] ?? ''); ?>">
                </div>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" name="save_smtp" class="btn-primary-dp">
                <i class="bi bi-check-circle-fill"></i> Save Notification Settings
            </button>
        </div>
    </form>

    <?php elseif ($tab === 'users'): ?>
    <!-- ── ADMIN USERS ── -->
    <div class="form-wrap" style="max-width:900px;">
        <div class="card-dp" style="margin-bottom:24px;">
            <div class="card-header-dp">
                <h3><i class="bi bi-people"></i> Admin Users (<?php echo count($users); ?>)</h3>
            </div>
            <div class="table-wrap">
                <table class="dp-table">
                    <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Role</th><th>Last Login</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td>
                            <div style="font-weight:600;"><?php echo htmlspecialchars($u['full_name']); ?></div>
                            <div style="font-size:0.75rem;color:var(--gray-400);"><?php echo htmlspecialchars($u['email']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><span class="status-pill status-<?php echo $u['role']==='super_admin'?'paid':'scheduled'; ?>"><?php echo ucfirst(str_replace('_',' ',$u['role'])); ?></span></td>
                        <td style="font-size:0.82rem;color:var(--gray-400);">
                            <?php echo $u['last_login'] ? date('M j, Y h:i A', strtotime($u['last_login'])) : 'Never'; ?>
                        </td>
                        <td>
                            <?php if($u['id'] !== $admin['id']): ?>
                            <a href="?tab=users&delete_user=<?php echo $u['id']; ?>" 
                               class="btn-danger-dp confirm-delete" style="padding:5px 10px;font-size:0.75rem;">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php else: ?>
                            <span style="font-size:0.75rem;color:var(--gray-400);">You</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-person-plus"></i> Add New Admin User</div>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label-dp">Full Name</label>
                        <input type="text" name="full_name" class="form-control-dp" required placeholder="e.g. Dr. Santos">
                    </div>
                    <div class="form-group">
                        <label class="form-label-dp">Email</label>
                        <input type="email" name="email" class="form-control-dp" required placeholder="user@clinic.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label-dp">Username</label>
                        <input type="text" name="username" class="form-control-dp" required placeholder="username">
                    </div>
                    <div class="form-group">
                        <label class="form-label-dp">Role</label>
                        <select name="role" class="form-control-dp">
                            <option value="admin">Admin</option>
                            <option value="staff">Staff</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label-dp">Password</label>
                    <input type="password" name="new_password" class="form-control-dp" required placeholder="Minimum 8 characters">
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_user" class="btn-primary-dp">
                        <i class="bi bi-person-plus-fill"></i> Create Admin User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php elseif ($tab === 'password'): ?>
    <!-- ── CHANGE PASSWORD ── -->
    <form method="POST" class="form-wrap" style="max-width:480px;">
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-lock"></i> Change Your Password</div>
            <div class="form-group">
                <label class="form-label-dp">Current Password</label>
                <input type="password" name="old_password" class="form-control-dp" required>
            </div>
            <div class="form-group">
                <label class="form-label-dp">New Password</label>
                <input type="password" name="new_password" class="form-control-dp" required placeholder="Minimum 8 characters">
            </div>
            <div class="form-group">
                <label class="form-label-dp">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control-dp" required>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" name="change_password" class="btn-primary-dp">
                <i class="bi bi-shield-lock-fill"></i> Update Password
            </button>
        </div>
    </form>
    <?php endif; ?>

</div>
</div>
<script src="assets/app.js"></script>
</body>
</html>