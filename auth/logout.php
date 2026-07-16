<?php
session_start();
$_SESSION = [];
session_destroy();

// Redirect to home page
header('Location: index.php');
exit;
