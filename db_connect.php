<?php
// db_connect.php
// Single shared database connection file for the whole project.
// All modules (ABR, CBR, TBR, dashboard) should include this file only —
// do not create parallel connection files (e.g. db.php) to avoid drift.

// 0. Make mysqli throw exceptions on error instead of returning false silently
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 1. Define Database Configuration Constants
//define('DB_SERVER', '10.147.17.3');  // ZeroTier
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');       // username = gw07
define('DB_PASSWORD', '');   // password = password
define('DB_NAME', 'gw07');           // database name (Username: gw07; Password: password)

// 2. Initiate Database Connection Gateway using MySQLi Object-Oriented Engine
try {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // 3. Set Global Character Encoding standard to UTF-8 to ensure multimedia text symbols parse correctly
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // 4. Intercept Connection Anomalies and Prevent Script Execution on Failure
    die("Database Connection Engine Failure: " . $e->getMessage());
}

// Connection is active and ready to process query strings safely.
?>
