<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$alert = "";

$name_parts = explode(' ', $username);
$initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));

// Account deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    $conn->query("DELETE FROM tasks WHERE user_id = $user_id");
    $conn->query("DELETE FROM goals WHERE user_id = $user_id");
    $conn->query("DELETE FROM habits WHERE user_id = $user_id");
    $conn->query("DELETE FROM user_streaks WHERE user_id = $user_id");
    $conn->query("DELETE FROM notifications WHERE user_id = $user_id");
    $conn->query("DELETE FROM users WHERE user_id = $user_id");
    session_destroy();
    header("Location: register.php");
    exit();
}

// Profile photo upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_photo'])) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target_file = $target_dir . time() . '_' . basename($_FILES["profile_pic"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if (in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $target_file, $user_id);
                $stmt->execute();
                $stmt->close();
                $alert = "<div style='color:#10b981;margin-bottom:15px;font-weight:600;'>Photo updated successfully!</div>";
            }
        }
    } else {
        $alert = "<div style='color:#ef4444;margin-bottom:15px;font-weight:600;'>Invalid file format.</div>";
    }
}

// Remove profile photo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_photo'])) {
    $empty_photo = NULL;
    $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $empty_photo, $user_id);
        $stmt->execute();
        $stmt->close();
        $alert = "<div style='color:#10b981;margin-bottom:15px;font-weight:600;'>Profile photo removed successfully!</div>";
    }
}

// Password update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $curr = $_POST['current_password'];
    $new = $_POST['new_password'];
    $conf = $_POST['confirm_password'];

    $result = $conn->query("SELECT password FROM users WHERE user_id = $user_id");
    if ($result && $user = $result->fetch_assoc()) {
        if (password_verify($curr, $user['password'])) {
            if ($new === $conf) {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password = '$hash' WHERE user_id = $user_id");
                $alert = "<div style='color:#10b981;margin-bottom:15px;font-weight:600;'>Password updated successfully!</div>";
            } else {
                $alert = "<div style='color:#ef4444;margin-bottom:15px;font-weight:600;'>Passwords do not match.</div>";
            }
        } else {
            $alert = "<div style='color:#ef4444;margin-bottom:15px;font-weight:600;'>Current password is incorrect.</div>";
        }
    }
}

