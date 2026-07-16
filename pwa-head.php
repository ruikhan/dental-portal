<?php
// partials/pwa-head.php
// Include this once inside <head>...</head> on EVERY page (root pages
// and pages inside customers/, appointments/, messages/ alike).
// Absolute paths (leading "/") are used on purpose so this works
// correctly no matter how deep the current page is nested — the
// same reason sidebar.php/topbar.php use rel_path() for nav links.
?>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#0f2d4a">

<!-- Android / Chrome install prompt -->
<meta name="mobile-web-app-capable" content="yes">

<!-- iOS Safari "Add to Home Screen" support (iOS ignores manifest.json) -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="DentalPortal">
<link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
