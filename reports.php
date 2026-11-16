<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FocusFlow | Analytics & Reports</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #f8fafc;
            --bg-sidebar: #ffffff;
            --bg-card: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --sidebar-hover: #f1f5f9;
            --sidebar-active: #e0e7ff;
            --sidebar-active-text: #4338ca;
        }
        [data-theme="dark"] {
            --bg-body: #0b0f19;
            --bg-sidebar: #131b2e;
            --bg-card: #131b2e;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: #1e293b;
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --sidebar-hover: #1e293b;
            --sidebar-active: #312e81;
            --sidebar-active-text: #818cf8;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            display: flex;
            min-height: 100vh;
            transition: background 0.3s, color 0.3s;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 20px;
            box-sizing: border-box;
        }
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 30px;
            padding-left: 10px;
        }
        .brand-mark {
            background: var(--primary);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        .sidebar-menu-category {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            margin: 15px 0 8px 10px;
        }
        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .sidebar-nav a:hover {
            background: var(--sidebar-hover);
            color: var(--text-main);
        }
        .sidebar-nav a.active {
            background: var(--sidebar-active);
            color: var(--sidebar-active-text);
            font-weight: 600;
        }
        .sidebar-footer {
            margin-top: auto;
            border-top: 1px solid var(--border);
            padding-top: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-avatar-small {
            width: 32px;
            height: 32px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }
        .user-info-small {
            display: flex;
            flex-direction: column;
        }
        .user-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
        }
        .user-workspace {
            font-size: 11px;
            color: var(--text-muted);
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            height: 60px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            background: var(--bg-card);
        }
        .breadcrumb {
            font-size: 14px;
            color: var(--text-muted);
        }
        .breadcrumb span {
            color: var(--text-main);
            font-weight: 500;
        }
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .theme-toggle {
            background: none;
            border: 1px solid var(--border);
            color: var(--text-main);
            width: 36px;
            height: 36px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .user-pill {
            width: 32px;
            height: 32px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        .content-body {
            padding: 30px;
            box-sizing: border-box;
        }
        .page-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .page-header h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
            font-weight: 700;
        }
        .page-header p {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
        }
        .export-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
        }
        .export-btn:hover {
            background: #059669;
        }

        .reports-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 900px) {
            .reports-grid { grid-template-columns: 1fr; }
        }
        .report-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            min-height: 280px;
        }
        .report-card h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="breadcrumb">Workspace / <span>Reports & Analytics</span></div>
            <div class="topbar-actions">
                <button class="theme-toggle" id="themeToggle" title="Toggle Dark/Light Mode">🌙</button>
                <div class="user-pill"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="content-body">
            <div class="page-header-row">
                <div class="page-header">
                    <h1>Productivity Reports 📊</h1>
                    <p>Analyze your habits and export your data.</p>
                </div>
                <a href="export_csv.php" class="export-btn">↓ Export CSV</a>
            </div>

            <div class="reports-grid">
                <div class="report-card">
                    <h3>Tasks by Category</h3>
                    <!-- Chart container -->
                </div>
                <div class="report-card">
                    <h3>Completion Rate</h3>
                    <!-- Chart container -->
                </div>
            </div>
        </div>
    </div>

    <script>
        const themeToggle = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;

        const currentTheme = localStorage.getItem('theme') || 'light';
        htmlElement.setAttribute('data-theme', currentTheme);
        themeToggle.textContent = currentTheme === 'dark' ? '☀️' : '🌙';

        themeToggle.addEventListener('click', () => {
            let theme = htmlElement.getAttribute('data-theme');
            let newTheme = theme === 'light' ? 'dark' : 'light';
            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            themeToggle.textContent = newTheme === 'dark' ? '☀️' : '🌙';
        });
    </script>
</body>
</html>