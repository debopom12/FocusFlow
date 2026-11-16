<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);
$initials = strtoupper(substr($username, 0, 2));

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Ensure table and columns exist safely
$conn->query("CREATE TABLE IF NOT EXISTS habits (
    habit_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    habit_name VARCHAR(255) NOT NULL,
    streak INT DEFAULT 0,
    last_checked DATE
)");
$conn->query("ALTER TABLE habits ADD COLUMN IF NOT EXISTS streak INT DEFAULT 0");
$conn->query("ALTER TABLE habits ADD COLUMN IF NOT EXISTS last_checked DATE");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_habit'])) {
    $stmt = $conn->prepare("INSERT INTO habits (user_id, habit_name) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $_POST['habit_name']);
    $stmt->execute();
    $stmt->close();

    header("Location: habits.php");
    exit();
}

if (isset($_GET['checkin'])) {
    $h_id = intval($_GET['checkin']);

    $stmt = $conn->prepare("SELECT streak, last_checked FROM habits WHERE habit_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $h_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res && $res['last_checked'] != $today) {
        $new_streak = ($res['last_checked'] == $yesterday) ? $res['streak'] + 1 : 1;

        $stmt = $conn->prepare("UPDATE habits SET streak = ?, last_checked = ? WHERE habit_id = ? AND user_id = ?");
        $stmt->bind_param("isii", $new_streak, $today, $h_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: habits.php");
    exit();
}

$habits = $conn->query("SELECT * FROM habits WHERE user_id = $user_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FocusFlow | Habits</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --bg: #f8fafc; --card: #fff; --text: #0f172a; --muted: #64748b;
            --border: #e2e8f0; --primary: #6366f1; --primary-hover: #4f46e5;
            --input: #fff; --soft: #eef2ff; --sidebar-width: 260px;
        }
        [data-theme="dark"] {
            --bg: #0f172a; --card: #1e293b; --text: #f8fafc; --muted: #94a3b8;
            --border: #334155; --primary: #818cf8; --primary-hover: #6366f1;
            --input: #0f172a; --soft: #283454;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Inter, sans-serif; background: var(--bg); color: var(--text); transition: .25s; }
        button, input, select { font: inherit; }
        button { cursor: pointer; }

        .app-shell { display: flex; min-height: 100vh; }
        .sidebar { width: var(--sidebar-width); flex: 0 0 var(--sidebar-width); padding: 25px 20px; background: var(--card); border-right: 1px solid var(--border); display: flex; flex-direction: column; justify-content: space-between; min-height: 100vh; position: sticky; top: 0; height: 100vh; }
        .brand { display: flex; align-items: center; gap: 12px; font-size: 24px; font-weight: 800; margin-bottom: 40px; }
        .brand-mark { width: 38px; height: 38px; background: var(--primary); color: white; display: grid; place-items: center; border-radius: 10px; }
        .brand-light { color: var(--primary); }
        .workspace-label { font-size: 12px; font-weight: 700; color: var(--muted); letter-spacing: 1.5px; margin: 0 0 15px 12px; }
        .second-label { margin-top: 30px; }
        .nav-menu { display: flex; flex-direction: column; gap: 8px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 14px; color: var(--muted); text-decoration: none; font-size: 15px; font-weight: 600; border-radius: 10px; transition: .2s; }
        .nav-link:hover, .nav-link.active { background: var(--soft); color: var(--primary); }
        .nav-icon { width: 22px; text-align: center; font-size: 18px; }
        .sidebar-bottom { padding-top: 25px; }
        .profile { display: flex; align-items: center; gap: 12px; padding: 10px; margin-bottom: 10px; }
        .avatar, .top-avatar { width: 40px; height: 40px; background: var(--primary); color: #fff; display: grid; place-items: center; border-radius: 50%; font-weight: 700; }
        .profile-info { display: flex; flex-direction: column; gap: 4px; overflow: hidden; }
        .profile-info strong { font-size: 14px; }
        .profile-info span { font-size: 12px; color: var(--muted); }
        .logout-link { display: flex; align-items: center; gap: 10px; color: #ef4444; font-weight: 600; padding: 12px 16px; border-radius: 8px; text-decoration: none; }
        .logout-link:hover { background: #ef44441a; }

        .main-content { min-width: 0; flex: 1; }
        .topbar { height: 78px; background: var(--card); border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; padding: 0 34px; }
        .breadcrumb { color: var(--muted); font-size: 14px; } .breadcrumb span { margin: 0 10px; } .breadcrumb strong { color: var(--text); }
        .topbar-actions { display: flex; align-items: center; gap: 14px; }
        .icon-button { width: 42px; height: 42px; border-radius: 10px; background: var(--bg); color: var(--text); border: 1px solid var(--border); display: grid; place-items: center; font-size: 18px; position: relative; }
        .glow-dot { position: absolute; top: 4px; right: 4px; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; }

        .page-content { padding: 30px 34px; max-width: 1600px; margin: auto; }
        .welcome-row { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 28px; }
        h1 { font-size: 30px; margin: 0; letter-spacing: -1px; }

        .primary-button { background: var(--primary); color: white; border: 0; border-radius: 9px; padding: 13px 18px; font-weight: 600; transition: .2s; }
        .primary-button:hover { background: var(--primary-hover); transform: translateY(-1px); }

        .panel { background: var(--card); border: 1px solid var(--border); border-radius: 15px; padding: 23px; }
        .content-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 22px; align-items: start; }

        .habit-form { padding: 10px 0; }
        .habit-label { display: block; margin-bottom: 18px; font-weight: 600; font-size: 14px; }
        .habit-input { width: 100%; padding: 11px; border-radius: 7px; margin-top: 7px; font-size: 15px; border: 1px solid var(--border); background: var(--input); color: var(--text); font-family: inherit; outline: none; }
        .habit-input:focus { border-color: var(--primary); }
        .habit-submit { width: 100%; margin-top: 10px; }

        .habit-item { border-bottom: 1px solid var(--border); padding: 16px 0; display: flex; justify-content: space-between; align-items: center; gap: 15px; }
        .habit-item:last-child { border-bottom: none; }
        .habit-name { font-size: 16px; font-weight: 600; }
        .habit-streak { margin: 4px 0 0; font-size: 13px; color: #f59e0b; font-weight: 600; }
        .habit-checkin { background: var(--primary); padding: 8px 14px; font-size: 13px; text-decoration: none; border-radius: 8px; color: white; }
        .habit-checkin:hover { background: var(--primary-hover); }
        .habit-done { color: #10b981; font-weight: 600; font-size: 13px; background: rgba(16, 185, 129, 0.1); padding: 8px 14px; border-radius: 8px; }
        .habit-empty { text-align: center; padding: 30px; color: var(--muted); font-size: 14px; }

        @media (max-width: 1100px) { .content-grid { grid-template-columns: 1fr; } }
        @media (max-width: 800px) { .sidebar { width: 76px; flex-basis: 76px; padding: 20px 10px; } .brand { justify-content: center; margin-bottom: 30px; } .brand > span, .workspace-label, .nav-link:not(.active) { display: none; } .nav-link { justify-content: center; padding: 13px 8px; } .profile-info, .logout-link { display: none; } }
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div>
            <div class="brand"><div class="brand-mark">F</div><span>Focus<span class="brand-light">Flow</span></span></div>
            <div class="workspace-label">WORKSPACE</div>
            <nav class="nav-menu">
                <a class="nav-link" href="dashboard.php"><span class="nav-icon">▦</span> Dashboard</a>
                <a class="nav-link" href="calendar.php"><span class="nav-icon">📅</span> Calendar</a>
                <a class="nav-link" href="timer.php"><span class="nav-icon">◷</span> Focus Timer</a>
                <a class="nav-link" href="reports.php"><span class="nav-icon">⌁</span> Analytics</a>
            </nav>
            <div class="workspace-label second-label">PERSONAL</div>
            <nav class="nav-menu">
                <a class="nav-link" href="goals.php"><span class="nav-icon">◎</span> Goals</a>
                <a class="nav-link active" href="habits.php"><span class="nav-icon">♧</span> Habits</a>
                <a class="nav-link" href="profile.php"><span class="nav-icon">⚙</span> Settings</a>
            </nav>
        </div>
        <div class="sidebar-bottom">
            <div class="profile"><div class="avatar"><?php echo $initials; ?></div><div class="profile-info"><strong><?php echo htmlspecialchars($username); ?></strong><span>Personal workspace</span></div></div>
            <a href="logout.php" class="logout-link" id="logout">🚪 Log Out</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="breadcrumb">Workspace <span>/</span> <strong>Habits</strong></div>
            <div class="topbar-actions">
                <button class="icon-button" id="themeToggle" title="Toggle dark/light mode">☾</button>
                <button class="icon-button" title="Notifications">🔔<i class="glow-dot"></i></button>
                <div class="top-avatar"><?php echo $initials; ?></div>
            </div>
        </header>
        <div class="page-content">
            <section class="welcome-row">
                <div><h1>Daily Habit Tracker 🔥</h1></div>
            </section>

            <section class="content-grid">
                <article class="panel">
                    <div class="panel-heading" style="margin-bottom: 15px;"><h2>New Habit</h2></div>
                    <form method="POST" action="" class="habit-form">
                        <label class="habit-label">
                            Habit Name
                            <input type="text" name="habit_name" class="habit-input" placeholder="e.g. Read 20 pages" required>
                        </label>
                        <button type="submit" name="add_habit" class="primary-button habit-submit">Create Habit</button>
                    </form>
                </article>

                <article class="panel">
                    <div class="panel-heading" style="margin-bottom: 15px;"><h2>Daily Tracker</h2></div>
                    <div>
                        <?php if ($habits && $habits->num_rows > 0): ?>
                            <?php while($h = $habits->fetch_assoc()): 
                                $done_today = ($h['last_checked'] == $today);
                            ?>
                                <div class="habit-item">
                                    <div>
                                        <div class="habit-name"><?php echo htmlspecialchars($h['habit_name']); ?></div>
                                        <div class="habit-streak">🔥 Streak: <?php echo intval($h['streak']); ?> days</div>
                                    </div>
                                    <?php if(!$done_today): ?>
                                        <a href="habits.php?checkin=<?php echo $h['habit_id']; ?>" class="habit-checkin">Check-in Today</a>
                                    <?php else: ?>
                                        <span class="habit-done">✓ Done</span>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="habit-empty">
                                <h3>No habits yet</h3>
                                <p>Create your first habit using the form.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            </section>
        </div>
    </main>
</div>

<script>
    if(localStorage.getItem("theme")==="dark"){
        document.documentElement.setAttribute("data-theme","dark");
        document.getElementById("themeToggle").textContent="☀";
    }

    document.getElementById("themeToggle").onclick = () => {
        const dark = document.documentElement.getAttribute("data-theme") === "dark";
        if(dark) {
            document.documentElement.removeAttribute("data-theme");
            localStorage.setItem("theme", "light");
            document.getElementById("themeToggle").textContent = "☾";
        } else {
            document.documentElement.setAttribute("data-theme", "dark");
            localStorage.setItem("theme", "dark");
            document.getElementById("themeToggle").textContent = "☀";
        }
    };
</script>
</body>
</html>