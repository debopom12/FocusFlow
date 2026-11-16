<?php
session_start();
include 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$alert = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $alert = "<div class='alert-error'>Passwords do not match.</div>";
    } else {
        $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $check_email->store_result();

        if ($check_email->num_rows > 0) {
            $alert = "<div class='alert-error'>Email is already registered.</div>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($insert_stmt->execute()) {
                header("Location: login.php");
                exit();
            } else {
                $alert = "<div class='alert-error'>Registration failed. Please try again.</div>";
            }
            $insert_stmt->close();
        }
        $check_email->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FocusFlow | Sign Up</title>
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
            color: #10b981;
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
            padding: 40px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-header {
            margin-bottom: 20px;
        }
        .auth-header h2 {
            margin: 0 0 4px 0;
            font-size: 22px;
            font-weight: 700;
            color: var(--text-main);
        }
        .auth-header p {
            margin: 0;
            color: var(--text-muted);
            font-size: 13px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        label {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-main);
        }

        input[type="text"], input[type="email"], input[type="password"] {
            font-family: 'Inter', sans-serif;
            background: var(--input-bg);
            color: var(--text-main);
            border: 1px solid var(--input-border);
            border-radius: 8px;
            padding: 10px 14px;
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
            margin-top: 20px;
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
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
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
                <div class="eyebrow">Get Started</div>
                <h1>Your Focus. <span>Your Workspace.</span></h1>
                <p>Create an account to manage tasks, track habits, and elevate your daily productivity.</p>
            </div>
            <div class="left-footer">One focus. One flow. Infinite productivity.</div>
        </div>

        <!-- Right Form Panel -->
        <div class="auth-right">
            <div class="auth-header">
                <h2>Create an account</h2>
                <p>Start managing your focus today.</p>
            </div>

            <?php echo $alert; ?>

            <form method="POST" action="">
                <label>
                    Username
                    <input type="text" name="username" placeholder="e.g. johndoe" required>
                </label>

                <label>
                    Email address
                    <input type="email" name="email" placeholder="you@example.com" required>
                </label>
                
                <label>
                    Password
                    <input type="password" name="password" placeholder="••••••••" required>
                </label>

                <label>
                    Confirm Password
                    <input type="password" name="confirm_password" placeholder="••••••••" required>
                </label>
                
                <button type="submit" class="primary-button">Sign Up →</button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Log in</a>
            </div>
        </div>
    </div>

</body>
</html>