<?php
// partials/pwa-head.php
// Include this once inside <head>...</head> on EVERY page (root pages
// and pages inside customers/, appointments/, messages/ alike):
//
//   <?php include 'partials/pwa-head.php'; ?>          (from a root page)
//   <?php include '../partials/pwa-head.php'; ?>       (from a page one folder deep)
//
// Absolute paths (leading "/") are used on purpose so this works
// correctly no matter how deep the current page is nested — the
// same reason sidebar.php/topbar.php use rel_path() for nav links.
?>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#0f2d4a">

<!-- Browser tab icon -->
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/icons/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/icons/favicon-16.png">

<!-- Android / Chrome install prompt -->
<meta name="mobile-web-app-capable" content="yes">

<!-- iOS Safari "Add to Home Screen" support (iOS ignores manifest.json,
     so it needs its own dedicated flat/full-bleed icon — see
     assets/icons/apple-touch-icon.png) -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="DentalPortal">
<link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
