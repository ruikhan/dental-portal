<?php
// auth/session.php — Central auth helper for admin panel
// Include this at the TOP of every admin page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ── Admin auth check ──────────────────────────────────────────
function require_admin_login(string $redirect_to = '') {
    if (empty($_SESSION['admin_id'])) {
        $back = $redirect_to ?: $_SERVER['REQUEST_URI'];
        header('Location: ' . root_path('auth/login.php') . '?redirect=' . urlencode($back));
        exit();
    }
}
// ── Patient portal auth check ─────────────────────────────────
function require_patient_login(string $redirect_to = '') {
    if (empty($_SESSION['patient_id'])) {
        $back = $redirect_to ?: $_SERVER['REQUEST_URI'];
        header('Location: ' . root_path('patient-portal/login.php') . '?redirect=' . urlencode($back));
        exit();
    }
}
// ── Utility: build root-relative path ─────────────────────────
// FIX: correct depth formula is (slash count - 1), not -2 — see
// partials/sidebar.php and partials/topbar.php for the same fix
// and full explanation. This only affects pages one or more
// folders deep (e.g. customers/view.php) that call
// require_admin_login()/require_patient_login(); root-level pages
// were already safe thanks to the max(0, ...) clamp.
function root_path(string $path = ''): string {
    $depth = substr_count($_SERVER['PHP_SELF'], '/') - 1;
    return str_repeat('../', max(0, $depth)) . $path;
}
// ── Flash messages ─────────────────────────────────────────────
function set_flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash_msg']  = $msg;
    $_SESSION['flash_type'] = $type;
}
function get_flash(): ?array {
    if (!empty($_SESSION['flash_msg'])) {
        $f = ['msg' => $_SESSION['flash_msg'], 'type' => $_SESSION['flash_type'] ?? 'success'];
        unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        return $f;
    }
    return null;
}
// ── CSRF token ─────────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}
function verify_csrf(): bool {
    return isset($_POST['csrf_token']) && hash_equals(csrf_token(), $_POST['csrf_token']);
}
// ── Current admin info ─────────────────────────────────────────
function current_admin(): array {
    return [
        'id'        => $_SESSION['admin_id']   ?? 0,
        'name'      => $_SESSION['admin_name'] ?? 'Admin',
        'email'     => $_SESSION['admin_email'] ?? '',
        'role'      => $_SESSION['admin_role'] ?? 'admin',
        'initials'  => strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1))
                     . strtoupper(substr(strstr($_SESSION['admin_name'] ?? '', ' '), 1, 1)),
    ];
}
// ── Current patient info ───────────────────────────────────────
function current_patient(): array {
    return [
        'id'          => $_SESSION['patient_id']       ?? 0,
        'customer_id' => $_SESSION['patient_customer_id'] ?? 0,
        'name'        => $_SESSION['patient_name']     ?? '',
        'username'    => $_SESSION['patient_username'] ?? '',
    ];
}
// ── Logout ──────────────────────────────────────────────────────
// Wipes $_SESSION, clears the session cookie, and destroys the
// session server-side. unset()'ing just admin_id would leave the
// session (and anything else stored in it) alive.
function logout_admin(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    session_destroy();
}
?>
