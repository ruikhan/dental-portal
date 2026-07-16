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
    <?php echo csrf_field(); ?>
    <button type="submit" class="topbar-icon-btn" title="Logout">
        <i class="bi bi-box-arrow-right"></i>
    </button>
</form>
