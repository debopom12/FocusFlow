<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';
$user_id = $_SESSION['user_id'];

// Fetch user tasks for the timer dropdown
$task_stmt = $conn->prepare("SELECT task_id, task_title FROM tasks WHERE user_id = ? AND status = 'pending'");
$task_stmt->bind_param("i", $user_id);
$task_stmt->execute();
$tasks_result = $task_stmt->get_result();

// Handle Time Logging
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_time'])) {
    $task_id = $_POST['task_id'];
    $added_time = (int)$_POST['time_spent']; // in seconds

    if ($task_id != 'none' && $added_time > 0) {
        $stmt = $conn->prepare("UPDATE tasks SET time_logged = time_logged + ? WHERE task_id = ? AND user_id = ?");
        $stmt->bind_param("iii", $added_time, $task_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: timer.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FocusFlow | Focus Timer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
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
            --input-bg: #0b0f19;
        }
        [data-theme="light"] {
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
            --input-bg: #ffffff;
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

        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 20px;
            box-sizing: border-box;
            height: 100vh;
            position: sticky;
            top: 0;
        }
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 25px;
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
            flex-direction: column;
            gap: 12px;
        }
        .user-footer-info {
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
        .logout-link {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ef4444;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .logout-link:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        /* Main Content */
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
            padding: 40px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .page-header {
            width: 100%;
            max-width: 600px;
            margin-bottom: 20px;
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

        /* Timer Card */
        .timer-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-sizing: border-box;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }
        .timer-label {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 10px;
            font-weight: 500;
        }
        .task-select {
            width: 100%;
            background: var(--input-bg);
            color: var(--text-main);
            border: 1px solid var(--border);
            padding: 12px;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            margin-bottom: 30px;
            outline: none;
        }
        .timer-display {
            font-size: 64px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: 2px;
            margin-bottom: 30px;
        }
        .timer-controls {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 25px;
        }
        .btn-timer {
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            color: white;
            font-family: 'Inter', sans-serif;
        }
        .btn-start { background: #10b981; }
        .btn-start:hover { background: #059669; }
        .btn-pause { background: #f59e0b; }
        .btn-pause:hover { background: #d97706; }
        .btn-reset { background: #ef4444; }
        .btn-reset:hover { background: #dc2626; }

        .save-time-btn {
            background: var(--primary);
            color: white;
            border: none;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }
        .save-time-btn:hover {
            background: var(--primary-hover);
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="breadcrumb">Workspace / <span>Focus Timer</span></div>
            <div class="topbar-actions">
                <button class="theme-toggle" id="themeToggle" title="Toggle Dark/Light Mode">☀️</button>
                <div class="user-pill"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="content-body">
            <div class="page-header">
                <h1>Focus Timer ⏱️</h1>
                <p>Use the Pomodoro technique to maintain deep focus.</p>
            </div>

            <div class="timer-card">
                <form method="POST" action="" id="timerForm">
                    <div class="timer-label">Working on:</div>
                    <select name="task_id" id="taskSelect" class="task-select">
                        <option value="none">No specific task (Just focus)</option>
                        <?php while ($t = $tasks_result->fetch_assoc()): ?>
                            <option value="<?php echo $t['task_id']; ?>"><?php echo htmlspecialchars($t['task_title']); ?></option>
                        <?php endwhile; ?>
                    </select>

                    <div class="timer-display" id="timerDisplay">25:00</div>

                    <div class="timer-controls">
                        <button type="button" class="btn-timer btn-start" id="startBtn">Start</button>
                        <button type="button" class="btn-timer btn-pause" id="pauseBtn">Pause</button>
                        <button type="button" class="btn-timer btn-reset" id="resetBtn">Reset</button>
                    </div>

                    <input type="hidden" name="time_spent" id="timeSpentInput" value="0">
                    <button type="submit" name="save_time" class="save-time-btn">Save Session Time</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Timer Logic
        let totalSeconds = 25 * 60;
        let elapsedSeconds = 0;
        let timerInterval = null;
        let isRunning = false;

        const display = document.getElementById('timerDisplay');
        const startBtn = document.getElementById('startBtn');
        const pauseBtn = document.getElementById('pauseBtn');
        const resetBtn = document.getElementById('resetBtn');
        const timeSpentInput = document.getElementById('timeSpentInput');

        function updateDisplay() {
            let minutes = Math.floor(totalSeconds / 60);
            let seconds = totalSeconds % 60;
            display.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        startBtn.addEventListener('click', () => {
            if (!isRunning && totalSeconds > 0) {
                isRunning = true;
                timerInterval = setInterval(() => {
                    if (totalSeconds > 0) {
                        totalSeconds--;
                        elapsedSeconds++;
                        timeSpentInput.value = elapsedSeconds;
                        updateDisplay();
                    } else {
                        clearInterval(timerInterval);
                        isRunning = false;
                        alert('Focus session complete!');
                    }
                }, 1000);
            }
        });

        pauseBtn.addEventListener('click', () => {
            clearInterval(timerInterval);
            isRunning = false;
        });

        resetBtn.addEventListener('click', () => {
            clearInterval(timerInterval);
            isRunning = false;
            totalSeconds = 25 * 60;
            elapsedSeconds = 0;
            timeSpentInput.value = 0;
            updateDisplay();
        });

        // Dark / Light Mode Toggle
        const themeToggle = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;

        const currentTheme = localStorage.getItem('theme') || 'dark';
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