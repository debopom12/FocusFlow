<?php
// sidebar.php - Shared Sidebar Component
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
$username = $_SESSION['username'] ?? 'DebopomGhatak';
$initials = strtoupper(substr($username, 0, 1));
?>
<aside class="sidebar" id="sidebar">
    <div>
        <!-- Brand Logo -->
        <div class="brand">
            <div class="brand-mark">F</div>
            <span>Focus<span class="brand-light">Flow</span></span>
        </div>

        <!-- Workspace Navigation -->
        <div class="workspace-label">WORKSPACE</div>
        <nav class="nav-menu">
            <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                <span class="nav-icon">▦</span> Dashboard
            </a>

            <a class="nav-link <?php echo ($current_page == 'calendar.php') ? 'active' : ''; ?>" href="calendar.php">
                <span class="nav-icon">📅</span> Calendar
            </a>

            <a class="nav-link <?php echo ($current_page == 'timer.php') ? 'active' : ''; ?>" href="timer.php">
                <span class="nav-icon">◷</span> Focus Timer
            </a>

            <a class="nav-link <?php echo ($current_page == 'reports.php' || $current_page == 'activity.php') ? 'active' : ''; ?>" href="reports.php">
                <span class="nav-icon">⌁</span> Analytics
            </a>
        </nav>

        <!-- Personal Navigation -->
        <div class="workspace-label second-label">PERSONAL</div>
        <nav class="nav-menu">
            <a class="nav-link <?php echo ($current_page == 'goals.php') ? 'active' : ''; ?>" href="goals.php">
                <span class="nav-icon">◎</span> Goals
            </a>

            <a class="nav-link <?php echo ($current_page == 'habits.php') ? 'active' : ''; ?>" href="habits.php">
                <span class="nav-icon">♧</span> Habits
            </a>

            <a class="nav-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>" href="profile.php">
                <span class="nav-icon">⚙</span> Settings
            </a>
        </nav>
    </div>

    <!-- Bottom Profile & Logout -->
    <div class="sidebar-bottom">
        <div class="profile" style="margin-bottom: 10px;">
            <div class="avatar">
                <?php echo $initials; ?>
            </div>

            <div class="profile-info">
                <strong><?php echo htmlspecialchars($username); ?></strong>
                <span>Personal workspace</span>
            </div>
        </div>

        <a href="logout.php" class="logout-link">
            🚪 Log Out
        </a>
    </div>
</aside>

<style>
/* Dark Theme Sidebar Styles matching your Dashboard */
.sidebar {
    width: 260px;
    min-height: 100vh;
    padding: 25px 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    background: #131b2e;
    border-right: 1px solid #1e293b;
    box-sizing: border-box;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 20px;
    font-weight: 800;
    color: #f8fafc;
    margin-bottom: 30px;
}

.brand-mark {
    width: 32px;
    height: 32px;
    background: #6366f1;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 800;
}

.brand-light {
    color: #818cf8;
}

.workspace-label {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
    letter-spacing: 1.5px;
    margin-bottom: 10px;
    padding-left: 10px;
}

.second-label {
    margin-top: 25px;
}

.nav-menu {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    text-decoration: none;
    color: #94a3b8;
    font-size: 14px;
    font-weight: 500;
    border-radius: 8px;
    transition: 0.2s;
}

.nav-link:hover {
    background: #1e293b;
    color: #f8fafc;
}

.nav-link.active {
    background: #312e81;
    color: #818cf8;
    font-weight: 600;
}

.nav-icon {
    width: 18px;
    text-align: center;
    font-size: 16px;
}

.sidebar-bottom {
    margin-top: auto;
    padding-top: 20px;
    border-top: 1px solid #1e293b;
}

.profile {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px;
    border-radius: 8px;
}

.avatar {
    width: 32px;
    height: 32px;
    background: #fbcfe8;
    color: #831843;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-weight: 600;
    font-size: 14px;
}

.profile-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    overflow: hidden;
}

.profile-info strong {
    font-size: 13px;
    color: #f8fafc;
}

.profile-info span {
    font-size: 11px;
    color: #94a3b8;
}

.logout-link {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #ef4444;
    font-weight: 600;
    padding: 10px 12px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    margin-top: 8px;
    transition: 0.2s;
}

.logout-link:hover {
    background: rgba(239, 68, 68, 0.1);
}
</style>