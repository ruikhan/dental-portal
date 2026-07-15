<header class="topbar">
    <button class="topbar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <div class="topbar-search">
        <i class="bi bi-search"></i>
        <input type="text" id="tableSearch" placeholder="Search patients, appointments...">
    </div>

    <div class="topbar-right">
        <a href="<?php echo str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/')-2); ?>messages/index.php" 
           class="topbar-icon-btn" title="Messages">
            <i class="bi bi-chat-dots"></i>
            <?php 
            if(isset($conn)) {
                $uc = $conn->query("SELECT COUNT(*) FROM messages WHERE is_read = 0 AND sender = 'customer'")->fetchColumn();
                if($uc > 0) echo '<span class="topbar-notif-dot"></span>';
            }
            ?>
        </a>
        <a href="<?php echo str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/')-2); ?>appointments/list.php" 
           class="topbar-icon-btn" title="Appointments">
            <i class="bi bi-calendar-event"></i>
        </a>
        <div class="topbar-admin">
            <div class="admin-avatar">AD</div>
            <span class="admin-name">Admin</span>
        </div>
    </div>
</header>
