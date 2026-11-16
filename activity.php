<?php
session_start();
include 'db_connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);
$initials = strtoupper(substr($username, 0, 2));

// 1. Fetch All Tasks for Stats & History
$stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY due_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks_result = $stmt->get_result();

$total_tasks = 0; 
$completed_tasks = 0; 
$total_seconds = 0; 
$all_tasks = [];

while ($row = $tasks_result->fetch_assoc()) {
    $all_tasks[] = $row; 
    $total_tasks++; 
    $total_seconds += $row['time_logged'];
    if ($row['status'] == 'completed') { 
        $completed_tasks++; 
    }
}
$stmt->close(); 

$pending_tasks = $total_tasks - $completed_tasks;
$h = floor($total_seconds / 3600); 
$m = floor(($total_seconds % 3600) / 60);
$formatted_total_time = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
$productivity_score = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

function formatTimePHP($seconds) {
    $h = floor($seconds / 3600); 
    $m = floor(($seconds % 3600) / 60); 
    $s = $seconds % 60;
    return sprintf("%02d:%02d:%02d", $h, $m, $s);
}

// 2. Fetch Data for Category Chart
$cat_stmt = $conn->prepare("SELECT category, COUNT(*) as count FROM tasks WHERE user_id = ? GROUP BY category");
$cat_stmt->bind_param("i", $user_id);
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();
$categories = []; 
$category_counts = [];
while ($row = $cat_result->fetch_assoc()) {
    $categories[] = $row['category'] ?: 'Uncategorized';
    $category_counts[] = $row['count'];
}
$cat_stmt->close();

