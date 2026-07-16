<?php
// logout.php — place this at your project ROOT (same level as index.php)
require_once __DIR__ . '/auth/session.php';

logout_admin();

// Redirect to home page
header('Location: index.php');
exit();
