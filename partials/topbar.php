<?php
// FIX: correct depth formula is (slash count - 1), not -2 — the old
// formula under-counted by one folder for every non-root page (e.g.
// customers/view.php, appointments/list.php), producing broken links
// like /customers/appointments/list.php instead of /appointments/list.php.
// max(0, ...) still guards root-level pages (index.php, settings.php)
// against a negative count, which crashes str_repeat() on PHP 8+.
$__depth = substr_count($_SERVER['PHP_SELF'], '/') - 1;
$__rel   = str_repeat('../', max(0, $__depth));
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
        <div class="topbar-admin">
            <div class="admin-avatar">AD</div>
            <span class="admin-name">Admin</span>
        </div>
<form action="<?php echo $__rel; ?>auth/logout.php" method="POST" class="topbar-icon-form" 
      onsubmit="return confirm('Are you sure you want to logout?');">
    <?php
    // FIX: topbar.php is included by every admin page, but not every
    // page loads the auth/session bootstrap that defines csrf_field()
    // first (messages/index.php was one such page — that's what caused
    // "Call to undefined function csrf_field()" and fataled the whole
    // page). Rather than trust every future include site to remember
    // the bootstrap, guard it here: use csrf_field() if it's loaded,
    // otherwise fall back to reading the session token directly so the
    // logout form still carries a valid CSRF token either way.
    if (function_exists('csrf_field')) {
        echo csrf_field();
    } elseif (isset($_SESSION['csrf_token'])) {
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
    }
    ?>
    <button type="submit" class="topbar-icon-btn" title="Logout">
        <i class="bi bi-box-arrow-right"></i>
    </button>
</form>
    </div>
</header>
