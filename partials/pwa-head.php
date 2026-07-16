<?php
/*
 * partials/pwa-head.php
 * Include this once inside <head>...</head> on EVERY page (root pages
 * and pages inside customers/, appointments/, messages/ alike):
 *
 *   include 'partials/pwa-head.php';          (from a root page)
 *   include '../partials/pwa-head.php';       (from a page one folder deep)
 */
?>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#0f2d4a">

<!-- Browser tab icon -->
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/icons/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/icons/favicon-16.png">

<!-- Android / Chrome install prompt -->
<meta name="mobile-web-app-capable" content="yes">

<!-- iOS Safari "Add to Home Screen" support -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="DentalPortal">
<link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">