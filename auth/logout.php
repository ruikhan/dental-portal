<?php
// auth/logout.php — POST-only, CSRF-protected admin logout
require_once __DIR__ . '/session.php';

// Only accept POST requests, and only with a valid CSRF token.
// This stops a plain <img src="auth/logout.php"> or link on some
// other site from silently logging an admin out (session riding).
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    http_response_code(403);
    exit('Invalid logout request.');
}

logout_admin();

// session.php's require above already ran session_start() before
// logout_admin() touched $_SESSION, so this redirect is safe.
// auth/logout.php always sits exactly one folder deep, so the
// relative path is simply login.php — no depth calc needed here.
header('Location: login.php');
exit();
?>
