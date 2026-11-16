<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Demo User';
$initials = strtoupper(substr($username, 0, 2));

// Handle Add Task POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $title = trim($_POST['task_title']);
    $date = $_POST['task_date'];
    $category = $_POST['task_category'];
    $priority = $_POST['task_priority'];

    if (!empty($title) && !empty($date)) {
        $stmt = $conn->prepare("INSERT INTO tasks (user_id, task_title, category, due_date, priority, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("issss", $user_id, $title, $category, $date, $priority);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: dashboard.php");
    exit();
}

// Handle Task Status Toggle / Deletion via AJAX or GET if needed, or fetch from DB
$task_stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY due_date ASC");
$task_stmt->bind_param("i", $user_id);
$task_stmt->execute();
$tasks_result = $task_stmt->get_result();

$db_tasks = [];
while ($row = $tasks_result->fetch_assoc()) {
    $db_tasks[] = [
    'id' => $row['task_id'],
    'title' => $row['task_title'],
    'category' => $row['category'] ?? 'Study',
    'due' => $row['due_date'] ?? 'Today',
    'done' => (isset($row['status']) && ($row['status'] === 'completed' || $row['status'] === '1')),
    'priority' => $row['priority'] ?? 'medium'
];
}
$task_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FocusFlow | Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
    .eyebrow { color: var(--muted); font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin: 0 0 10px; }
    h1 { font-size: 30px; margin: 0; letter-spacing: -1px; }
    .wave { color: #f59e0b; }
    .primary-button { background: var(--primary); color: white; border: 0; border-radius: 9px; padding: 13px 18px; font-weight: 600; transition: .2s; }
    .primary-button:hover { background: var(--primary-hover); transform: translateY(-1px); }
    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
    .stat-card, .panel { background: var(--card); border: 1px solid var(--border); border-radius: 15px; }
    .stat-card { padding: 22px; }
    .stat-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
    .stat-label { color: var(--muted); font-size: 14px; font-weight: 500; }
    .stat-icon { width: 35px; height: 35px; border-radius: 10px; display: grid; place-items: center; font-weight: 700; }
    .purple { background: #ede9fe; color: #7c3aed; } .blue { background: #dbeafe; color: #2563eb; } .orange { background: #ffedd5; color: #ea580c; }
    .stat-value { font-size: 32px; font-weight: 800; letter-spacing: -1px; } .stat-total { color: var(--muted); font-weight: 500; }
    .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 22px; align-items: start; }
    .panel { padding: 23px; }
    .panel-heading { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; }
    .panel-heading h2 { font-size: 20px; margin: 0 0 7px; }
    .panel-subtitle { color: var(--muted); font-size: 13px; margin: 0; }
    .panel-subtitle strong { color: var(--text); }
    .task-list { display: flex; flex-direction: column; }
    .task-item { display: flex; align-items: center; gap: 13px; padding: 16px 0; border-top: 1px solid var(--border); }
    .task-check { width: 23px; height: 23px; flex-shrink: 0; border: 2px solid #cbd5e1; border-radius: 7px; background: transparent; color: white; }
    .task-check.checked { background: #10b981; border-color: #10b981; }
    .task-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 7px; }
    .task-main strong { font-size: 14px; overflow-wrap: anywhere; }
    .task-main span { color: var(--muted); font-size: 12px; }
    .task-time { margin-left: 10px; }
    .category-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 4px; }
    .blue-dot { background: #3b82f6; } .violet { background: #8b5cf6; } .green-dot { background: #10b981; } .orange-dot { background: #f59e0b; }
    .completed-task strong { text-decoration: line-through; color: var(--muted); }
    .task-menu { border: 0; background: transparent; color: var(--muted); padding: 8px; }
    .timer-card { text-align: center; }
    .timer-card select { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--input); color: var(--text); margin-bottom: 15px; }
    .timer-input-wrapper { display: flex; justify-content: center; align-items: center; gap: 10px; margin: 15px 0; }
    .timer-input-wrapper input { width: 100px; font-size: 35px; font-weight: 700; text-align: center; border: 2px solid var(--border); border-radius: 12px; padding: 5px; background: var(--input); color: var(--text); }
    .timer-input-wrapper span { color: var(--muted); font-size: 14px; }
    .timer-display { font-size: 52px; font-weight: 800; color: var(--primary); margin: 20px 0; font-variant-numeric: tabular-nums; }
    .timer-buttons { display: flex; gap: 8px; justify-content: center; margin: 20px 0; }
    .timer-btn { padding: 10px 14px; border: 0; color: white; border-radius: 8px; font-size: 13px; font-weight: 600; }
    .start { background: #10b981; } .pause { background: #f59e0b; } .reset { background: #ef4444; }
    .full-button { width: 100%; }
    .modal-backdrop { position: fixed; inset: 0; background: #0f172a80; display: none; align-items: center; justify-content: center; padding: 20px; z-index: 1000; }
    .modal { width: 100%; max-width: 480px; background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 25px; box-shadow: 0 20px 60px #0002; }
    .modal-heading { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .modal-heading h2 { margin: 0; font-size: 20px; }
    .close-modal { border: 0; background: transparent; color: var(--text); font-size: 26px; }
    .modal label { display: block; font-size: 14px; font-weight: 600; margin-top: 14px; }
    .modal input, .modal select { display: block; width: 100%; padding: 11px; margin-top: 7px; border: 1px solid var(--border); border-radius: 7px; background: var(--input); color: var(--text); }
    .modal .primary-button { margin-top: 20px; width: 100%; }
    .empty-state { color: var(--muted); text-align: center; padding: 20px 0; font-size: 14px; }
    @media (max-width: 1100px) { .content-grid { grid-template-columns: 1fr; } .stats-grid { gap: 12px; } }
    @media (max-width: 800px) { .sidebar { width: 76px; flex-basis: 76px; padding: 20px 10px; } .brand { justify-content: center; margin-bottom: 30px; } .brand > span, .workspace-label, .nav-link:not(.active) { display: none; } .nav-link { justify-content: center; padding: 13px 8px; } .nav-link.active { font-size: 0; } .nav-link.active .nav-icon { font-size: 20px; } .profile { justify-content: center; padding: 5px 0; } .profile-info, .logout-link { display: none; } .topbar { padding: 0 20px; } .page-content { padding: 24px 20px; } }
    @media (max-width: 600px) { .stats-grid { grid-template-columns: 1fr; } .welcome-row { align-items: flex-start; flex-direction: column; } h1 { font-size: 25px; } .topbar { height: 65px; } .breadcrumb { font-size: 12px; } .top-avatar { width: 34px; height: 34px; } .panel { padding: 17px; } }
  </style>
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <div>
      <div class="brand"><div class="brand-mark">F</div><span>Focus<span class="brand-light">Flow</span></span></div>
      <div class="workspace-label">WORKSPACE</div>
      <nav class="nav-menu">
        <a class="nav-link active" href="dashboard.php"><span class="nav-icon">▦</span> Dashboard</a>
        <a class="nav-link" href="calendar.php"><span class="nav-icon">📅</span> Calendar</a>
        <a class="nav-link" href="timer.php"><span class="nav-icon">◷</span> Focus Timer</a>
        <a class="nav-link" href="reports.php"><span class="nav-icon">⌁</span> Analytics</a>
      </nav>
      <div class="workspace-label second-label">PERSONAL</div>
      <nav class="nav-menu">
        <a class="nav-link" href="goals.php"><span class="nav-icon">◎</span> Goals</a>
        <a class="nav-link" href="habits.php"><span class="nav-icon">♧</span> Habits</a>
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
      <div class="breadcrumb">Workspace <span>/</span> <strong id="currentPage">Dashboard</strong></div>
      <div class="topbar-actions">
        <button class="icon-button" id="themeToggle" title="Toggle dark/light mode">☾</button>
        <button class="icon-button notification" id="notificationButton" title="Notifications">🔔<i class="glow-dot"></i></button>
        <div class="top-avatar"><?php echo $initials; ?></div>
      </div>
    </header>
    <div class="page-content">
      <section class="welcome-row">
        <div><p class="eyebrow" id="todayDate"></p><h1>Good evening, <?php echo htmlspecialchars(explode(' ', $username)[0]); ?> <span class="wave">✦</span></h1></div>
        <button class="primary-button" id="openTaskModal">＋ Add new task</button>
      </section>

      <section class="stats-grid">
        <article class="stat-card"><div class="stat-top"><span class="stat-label">Tasks completed</span><span class="stat-icon purple">✓</span></div><div class="stat-value"><span id="completedCount">01</span> <span class="stat-total">/ <span id="totalCount">04</span></span></div></article>
        <article class="stat-card"><div class="stat-top"><span class="stat-label">Focus time</span><span class="stat-icon blue">◷</span></div><div class="stat-value" id="focusTime">25m</div></article>
        <article class="stat-card"><div class="stat-top"><span class="stat-label">Productivity score</span><span class="stat-icon orange">✦</span></div><div class="stat-value"><span id="productivity">25</span><span class="stat-total">/100</span></div></article>
      </section>

      <section class="content-grid">
        <article class="panel tasks-panel">
          <div class="panel-heading"><div><h2>Your tasks</h2><p class="panel-subtitle">You have <strong id="remainingCount">3 tasks</strong> remaining</p></div></div>
          <div class="task-list" id="taskList"></div>
        </article>
        <article class="panel timer-card">
          <div class="panel-heading"><div><h2>Focus Timer</h2><p class="panel-subtitle">Set your custom focus time</p></div></div>
          <select id="timerTask"><option value="none">No specific task</option></select>
          <div class="timer-input-wrapper" id="timerSetup"><input type="number" id="minutesInput" value="25" min="1" max="120"><span>minutes</span></div>
          <div class="timer-display" id="timerDisplay" style="display:none">25:00</div>
          <div class="timer-buttons"><button class="timer-btn start" id="startTimer">Start</button><button class="timer-btn pause" id="pauseTimer">Pause</button><button class="timer-btn reset" id="resetTimer">Reset</button></div>
          <button class="primary-button full-button" id="logTime">Log Time</button>
        </article>
      </section>
    </div>
  </main>
</div>

<div class="modal-backdrop" id="taskModal">
  <div class="modal">
    <div class="modal-heading"><h2>Add a new task</h2><button class="close-modal" id="closeModal">×</button></div>
    <form id="taskForm" method="POST" action="dashboard.php">
      <input type="hidden" name="add_task" value="1">
      <label>Task title<input type="text" name="task_title" id="taskTitle" placeholder="e.g. Complete assignment" required></label>
      <label>Due Date<input type="date" name="task_date" id="taskDate" required></label>
      <label>Category<select name="task_category" id="taskCategory"><option>Study</option><option>Project</option><option>Personal</option><option>Work</option></select></label>
      <label>Priority<select name="task_priority" id="taskPriority"><option value="high">High</option><option value="medium">Medium</option><option value="low">Low</option></select></label>
      <button class="primary-button" type="submit">Create task</button>
    </form>
  </div>
</div>

<script>
  const $ = id => document.getElementById(id);
  let tasks = <?php echo json_encode($db_tasks); ?>;
  let remainingSeconds=0, elapsedSeconds=0, timerInterval=null;

  $("todayDate").textContent = new Date().toLocaleDateString(undefined,{weekday:"long",month:"long",day:"numeric",year:"numeric"}).toUpperCase();

  function categoryClass(category){ return ({Study:"blue-dot",Project:"violet",Personal:"green-dot",Work:"orange-dot"})[category] || "orange-dot"; }
  function renderTasks(){
    const list=$("taskList");
    list.innerHTML="";
    tasks.forEach(task=>{
      const item=document.createElement("div"); item.className="task-item"+(task.done?" completed-task":"");
      item.innerHTML=`<button class="task-check ${task.done?"checked":""}" aria-label="Complete task">${task.done?"✓":""}</button>
        <div class="task-main"><strong></strong><span><i class="category-dot ${categoryClass(task.category)}"></i>${task.category}<span class="task-time">◷ Due: ${task.due}</span></span></div>
        <button class="task-menu" aria-label="Delete task">🗑️</button>`;
      item.querySelector(".task-main strong").textContent=task.title;
      item.querySelector(".task-check").onclick=()=>{task.done=!task.done;renderTasks();};
      item.querySelector(".task-menu").onclick=()=>{tasks=tasks.filter(t=>t.id!==task.id);renderTasks();};
      list.appendChild(item);
    });
    if(!tasks.length) list.innerHTML='<div class="empty-state">No tasks yet. Add your first task!</div>';
    const completed=tasks.filter(t=>t.done).length;
    $("completedCount").textContent=String(completed).padStart(2,"0");
    $("totalCount").textContent=String(tasks.length).padStart(2,"0");
    $("remainingCount").textContent=(tasks.length-completed)+" tasks";
    $("productivity").textContent=tasks.length?Math.round(completed/tasks.length*100):0;
    $("timerTask").innerHTML='<option value="none">No specific task</option>'+tasks.filter(t=>!t.done).map(t=>`<option value="${t.id}">${escapeHtml(t.title)}</option>`).join("");
  }
  function escapeHtml(s){return String(s).replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));}

  $("openTaskModal").onclick=()=>$("taskModal").style.display="flex";
  $("closeModal").onclick=()=>$("taskModal").style.display="none";
  $("taskModal").onclick=e=>{if(e.target===$("taskModal"))$("taskModal").style.display="none";};
  
  $("notificationButton").onclick=()=>alert("You're all caught up! No new notifications.");

  if(localStorage.getItem("theme")==="dark"){document.documentElement.setAttribute("data-theme","dark");$("themeToggle").textContent="☀";}
  $("themeToggle").onclick=()=>{
    const dark=document.documentElement.getAttribute("data-theme")==="dark";
    if(dark){document.documentElement.removeAttribute("data-theme");localStorage.setItem("theme","light");$("themeToggle").textContent="☾";}
    else{document.documentElement.setAttribute("data-theme","dark");localStorage.setItem("theme","dark");$("themeToggle").textContent="☀";}
  };

  function updateTimer(){
    const m=String(Math.floor(remainingSeconds/60)).padStart(2,"0"),s=String(remainingSeconds%60).padStart(2,"0");
    $("timerDisplay").textContent=`${m}:${s}`;
  }
  $("startTimer").onclick=()=>{
    if(timerInterval)return;
    if(remainingSeconds<=0){remainingSeconds=(parseInt($("minutesInput").value)||25)*60;elapsedSeconds=0;$("timerSetup").style.display="none";$("timerDisplay").style.display="block";}
    timerInterval=setInterval(()=>{if(remainingSeconds>0){remainingSeconds--;elapsedSeconds++;updateTimer();}else{clearInterval(timerInterval);timerInterval=null;alert("Focus session complete!");}},1000);updateTimer();
  };
  $("pauseTimer").onclick=()=>{clearInterval(timerInterval);timerInterval=null;};
  $("resetTimer").onclick=()=>{clearInterval(timerInterval);timerInterval=null;remainingSeconds=0;elapsedSeconds=0;$("timerSetup").style.display="flex";$("timerDisplay").style.display="none";};
  $("logTime").onclick=()=>{if(elapsedSeconds<=0){alert("Start a timer first to log focus time.");return;}const mins=Math.floor(elapsedSeconds/60);$("focusTime").textContent=mins+"m";alert("Logged "+mins+" minutes of focus time (demo only).");};
  renderTasks();
</script>
</body>
</html>