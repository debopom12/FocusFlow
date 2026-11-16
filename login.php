<?php
session_start();
include 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$alert = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];

            // Streak Calculation Logic on Login
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));

            $streak_stmt = $conn->prepare("SELECT current_streak, longest_streak, last_login FROM user_streaks WHERE user_id = ?");
            $streak_stmt->bind_param("i", $user['user_id']);
            $streak_stmt->execute();
            $streak_result = $streak_stmt->get_result();

            if ($streak_result->num_rows === 1) {
                $streak_data = $streak_result->fetch_assoc();
                $last_login = $streak_data['last_login'];
                $current = $streak_data['current_streak'];
                $longest = $streak_data['longest_streak'];

                if ($last_login == $yesterday) {
                    $current++;
                    if ($current > $longest) { $longest = $current; }
                    $update_stmt = $conn->prepare("UPDATE user_streaks SET current_streak = ?, longest_streak = ?, last_login = ? WHERE user_id = ?");
                    $update_stmt->bind_param("iisi", $current, $longest, $today, $user['user_id']);
                    $update_stmt->execute();
                } elseif ($last_login < $yesterday) {
                    $current = 1;
                    $update_stmt = $conn->prepare("UPDATE user_streaks SET current_streak = ?, last_login = ? WHERE user_id = ?");
                    $update_stmt->bind_param("isi", $current, $today, $user['user_id']);
                    $update_stmt->execute();
                }
            } else {
                $insert_streak = $conn->prepare("INSERT INTO user_streaks (user_id, current_streak, longest_streak, last_login) VALUES (?, 1, 1, ?)");
                $insert_streak->bind_param("is", $user['user_id'], $today);
                $insert_streak->execute();
            }
            $streak_stmt->close();

            header("Location: dashboard.php");
            exit();
        } else {
            $alert = "<div class='alert-error'>Invalid email or password.</div>";
        }
    } else {
        $alert = "<div class='alert-error'>Invalid email or password.</div>";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FocusFlow | Log In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #0b0f19;
            --bg-card: #131b2e;
            --bg-left: #0e1626;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: #1e293b;
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --input-bg: #0b0f19;
            --input-border: #334155;
            --focus-ring: rgba(99, 102, 241, 0.2);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .split-card {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            width: 100%;
            max-width: 850px;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            margin: 20px;
        }

        @media (max-width: 768px) {
            .split-card { grid-template-columns: 1fr; }
            .auth-left { display: none; }
        }

        .auth-left {
            background: linear-gradient(135deg, #0e1626 0%, #172038 100%);
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-right: 1px solid var(--border);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 700;
            color: var(--text-main);
        }
        .brand-mark {
            background: var(--primary);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .brand-light {
            color: #818cf8;
        }

        .left-content {
            margin: auto 0;
        }
        .eyebrow {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #f59e0b;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        .left-content h1 {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.2;
            margin: 0 0 15px 0;
            color: var(--text-main);
        }
        .left-content h1 span {
            color: #818cf8;
        }
        .left-content p {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.5;
            margin: 0;
        }
        .left-footer {
            font-size: 11px;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .auth-right {
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-header {
            margin-bottom: 25px;
        }
        .auth-header h2 {
            margin: 0 0 6px 0;
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
        }
        .auth-header p {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        label {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-main);
        }

        input[type="email"], input[type="password"] {
            font-family: 'Inter', sans-serif;
            background: var(--input-bg);
            color: var(--text-main);
            border: 1px solid var(--input-border);
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease;
        }
        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--focus-ring);
        }

        .primary-button {
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: background 0.2s;
            margin-top: 5px;
        }
        .primary-button:hover {
            background: var(--primary-hover);
        }

        .auth-footer {
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
            color: var(--text-muted);
        }
        .auth-footer a {
            color: #818cf8;
            text-decoration: none;
            font-weight: 500;
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 500;
        }
    </style>
</head>
<body>

    <div class="split-card">
        <!-- Left Branding Panel -->
        <div class="auth-left">
            <div class="brand">
                <div class="brand-mark">F</div>
                <span>Focus<span class="brand-light">Flow</span></span>
            </div>
            <div class="left-content">
                <div class="eyebrow">Welcome Back</div>
                <h1>Master your time, <span>shape your focus.</span></h1>
                <p>Log in to access your workspace, track habits, and achieve your goals.</p>
            </div>
            <div class="left-footer">One focus. One flow. Infinite productivity.</div>
        </div>

        <!-- Right Form Panel -->
        <div class="auth-right">
            <div class="auth-header">
                <h2>Sign In</h2>
                <p>Enter your credentials to continue.</p>
            </div>

            <?php echo $alert; ?>

            <form method="POST" action="">
                <label>
                    Email Address
                    <input type="email" name="email" placeholder="you@example.com" required>
                </label>
                
                <label>
                    Password
                    <input type="password" name="password" placeholder="••••••••" required>
                </label>
                
                <button type="submit" class="primary-button">Sign In →</button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="register.php">Sign up</a>
            </div>
        </div>
    </div>

</body>
</html>