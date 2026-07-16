<?php
require_once '../auth/session.php';
require_once '../db_conn.php';

// Already logged in
if (!empty($_SESSION['patient_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $conn->prepare("
            SELECT ppu.*, c.customer_name
            FROM patient_portal_users ppu
            JOIN customers c ON c.id = ppu.customer_id
            WHERE ppu.username = ? AND ppu.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $acct = $stmt->fetch();

        if ($acct && password_verify($password, $acct['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['patient_id']          = $acct['id'];
            $_SESSION['patient_customer_id'] = $acct['customer_id'];
            $_SESSION['patient_name']        = $acct['customer_name'];
            $_SESSION['patient_username']    = $acct['username'];

            $conn->prepare("UPDATE patient_portal_users SET last_login = NOW() WHERE id = ?")->execute([$acct['id']]);

            $redirect = $_GET['redirect'] ?? 'dashboard.php';
            header('Location: ' . $redirect);
            exit();
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    } else {
        $error = 'Please enter your username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../partials/pwa-head.php'; ?>
    <title>Patient Login — DentalPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --navy: #0f2d4a; --navy-soft: #1e4d72;
            --teal: #0a8f8f; --teal-soft: #0fb3b3;
            --gold: #d4a843;
            --gray-100: #e2eaf2; --gray-400: #7a9ab8; --gray-800: #2c3e50;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 480px;
            background: var(--navy);
        }
        .login-brand {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 60px;
            background: linear-gradient(160deg, var(--navy) 0%, var(--navy-soft) 100%);
            position: relative;
            overflow: hidden;
        }
        .login-brand::before {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(10,143,143,0.15), transparent 70%);
            top: -100px; right: -200px;
        }
        .login-brand::after {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(212,168,67,0.08), transparent 70%);
            bottom: -100px; left: -100px;
        }
        .brand-logo {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, var(--teal), var(--teal-soft));
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: white;
            margin-bottom: 32px;
            box-shadow: 0 8px 32px rgba(10,143,143,0.4);
            position: relative; z-index: 1;
        }
        .brand-tagline {
            font-size: 0.78rem; letter-spacing: 3px; text-transform: uppercase;
            color: var(--teal-soft); font-weight: 600; margin-bottom: 12px;
            position: relative; z-index: 1;
        }
        .brand-title {
            font-family: 'DM Serif Display', serif;
            font-size: 3rem; color: white; line-height: 1.15; margin-bottom: 20px;
            position: relative; z-index: 1;
        }
        .brand-desc {
            font-size: 0.95rem; color: rgba(255,255,255,0.55); line-height: 1.8;
            max-width: 420px; position: relative; z-index: 1;
        }
        .brand-features { margin-top: 48px; display: flex; flex-direction: column; gap: 16px; position: relative; z-index: 1; }
        .brand-feature { display: flex; align-items: center; gap: 14px; color: rgba(255,255,255,0.7); font-size: 0.9rem; }
        .brand-feature i {
            width: 36px; height: 36px; background: rgba(10,143,143,0.2); border-radius: 8px;
            display: flex; align-items: center; justify-content: center; color: var(--teal-soft);
            font-size: 1rem; flex-shrink: 0;
        }
        .login-form-panel {
            background: #f7f9fc; display: flex; flex-direction: column;
            justify-content: center; padding: 60px 48px; position: relative;
        }
        .form-header { margin-bottom: 36px; }
        .form-header h2 { font-family: 'DM Serif Display', serif; font-size: 1.9rem; color: var(--navy); margin-bottom: 6px; }
        .form-header p { font-size: 0.9rem; color: var(--gray-400); }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 0.82rem; font-weight: 600; color: var(--gray-800); margin-bottom: 8px; letter-spacing: 0.2px; }
        .input-wrap { position: relative; }
        .input-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 1rem; }
        .form-input {
            width: 100%; padding: 13px 14px 13px 42px; border: 2px solid var(--gray-100); border-radius: 12px;
            font-family: 'Outfit', sans-serif; font-size: 0.95rem; color: var(--gray-800); background: white; transition: all 0.2s;
        }
        .form-input:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 4px rgba(10,143,143,0.1); }
        .toggle-pw { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--gray-400); cursor: pointer; font-size: 1rem; padding: 0; }
        .toggle-pw:hover { color: var(--navy); }
        .btn-login {
            width: 100%; padding: 14px; background: linear-gradient(135deg, var(--teal), var(--navy-soft)); color: white;
            border: none; border-radius: 12px; font-family: 'Outfit', sans-serif; font-size: 1rem; font-weight: 700;
            cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 16px rgba(10,143,143,0.3); margin-top: 8px;
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-login:hover { transform: translateY(-1px); box-shadow: 0 6px 22px rgba(10,143,143,0.4); }
        .alert-error {
            background: rgba(214,51,72,0.08); border: 1px solid rgba(214,51,72,0.2); color: #a0172a;
            padding: 12px 16px; border-radius: 10px; font-size: 0.875rem; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .login-footer { margin-top: 32px; text-align: center; font-size: 0.8rem; color: var(--gray-400); }
        .login-footer a { color: var(--teal); text-decoration: none; font-weight: 600; }
        .help-note {
            background: rgba(212,168,67,0.08); border: 1px solid rgba(212,168,67,0.25); border-radius: 10px;
            padding: 12px 16px; margin-bottom: 20px; font-size: 0.8rem; color: #8a6b1f; display: flex; gap: 10px;
        }
        @media (max-width: 900px) {
            body { grid-template-columns: 1fr; }
            .login-brand { display: none; }
            .login-form-panel { padding: 40px 28px; }
        }
    </style>
</head>
<body>

<div class="login-brand">
    <div class="brand-logo"><i class="bi bi-tooth"></i></div>
    <div class="brand-tagline">Patient Portal</div>
    <h1 class="brand-title">Your Dental<br>Care, Online</h1>
    <p class="brand-desc">
        Check your treatment progress, see upcoming appointments, and message
        your clinic directly — anytime, from any device.
    </p>
    <div class="brand-features">
        <div class="brand-feature"><i class="bi bi-tooth"></i> Track Your Treatment</div>
        <div class="brand-feature"><i class="bi bi-calendar3"></i> View Appointments</div>
        <div class="brand-feature"><i class="bi bi-chat-dots-fill"></i> Message Your Clinic</div>
        <div class="brand-feature"><i class="bi bi-receipt"></i> Check Billing Status</div>
    </div>
</div>

<div class="login-form-panel">
    <div class="form-header">
        <h2>Welcome back</h2>
        <p>Sign in to view your dental care details</p>
    </div>

    <?php if ($error): ?>
    <div class="alert-error">
        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <div class="help-note">
        <i class="bi bi-info-circle-fill"></i>
        <span>Don't have login details yet? Ask your clinic's front desk — they can set up your patient portal access.</span>
    </div>

    <form method="POST" autocomplete="off">
        <div class="form-group">
            <label class="form-label">Username</label>
            <div class="input-wrap">
                <i class="bi bi-person input-icon"></i>
                <input type="text" name="username" class="form-input"
                       placeholder="Your username"
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       autofocus required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <div class="input-wrap">
                <i class="bi bi-lock input-icon"></i>
                <input type="password" name="password" id="passwordInput"
                       class="form-input" placeholder="Enter password" required>
                <button type="button" class="toggle-pw" onclick="togglePw()">
                    <i class="bi bi-eye" id="pwIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right"></i> Sign In
        </button>
    </form>

    <div class="login-footer">
        <p>Clinic staff? <a href="../auth/login.php">Go to Admin Login →</a></p>
    </div>
            <button type="submit" class="btn-login">
            <i class="bi bi-anthropic"></i> Powered by AI Insight
        </button>
</div>

<script>
function togglePw() {
    const inp = document.getElementById('passwordInput');
    const icon = document.getElementById('pwIcon');
    if (inp.type === 'password') { inp.type = 'text'; icon.className = 'bi bi-eye-slash'; }
    else { inp.type = 'password'; icon.className = 'bi bi-eye'; }
}
</script>
</body>
</html>
