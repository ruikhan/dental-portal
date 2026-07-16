<?php
require_once '../auth/session.php';
// Deliberately unset only the patient_* keys rather than a full
// session_destroy(). The admin panel and patient portal currently share
// one session cookie across the whole site — a full destroy here would
// also log out an admin who happens to be signed into both areas in the
// same browser. (auth/logout.php has the same theoretical issue on the
// admin side; worth applying the same fix there in a follow-up.)
unset($_SESSION['patient_id'], $_SESSION['patient_customer_id'], $_SESSION['patient_name'], $_SESSION['patient_username']);
header('Location: login.php');
exit();
