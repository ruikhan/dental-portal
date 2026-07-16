<?php
// patient-portal/partials/topbar.php
// Reuses the existing .topbar / .brand-icon / .topbar-admin CSS classes
// from assets/style.css — no sidebar needed for the (currently 2-page)
// patient area, so this renders full-width directly under <body>.
$patient = current_patient();
$parts = explode(' ', $patient['name']);
$initials = strtoupper(substr($parts[0] ?? '', 0, 1)) . (isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '');
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="topbar">
    <div style="display:flex;align-items:center;gap:10px;">
        <div class="brand-icon" style="width:36px;height:36px;font-size:1rem;flex-shrink:0;">
            <i class="bi bi-tooth"></i>
        </div>
        <span style="font-family:'DM Serif Display',serif;font-size:1.1rem;color:var(--navy);white-space:nowrap;">DentalPortal</span>
    </div>

    <nav style="display:flex;gap:4px;margin-left:24px;">
        <a href="dashboard.php" class="nav-item" style="color:<?php echo $current_page==='dashboard.php' ? 'var(--teal)' : 'var(--gray-600)'; ?>;padding:8px 14px;border-radius:8px;<?php echo $current_page==='dashboard.php' ? 'background:rgba(10,143,143,0.08);' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="messages.php" class="nav-item" style="color:<?php echo $current_page==='messages.php' ? 'var(--teal)' : 'var(--gray-600)'; ?>;padding:8px 14px;border-radius:8px;<?php echo $current_page==='messages.php' ? 'background:rgba(10,143,143,0.08);' : ''; ?>">
            <i class="bi bi-chat-dots-fill"></i> Messages
        </a>
    </nav>

    <div class="topbar-right">
        <div class="topbar-admin" style="cursor:default;">
            <div class="admin-avatar"><?php echo htmlspecialchars($initials ?: 'P'); ?></div>
            <span class="admin-name"><?php echo htmlspecialchars($patient['name']); ?></span>
        </div>
        <a href="logout.php" class="topbar-icon-btn" title="Log out">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</header>

<!-- Mobile bottom tab bar — the top nav hides below 700px, so navigation
     moves here instead of disappearing entirely. -->
<nav class="patient-bottom-nav">
    <a href="dashboard.php" class="<?php echo $current_page==='dashboard.php' ? 'active' : ''; ?>">
        <i class="bi bi-speedometer2"></i><span>Dashboard</span>
    </a>
    <a href="messages.php" class="<?php echo $current_page==='messages.php' ? 'active' : ''; ?>">
        <i class="bi bi-chat-dots-fill"></i><span>Messages</span>
    </a>
</nav>

<style>
/* .nav-item outside the sidebar needs its own inline-friendly variant —
   the base .nav-item rules in style.css assume a dark sidebar background */
.topbar nav .nav-item {
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: var(--transition);
}
.topbar nav .nav-item:hover { background: var(--gray-50); color: var(--navy); }
@media (max-width: 700px) {
    .topbar nav { display: none; }
}

.patient-bottom-nav {
    display: none;
    position: fixed;
    bottom: 0; left: 0; right: 0;
    background: white;
    border-top: 1px solid var(--gray-100);
    box-shadow: 0 -4px 16px rgba(15,45,74,0.08);
    z-index: 998;
    padding-bottom: env(safe-area-inset-bottom, 0); /* iPhone home-indicator clearance */
}
.patient-bottom-nav a {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    padding: 10px 0 8px;
    text-decoration: none;
    color: var(--gray-400);
    font-size: 0.66rem;
    font-weight: 600;
}
.patient-bottom-nav a i { font-size: 1.15rem; }
.patient-bottom-nav a.active { color: var(--teal); }
@media (max-width: 700px) {
    .patient-bottom-nav { display: flex; }
    /* keep content clear of the fixed bottom bar */
    body { padding-bottom: 64px; }
}
@media (max-width: 380px) {
    .topbar .admin-name { display: none; }
}
</style>
