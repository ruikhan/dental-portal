<div class="topbar-admin dropdown" id="adminMenuToggle" role="button" tabindex="0"
     aria-haspopup="true" aria-expanded="false">
    <div class="admin-avatar">
        <?php echo htmlspecialchars($__admin['initials']); ?>
        <span class="admin-status-dot"></span>
    </div>
    <span class="admin-name"><?php echo htmlspecialchars($__admin['name']); ?></span>
    <i class="bi bi-chevron-down admin-chevron"></i>

    <div class="admin-dropdown-menu" id="adminDropdownMenu">
        <div class="admin-dropdown-header">
            <div class="admin-avatar admin-avatar-lg">
                <?php echo htmlspecialchars($__admin['initials']); ?>
            </div>
            <div class="admin-dropdown-info">
                <span class="admin-dropdown-name"><?php echo htmlspecialchars($__admin['name']); ?></span>
                <span class="admin-dropdown-role"><?php echo htmlspecialchars(ucfirst($__admin['role'])); ?></span>
            </div>
        </div>
        <div class="admin-dropdown-divider"></div>
        <form action="<?php echo $__rel; ?>auth/logout.php" method="POST" class="logout-form">
            <?php echo csrf_field(); ?>
            <button type="submit" class="admin-dropdown-item logout-link">
                <span class="dropdown-item-icon"><i class="bi bi-box-arrow-right"></i></span>
                <span>Logout</span>
            </button>
        </form>
    </div>
</div>


<script>
(function () {
    const trigger = document.getElementById('adminMenuToggle');
    const menu = document.getElementById('adminDropdownMenu');
    if (!trigger || !menu) return;

    function closeMenu() {
        trigger.setAttribute('aria-expanded', 'false');
        menu.classList.remove('show');
    }
    function toggleMenu(e) {
        e.stopPropagation();
        const open = trigger.getAttribute('aria-expanded') === 'true';
        trigger.setAttribute('aria-expanded', String(!open));
        menu.classList.toggle('show');
    }

    trigger.addEventListener('click', toggleMenu);
    trigger.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleMenu(e); }
        if (e.key === 'Escape') closeMenu();
    });
    document.addEventListener('click', closeMenu);
})();
</script>
