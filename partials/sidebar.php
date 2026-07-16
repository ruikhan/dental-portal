<?php
// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

// Count unread messages
if(isset($conn)) {
    $unread_count = $conn->query("SELECT COUNT(*) FROM messages WHERE is_read = 0 AND sender = 'customer'")->fetchColumn();
} else {
    $unread_count = 0;
}

function nav_active($dir, $file = '') {
    global $current_dir, $current_page;
    if ($dir && $current_dir === $dir) return 'active';
    if ($file && $current_page === $file) return 'active';
    return '';
}

// FIX: clamp to 0 so root-level pages (e.g. /index.php, /settings.php)
// don't produce a negative count and crash str_repeat() on PHP 8+.
function rel_path(): string {
    $depth = substr_count($_SERVER['PHP_SELF'], '/') - 2;
    return str_repeat('../', max(0, $depth));
}
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-tooth"></i>
        </div>
        <div class="brand-text">
            <h2>DentalPortal</h2>
            <span>Management System</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <span class="nav-group-label">Main</span>
        
        <a href="<?php echo rel_path(); ?>index.php" 
           class="nav-item <?php echo nav_active('', 'index.php'); ?>">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>

        <span class="nav-group-label">Patients</span>

        <a href="<?php echo rel_path(); ?>customers/list.php" 
           class="nav-item <?php echo nav_active('customers'); ?>">
            <i class="bi bi-people-fill"></i>
            All Patients
        </a>

        <a href="<?php echo rel_path(); ?>customers/create.php" 
           class="nav-item <?php echo ($current_dir === 'customers' && $current_page === 'create.php') ? 'active' : ''; ?>">
            <i class="bi bi-person-plus-fill"></i>
            Add Patient
        </a>

        <span class="nav-group-label">Scheduling</span>

        <a href="<?php echo rel_path(); ?>appointments/list.php" 
           class="nav-item <?php echo nav_active('appointments'); ?>">
            <i class="bi bi-calendar3"></i>
            Appointments
        </a>

        <a href="<?php echo rel_path(); ?>appointments/create.php" 
           class="nav-item <?php echo ($current_dir === 'appointments' && $current_page === 'create.php') ? 'active' : ''; ?>">
            <i class="bi bi-calendar-plus"></i>
            New Appointment
        </a>

        <span class="nav-group-label">Communication</span>

        <a href="<?php echo rel_path(); ?>messages/index.php" 
           class="nav-item <?php echo nav_active('messages'); ?>">
            <i class="bi bi-chat-dots-fill"></i>
            Messages
            <?php if($unread_count > 0): ?>
            <span class="nav-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </a>

    </nav>

    <div class="sidebar-footer">
        <div class="nav-item" style="cursor:default;">
            <i class="bi bi-shield-check"></i>
            Admin Panel v1.0
        </div>
    </div>
</aside>