// 3. Fetch Data for Priority Chart
$pri_stmt = $conn->prepare("SELECT priority, COUNT(*) as count FROM tasks WHERE user_id = ? GROUP BY priority");
$pri_stmt->bind_param("i", $user_id);
$pri_stmt->execute();
$pri_result = $pri_stmt->get_result();
$priorities = []; 
$priority_counts = [];
while ($row = $pri_result->fetch_assoc()) {
    $priorities[] = ucfirst($row['priority']);
    $priority_counts[] = $row['count'];
}
$pri_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>FocusFlow | Analytics</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root { --bg-body: #f8fafc; --bg-card: #ffffff; --text-main: #0f172a; --text-muted: #64748b; --border: #e2e8f0; --primary: #6366f1; --primary-hover: #4f46e5; --input-bg: #ffffff; --font-base: 16px; }
    [data-theme="dark"] { --bg-body: #0f172a; --bg-card: #1e293b; --text-main: #f8fafc; --text-muted: #94a3b8; --border: #334155; --primary: #818cf8; --primary-hover: #6366f1; --input-bg: #0f172a; }
    body { background-color: var(--bg-body); color: var(--text-main); font-size: var(--font-base) !important; transition: 0.3s; }
    .panel-heading h2 { font-size: 22px !important; } .stat-value { font-size: 34px !important; } .stat-label { font-size: 15px !important; }
    .task-main strong { font-size: 17px !important; } .nav-link { font-size: 16px !important; } .welcome-row h1 { font-size: 32px !important; }
    .sidebar, .topbar, .panel, .stat-card { background-color: var(--bg-card) !important; border-color: var(--border) !important; }
    .task-item { border-color: var(--border) !important; }
    .topbar-actions .icon-button { background: var(--bg-body); color: var(--text-main); border: 1px solid var(--border); cursor: pointer;}
    .topbar-actions .icon-button:hover { background: var(--border); }
  </style>
</head>
<body>
  <div class="app-shell">
    <aside class="sidebar" id="sidebar">
      <div class="brand"><div class="brand-mark">F</div><span>Focus<span class="brand-light">Flow</span></span></div>
      <div class="workspace-label">WORKSPACE</div>
      <nav class="nav-menu">
        <a class="nav-link" href="dashboard.php">▦ Dashboard</a>
        <a class="nav-link" href="calendar.php">📅 Calendar</a>
        <a class="nav-link" href="timer.php">◷ Focus Timer</a>
        <a class="nav-link active" href="activity.php">⌁ Analytics</a>
      </nav>
      <div class="workspace-label second-label">PERSONAL</div>
      <nav class="nav-menu">
        <a class="nav-link" href="goals.php">◎ Goals</a>
        <a class="nav-link" href="habits.php">♧ Habits</a>
        <a class="nav-link" href="profile.php">⚙ Settings</a>
      </nav>
    </aside>

    <main class="main-content">
      <header class="topbar">
        <div class="breadcrumb">Workspace <span>/</span> <strong>Analytics</strong></div>
        <div class="topbar-actions">
          <button class="icon-button" id="themeToggle" onclick="toggleTheme()" title="Toggle Dark/Light Mode">☾</button>
          <div class="top-avatar"><?php echo $initials; ?></div>
        </div>
      </header>
      <div class="page-content">
        <section class="welcome-row">
            <div>
                <h1>Productivity Analytics 📊</h1>
                <p class="subtitle">Review your progress, view charts, and export data.</p>
            </div>
            <button class="primary-button" onclick="window.location.href='export_csv.php'" style="background: #10b981; font-size: 16px; padding: 12px 20px;"><span>⬇</span> Export to CSV</button>
        </section>

        <section class="stats-grid" style="margin-bottom: 30px;">
          <article class="stat-card"><div class="stat-top"><span class="stat-label">Total Time Logged</span><span class="stat-icon blue">◷</span></div><div class="stat-value"><?php echo $formatted_total_time; ?></div></article>
          <article class="stat-card"><div class="stat-top"><span class="stat-label">Completed Tasks</span><span class="stat-icon purple">✓</span></div><div class="stat-value"><?php echo sprintf("%02d", $completed_tasks); ?> <span class="stat-total" style="font-size:20px;">/ <?php echo $total_tasks; ?></span></div></article>
          <article class="stat-card"><div class="stat-top"><span class="stat-label">Pending Tasks</span><span class="stat-icon orange">✦</span></div><div class="stat-value"><?php echo sprintf("%02d", $pending_tasks); ?></div></article>
        </section>

        <section class="content-grid" style="grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
          <article class="panel">
            <div class="panel-heading"><div><h2>Tasks by Category</h2></div></div>
            <div style="padding: 20px; position: relative; height: 280px; display:flex; justify-content:center;">
              <canvas id="categoryChart"></canvas>
            </div>
          </article>
          <article class="panel">
            <div class="panel-heading"><div><h2>Tasks by Priority</h2></div></div>
            <div style="padding: 20px; position: relative; height: 280px; display:flex; justify-content:center;">
              <canvas id="priorityChart"></canvas>
            </div>
          </article>
        </section>

        <section class="content-grid" style="grid-template-columns: 1fr;">
          <article class="panel tasks-panel">
            <div class="panel-heading"><div><h2>Complete Task History</h2></div></div>
            <div class="task-list">
              <?php if (count($all_tasks) > 0) {
                  foreach ($all_tasks as $row) {
                      $is_completed = $row['status'] == 'completed';
                      $p_class = strtolower($row['priority'] ?? 'medium');
                      echo "<div class='task-item ".($is_completed?'completed-task':'')."' style='cursor:default;'><div class='task-main' style='margin-left:0;'>";
                      echo "<strong>" . htmlspecialchars($row['task_title']) . "</strong><span>Due: " . htmlspecialchars($row['due_date']) . " • Time Logged: " . formatTimePHP($row['time_logged']) . "</span>";
                      echo "</div><span class='priority $p_class'>" . ($is_completed?'Completed':'Pending') . "</span></div>";
                  }
              } else { echo "<p style='color: var(--text-muted); font-size: 15px; padding: 20px;'>No task history yet.</p>"; } ?>
            </div>
          </article>
        </section>
      </div>
    </main>
  </div>
  <script>
    const themeBtn = document.getElementById('themeToggle');
    if (localStorage.getItem('theme') === 'dark') { document.documentElement.setAttribute('data-theme', 'dark'); themeBtn.innerText = '☀'; }
    function toggleTheme() {
        if (document.documentElement.getAttribute('data-theme') === 'dark') { document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme', 'light'); themeBtn.innerText = '☾'; } 
        else { document.documentElement.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark'); themeBtn.innerText = '☀'; }
    }

    const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 14 } } } } };

    new Chart(document.getElementById('categoryChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($categories); ?>,
            datasets: [{
                data: <?php echo json_encode($category_counts); ?>,
                backgroundColor: ['#6366f1', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 0
            }]
        },
        options: chartOptions
    });

    new Chart(document.getElementById('priorityChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($priorities); ?>,
            datasets: [{
                label: 'Number of Tasks',
                data: <?php echo json_encode($priority_counts); ?>,
                backgroundColor: ['#ef4444', '#f59e0b', '#10b981'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
  </script>
</body>
</html>