$user_query = $conn->query("SELECT username, email, profile_photo FROM users WHERE user_id = $user_id");
$user_info = $user_query ? $user_query->fetch_assoc() : ['username' => $username, 'email' => '', 'profile_photo' => ''];
$profile_photo = $user_info['profile_photo'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FocusFlow | Settings & Profile</title>
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
    h1 { font-size: 30px; margin: 0; letter-spacing: -1px; }
    .primary-button { background: var(--primary); color: white; border: 0; border-radius: 9px; padding: 13px 18px; font-weight: 600; transition: .2s; }
    .primary-button:hover { background: var(--primary-hover); transform: translateY(-1px); }
    .panel { background: var(--card); border: 1px solid var(--border); border-radius: 15px; padding: 25px; margin-bottom: 20px; }
    .settings-input { width: 100%; border: 1px solid var(--border); border-radius: 8px; padding: 11px; margin-top: 7px; margin-bottom: 15px; background: var(--input); color: var(--text); font-family: inherit; }
    .settings-label { display: block; font-size: 14px; font-weight: 600; margin-top: 14px; }
    .profile-avatar { width: 80px; height: 80px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 600; margin-bottom: 15px; object-fit: cover; }
    .remove-btn { width: 100%; border-radius: 8px; background: transparent; border: 1px solid #ef4444; color: #ef4444; padding: 12px; margin-top: 10px; cursor: pointer; font-weight: 600; }
    .remove-btn:hover { background: rgba(239, 68, 68, 0.1); }
    .danger-panel { border: 1px solid #fecaca; background: rgba(239, 68, 68, 0.05); }
    .danger-heading h2 { color: #ef4444; }
    .danger-button { width: 100%; background: #ef4444; }
    .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
    @media (max-width: 800px) { .sidebar { width: 76px; flex-basis: 76px; padding: 20px 10px; } .brand { justify-content: center; margin-bottom: 30px; } .brand > span, .workspace-label, .nav-link:not(.active) { display: none; } .nav-link { justify-content: center; padding: 13px 8px; } .profile-info, .logout-link { display: none; } .content-grid { grid-template-columns: 1fr; } }
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
        <a class="nav-link" href="habits.php"><span class="nav-icon">♧</span> Habits</a>
        <a class="nav-link active" href="profile.php"><span class="nav-icon">⚙</span> Settings</a>
      </nav>
    </div>
    <div class="sidebar-bottom">
      <div class="profile"><div class="avatar"><?php echo $initials; ?></div><div class="profile-info"><strong><?php echo htmlspecialchars($username); ?></strong><span>Personal workspace</span></div></div>
      <a href="logout.php" class="logout-link" id="logout">🚪 Log Out</a>
    </div>
  </aside>

  <main class="main-content">
    <header class="topbar">
      <div class="breadcrumb">Workspace <span>/</span> <strong>Settings</strong></div>
      <div class="topbar-actions">
        <button class="icon-button" id="themeToggle" title="Toggle dark/light mode">☾</button>
        <button class="icon-button" title="Notifications">🔔<i class="glow-dot"></i></button>
        <div class="top-avatar"><?php echo $initials; ?></div>
      </div>
    </header>
    <div class="page-content">
      <section class="welcome-row">
        <div><h1>Account Settings ⚙️</h1></div>
      </section>

      <?php echo $alert; ?>

      <section class="content-grid">
        <div>
          <article class="panel">
            <h2>Profile Details</h2>
            <div style="padding-top: 15px;">
              <?php if(!empty($profile_photo)): ?>
                <img src="<?php echo htmlspecialchars($profile_photo); ?>" class="profile-avatar" style="border:1px solid var(--border);">
              <?php else: ?>
                <div class="profile-avatar"><?php echo $initials; ?></div>
              <?php endif; ?>
              <div style="margin-bottom:15px;"><span style="color:var(--muted);font-size:13px;">Username</span><br><strong><?php echo htmlspecialchars($user_info['username'] ?? $username); ?></strong></div>
              <div><span style="color:var(--muted);font-size:13px;">Email Address</span><br><strong><?php echo htmlspecialchars($user_info['email'] ?? ''); ?></strong></div>
            </div>
          </article>

          <article class="panel" style="margin-top:20px;">
            <h2>Update Photo</h2>
            <form method="POST" action="" enctype="multipart/form-data" style="padding-top: 15px;">
              <input type="file" name="profile_pic" accept="image/png,image/jpeg,image/gif" class="settings-input" required>
              <button type="submit" name="update_photo" class="primary-button" style="width:100%;">Upload Photo</button>
            </form>
            <?php if(!empty($profile_photo)): ?>
              <form method="POST" action="" onsubmit="return confirm('Remove profile picture?');">
                <button type="submit" name="delete_photo" class="remove-btn">Remove Current Photo</button>
              </form>
            <?php endif; ?>
          </article>

          <article class="panel danger-panel" style="margin-top:20px;">
            <div class="danger-heading"><h2>Danger Zone</h2></div>
            <p style="font-size:14px;color:var(--muted);margin:15px 0;">Once you delete your account, there is no going back.</p>
            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete your account?');">
              <button type="submit" name="delete_account" class="primary-button danger-button">Delete Account</button>
            </form>
          </article>
        </div>

        <article class="panel">
          <h2>Security Settings</h2>
          <form method="POST" action="" style="padding-top: 15px;">
            <label class="settings-label">Current Password</label>
            <input type="password" name="current_password" class="settings-input" required>
            <label class="settings-label">New Password</label>
            <input type="password" name="new_password" class="settings-input" required minlength="6">
            <label class="settings-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="settings-input" required minlength="6">
            <button type="submit" name="update_password" class="primary-button" style="width:100%;margin-top:10px;">Update Password</button>
          </form>
        </article>
      </section>
    </div>
  </main>
</div>
<script>
  if(localStorage.getItem("theme")==="dark"){document.documentElement.setAttribute("data-theme","dark");document.getElementById("themeToggle").textContent="☀";}
  document.getElementById("themeToggle").onclick=()=>{
    const dark=document.documentElement.getAttribute("data-theme")==="dark";
    if(dark){document.documentElement.removeAttribute("data-theme");localStorage.setItem("theme","light");document.getElementById("themeToggle").textContent="☾";}
    else{document.documentElement.setAttribute("data-theme","dark");localStorage.setItem("theme","dark");document.getElementById("themeToggle").textContent="☀";}
  };
</script>
</body>
</html>