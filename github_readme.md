# FocusFlow 🚀
> A modern, distraction-free time-management and productivity web application built with PHP, MySQL, HTML, CSS, and JavaScript.

FocusFlow is designed with a sleek SaaS aesthetic inspired by tools like Linear and Notion. It features full user authentication, task management, dynamic calendar scheduling, focus timers, habit tracking, and seamless light/dark mode persistence.

---

## ✨ Features
- **User Authentication:** Secure registration, login, and password hashing with PHP sessions.
- **Interactive Dashboard:** Real-time task completion tracking, productivity scoring, and a built-in customizable Focus Timer.
- **Calendar & Scheduling:** Full month navigation with interactive event adding and category organization.
- **Goal & Habit Trackers:** Long-term goal milestone tracking and daily habit streak monitoring.
- **Account Settings:** Profile photo upload/removal, password updates, and secure account deletion.
- **Theme Persistence:** Instant switching between light and dark modes with state saved in `localStorage`.

---

## 🛠️ Technology Stack
- **Frontend:** HTML5, CSS3 (CSS Variables, Flexbox/Grid), JavaScript (ES6)
- **Backend:** PHP (>= 7.4)
- **Database:** MySQL / MariaDB (via XAMPP)
- **Typography:** Inter (Google Fonts)

---

## ⚙️ Local Installation & Setup (XAMPP Environment)

1. **Clone or Download the Repository:**
   Place your project folder inside your local web server root directory (e.g., `htdocs` in XAMPP):
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/your-username/focusflow.git
   ```

2. **Start Apache and MySQL:**
   Open your XAMPP Control Panel and start both **Apache** and **MySQL**.

3. **Create the Database:**
   - Open phpMyAdmin (`http://localhost/phpmyadmin/`).
   - Create a new database named **`time_manager`** (or match your `db_connect.php` configuration).

4. **Database Configuration:**
   Ensure your `db_connect.php` file correctly connects to your local database:
   ```php
   <?php
   $host = "localhost";
   $user = "root";
   $password = "";
   $dbname = "time_manager";

   $conn = new mysqli($host, $user, $password, $dbname);
   if ($conn->connect_error) {
       die("Connection failed: " . $conn->connect_error);
   }
   ?>
   ```
   *(Note: FocusFlow features automatic table creation and column safeguards, so tables for `users`, `tasks`, `goals`, `habits`, and `calendar_events` will initialize on first load).*

5. **Run the Application:**
   Open your browser and navigate to:
   ```text
   http://localhost/time_manager/login.php
   ```

---

## 📂 Project Structure
```text
focusflow/
│
├── db_connect.php         # Database connection script
├── login.php              # User login page
├── register.php           # User registration page
├── dashboard.php          # Main workspace & task hub
├── calendar.php           # Event scheduler & calendar view
├── timer.php              # Standalone focus timer
├── reports.php            # Analytics & productivity reporting
├── goals.php              # Long-term goal tracker
├── habits.php             # Daily habit streak tracker
├── profile.php            # Account settings & security
├── sidebar.php            # Shared responsive navigation component
├── logout.php             # Session destruction handler
└── uploads/               # User profile picture storage directory
```

---

## 🛡️ License
This project is open-source and available under the [MIT License](LICENSE).