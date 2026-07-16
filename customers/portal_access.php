<?php
require_once '../auth/session.php';
require_admin_login();
include "../db_conn.php";

if (!isset($_POST['customer_id']) || !isset($_POST['action'])) {
    header("Location: list.php");
    exit();
}
$customer_id = (int)$_POST['customer_id'];
$action = $_POST['action'];

$customer = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$customer->execute([$customer_id]);
$c = $customer->fetch();
if (!$c) { header("Location: list.php"); exit(); }

function generate_portal_username(string $name, PDO $conn): string {
    $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $name));
    if ($base === '') $base = 'patient';
    $username = $base;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM patient_portal_users WHERE username = ?");
    $i = 1;
    while (true) {
        $stmt->execute([$username]);
        if ((int)$stmt->fetchColumn() === 0) break;
        $i++;
        $username = $base . $i;
    }
    return $username;
}

function generate_portal_password(int $len = 10): string {
    // Excludes visually ambiguous characters (0/O, 1/l/I) since this gets
    // read off a screen and typed back in by the patient.
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $pw = '';
    for ($i = 0; $i < $len; $i++) {
        $pw .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $pw;
}

$existing = $conn->prepare("SELECT * FROM patient_portal_users WHERE customer_id = ?");
$existing->execute([$customer_id]);
$acct = $existing->fetch();

if ($action === 'create' && !$acct) {
    $username = generate_portal_username($c['customer_name'], $conn);
    $plain = generate_portal_password();
    $hash = password_hash($plain, PASSWORD_BCRYPT);
    $conn->prepare("INSERT INTO patient_portal_users (customer_id, username, password_hash, is_active) VALUES (?,?,?,1)")
         ->execute([$customer_id, $username, $hash]);
    // One-time reveal: the plain password is never retrievable again after
    // this request, so it's stashed in a flash session value for view.php
    // to display once, then it must be cleared.
    $_SESSION['portal_credentials'] = ['username' => $username, 'password' => $plain];
    $msg = "Patient portal access created — share the credentials shown below.";

} elseif ($action === 'reset' && $acct) {
    $plain = generate_portal_password();
    $hash = password_hash($plain, PASSWORD_BCRYPT);
    $conn->prepare("UPDATE patient_portal_users SET password_hash = ?, is_active = 1 WHERE customer_id = ?")
         ->execute([$hash, $customer_id]);
    $_SESSION['portal_credentials'] = ['username' => $acct['username'], 'password' => $plain];
    $msg = "Password reset — share the new credentials shown below.";

} elseif ($action === 'deactivate' && $acct) {
    $conn->prepare("UPDATE patient_portal_users SET is_active = 0 WHERE customer_id = ?")->execute([$customer_id]);
    $msg = "Patient portal access disabled.";

} elseif ($action === 'reactivate' && $acct) {
    $conn->prepare("UPDATE patient_portal_users SET is_active = 1 WHERE customer_id = ?")->execute([$customer_id]);
    $msg = "Patient portal access re-enabled.";

} else {
    header("Location: view.php?id=$customer_id");
    exit();
}

header("Location: view.php?id=$customer_id&msg=" . urlencode($msg));
exit();
