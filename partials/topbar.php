<div class="topbar-admin dropdown">
    <button class="admin-avatar-btn" id="adminMenuToggle" aria-haspopup="true" aria-expanded="false">
        <div class="admin-avatar"><?php echo htmlspecialchars(current_admin()['initials']); ?></div>
        <span class="admin-name"><?php echo htmlspecialchars(current_admin()['name']); ?></span>
        <i class="bi bi-chevron-down"></i>
    </button>
    <div class="admin-dropdown-menu" id="adminDropdownMenu">
        <a href="<?php echo $__rel; ?>auth/logout.php" class="admin-dropdown-item logout-link">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>
