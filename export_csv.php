<?php
session_start();
include 'db_connect.php';

// Ensure user is logged in before allowing download
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch all tasks for the logged-in user
$stmt = $conn->prepare("SELECT task_title, category, priority, due_date, status, time_logged FROM tasks WHERE user_id = ? ORDER BY due_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Format the filename (e.g., FocusFlow_Export_Debopom_2026-11-14.csv)
$date = date('Y-m-d');
$filename = "FocusFlow_Export_" . preg_replace('/[^A-Za-z0-9\-]/', '_', $username) . "_" . $date . ".csv";

// Force Browser to download the file as a CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Write the Column Headers to the CSV
fputcsv($output, array('Task Title', 'Category', 'Priority', 'Due Date', 'Status', 'Time Logged (Seconds)', 'Time Logged (Minutes)'));

// Loop through database results and write them to the CSV
while ($row = $result->fetch_assoc()) {
    
    // Calculate minutes for easier reading in Excel
    $minutes_logged = round($row['time_logged'] / 60, 2);
    
    // Format the data array for the CSV row
    $csv_row = array(
        $row['task_title'],
        $row['category'],
        ucfirst($row['priority']),
        $row['due_date'],
        ucfirst($row['status']),
        $row['time_logged'],
        $minutes_logged
    );
    
    // Write row to CSV
    fputcsv($output, $csv_row);
}

// Close connections
$stmt->close();
$conn->close();
fclose($output);
exit();
?>