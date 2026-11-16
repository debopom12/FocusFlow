<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';
$user_id = $_SESSION['user_id'];

// Handle Event Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $title = trim($_POST['event_title']);
    $date = $_POST['event_date'];
    $category = $_POST['category'];

    if (!empty($title) && !empty($date)) {
        $stmt = $conn->prepare("INSERT INTO calendar_events (user_id, event_title, event_date, category) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $title, $date, $category);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: calendar.php?month=" . date('m', strtotime($date)) . "&year=" . date('Y', strtotime($date)));
    exit();
}

// Handle Event Deletion
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $del_stmt = $conn->prepare("DELETE FROM calendar_events WHERE event_id = ? AND user_id = ?");
    $del_stmt->bind_param("ii", $delete_id, $user_id);
    $del_stmt->execute();
    $del_stmt->close();
    header("Location: calendar.php");
    exit();
}

// Month & Year Navigation
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_day_timestamp = mktime(0, 0, 0, $month, 1, $year);
$start_day_of_week = date('w', $first_day_timestamp);
$month_name = date('F Y', $first_day_timestamp);

// Fetch Events for this User & Month
$start_date_str = sprintf('%04d-%02d-01', $year, $month);
$end_date_str = sprintf('%04d-%02d-%02d', $year, $month, $days_in_month);

$ev_stmt = $conn->prepare("SELECT event_id, event_title, event_date, category FROM calendar_events WHERE user_id = ? AND event_date BETWEEN ? AND ?");
$ev_stmt->bind_param("iss", $user_id, $start_date_str, $end_date_str);
$ev_stmt->execute();
$ev_result = $ev_stmt->get_result();

$events = [];
while ($row = $ev_result->fetch_assoc()) {
    $events[$row['event_date']][] = $row;
}
$ev_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FocusFlow | Calendar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="style.css">
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

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling matching Dashboard */
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
            background: #fbcfe8;
            color: #831843;
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

        /* Main Content Layout */
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
            gap: 12px;
        }
        .topbar-icon-btn {
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

        .add-event-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
        }
        .add-event-btn:hover {
            background: var(--primary-hover);
        }

        .calendar-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }
        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .calendar-nav h2 {
            margin: 0;
            font-size: 18px;
        }
        .nav-buttons a {
            background: var(--bg-body);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 6px 12px;
            font-size: 13px;
            text-decoration: none;
            border-radius: 6px;
            margin-left: 5px;
            font-weight: 500;
        }
        .nav-buttons a:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            border-top: 1px solid var(--border);
            border-left: 1px solid var(--border);
        }
        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            font-size: 12px;
            color: var(--text-muted);
            padding: 10px;
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            background: var(--bg-body);
        }
        .calendar-cell {
            min-height: 110px;
            padding: 8px;
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            box-sizing: border-box;
            background: var(--bg-card);
        }
        .calendar-cell.empty {
            background: rgba(0,0,0,0.05);
        }
        .day-number {
            font-weight: 600;
            margin-bottom: 6px;
            display: inline-block;
        }
        .event-pill {
            background: rgba(99, 102, 241, 0.2);
            color: #818cf8;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-bottom: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
        }
        .event-delete {
            color: #fca5a5;
            text-decoration: none;
            font-weight: bold;
            font-size: 12px;
            margin-left: 6px;
        }
        .event-delete:hover {
            color: #ef4444;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border);
            padding: 30px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
        }
        .modal-content h3 { margin-top: 0; }
        .modal-form { display: flex; flex-direction: column; gap: 15px; }
        .modal-form label { font-size: 13px; font-weight: 500; display: flex; flex-direction: column; gap: 6px; }
        .modal-form input, .modal-form select {
            background: var(--input-bg);
            color: var(--text-main);
            border: 1px solid var(--border);
            padding: 10px;
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
        }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
        .btn-cancel { background: none; border: 1px solid var(--border); color: var(--text-main); padding: 8px 14px; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="breadcrumb">Workspace / <span>Calendar</span></div>
            <div class="topbar-actions">
                <button class="topbar-icon-btn" title="Toggle Theme">☀️</button>
                <button class="topbar-icon-btn" title="Notifications">🔔</button>
                <div class="user-pill"><?php echo strtoupper(substr($_SESSION['username'] ?? 'D', 0, 1)); ?></div>
            </div>
        </div>

        <div class="content-body">
            <div class="page-header-row">
                <div class="page-header">
                    <h1>Your Schedule 📅</h1>
                    <p>Plan your month, schedule deadlines, and organize your events.</p>
                </div>
                <button class="add-event-btn" onclick="openModal()">+ Add Event</button>
            </div>

            <div class="calendar-card">
                <div class="calendar-nav">
                    <h2><?php echo $month_name; ?></h2>
                    <div class="nav-buttons">
                        <a href="calendar.php?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>">← Prev</a>
                        <a href="calendar.php">Today</a>
                        <a href="calendar.php?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>">Next →</a>
                    </div>
                </div>

                <div class="calendar-grid">
                    <div class="calendar-day-header">Sun</div>
                    <div class="calendar-day-header">Mon</div>
                    <div class="calendar-day-header">Tue</div>
                    <div class="calendar-day-header">Wed</div>
                    <div class="calendar-day-header">Thu</div>
                    <div class="calendar-day-header">Fri</div>
                    <div class="calendar-day-header">Sat</div>
                    
                    <?php
                    for ($i = 0; $i < $start_day_of_week; $i++) {
                        echo '<div class="calendar-cell empty"></div>';
                    }

                    for ($day = 1; $day <= $days_in_month; $day++) {
                        $current_date_key = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        echo '<div class="calendar-cell">';
                        echo '<span class="day-number">' . $day . '</span>';
                        
                        if (isset($events[$current_date_key])) {
                            foreach ($events[$current_date_key] as $ev) {
                                echo '<div class="event-pill"><span>' . htmlspecialchars($ev['event_title']) . '</span>';
                                echo '<a href="calendar.php?delete_id=' . $ev['event_id'] . '" class="event-delete" title="Delete Event">×</a>';
                                echo '</div>';
                            }
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div class="modal" id="eventModal">
        <div class="modal-content">
            <h3>Add New Event</h3>
            <form method="POST" action="" class="modal-form">
                <label>
                    Event Title
                    <input type="text" name="event_title" placeholder="e.g. Project Deadline" required>
                </label>
                <label>
                    Date
                    <input type="date" name="event_date" value="<?php echo date('Y-m-d'); ?>" required>
                </label>
                <label>
                    Category
                    <select name="category">
                        <option value="Work">Work</option>
                        <option value="Study">Study</option>
                        <option value="Personal">Personal</option>
                    </select>
                </label>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="add_event" class="add-event-btn">Save Event</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('eventModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('eventModal').style.display = 'none';
        }
    </script>
</body>
</html>