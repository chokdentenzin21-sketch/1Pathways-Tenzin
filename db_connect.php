<?php
// db_connect.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);        // Default MySQL port
define('DB_USER', 'root');      // Update with your MySQL username
define('DB_PASS', '');          // Update with your MySQL password
define('DB_NAME', 'student_programs');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}
?>