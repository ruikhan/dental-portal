<?php
// FIX: correct depth formula is (slash count - 1), not -2 — the old
// formula under-counted by one folder for every non-root page (e.g.
// customers/view.php, appointments/list.php), producing broken links
// like /customers/appointments/list.php instead of /appointments/list.php.
// max(0, ...) still guards root-level pages (index.php, settings.php)
// against a negative count, which crashes str_repeat() on PHP 8+.
$__depth = substr_count($_SERVER['PHP_SELF'], '/') - 1;
$__rel   = str_repeat('../', max(0, $__depth));

// Needed for the logout form's CSRF field and for showing the
// real logged-in admin's name/initials instead of hardcoded values.
$__admin = current_admin();
?>
<header class="topbar">
    <button class="topbar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>
    <div class="topbar-search">
        <i class="bi bi-search"></i>
        <input type="text" id="tableSearch" placeholder="Search patients, appointments...">
    </div>
    <div class="topbar-right">
        <a href="<?php echo $__rel; ?>messages/index.php" 
           class="topbar-icon-btn" title="Messages">
            <i class="bi bi-chat-dots"></i>
            <?php 
            if(isset($conn)) {
                $uc = $conn->query("SELECT COUNT(*) FROM messages WHERE is_read = 0 AND sender = 'customer'")->fetchColumn();
                if($uc > 0) echo '<span class="topbar-notif-dot"></span>';
            }
            ?>
        </a>
        <a href="<?php echo $__rel; ?>appointments/list.php" 
           class="topbar-icon-btn" title="Appointments">
            <i class="bi bi-calendar-event"></i>
        </a>
        <div class="topbar-admin dropdown">
            <button type="button" class="admin-avatar-btn" id="adminMenuToggle"
                    aria-haspopup="true" aria-expanded="false">
                <div class="admin-avatar"><?php echo htmlspecialchars($__admin['initials']); ?></div>
                <span class="admin-name"><?php echo htmlspecialchars($__admin['name']); ?></span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="admin-dropdown-menu" id="adminDropdownMenu">
                <form action="<?php echo $__rel; ?>auth/logout.php" method="POST" class="logout-form">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="admin-dropdown-item logout-link">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<script>
document.getElementById('adminMenuToggle')?.addEventListener('click', function(e) {
    e.stopPropagation();
    const menu = document.getElementById('adminDropdownMenu');
    const expanded = this.getAttribute('aria-expanded') === 'true';
    this.setAttribute('aria-expanded', String(!expanded));
    menu.classList.toggle('show');
});
document.addEventListener('click', function() {
    document.getElementById('adminMenuToggle')?.setAttribute('aria-expanded', 'false');
    document.getElementById('adminDropdownMenu')?.classList.remove('show');
});
</script>
