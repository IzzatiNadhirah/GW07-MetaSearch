<?php
// ==========================================================================
// db_connect.php
// Single Database Connection for MetaSearch Project
// ==========================================================================
// 
// DATABASE CONFIGURATION:
// ┌─────────────────────────────────────────────────────────────────────────┐
// │ Database: gw07 (Remote Server - bitp3353.utem.edu.my)                 │
// │   - Host: localhost (or bitp3353.utem.edu.my for production)          │
// │   - Username: gw07                                                    │
// │   - Password: password                                                │
// │   - Database: gw07                                                    │
// │   - Used for: All project data (multimedia_asset, metadata tables,    │
// │                student data via vstu)                                 │
// └─────────────────────────────────────────────────────────────────────────┘
// ==========================================================================

// 0. Make mysqli throw exceptions on error instead of returning false silently
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ==========================================================================
// DATABASE CONFIGURATION
// ==========================================================================

// ──────────────────────────────────────────────────────────────────────────
// Database: gw07 (Primary Database)
// ──────────────────────────────────────────────────────────────────────────
define('DB_SERVER', 'localhost'); // bitp3353.utem.edu.my (Server Madam)
define('DB_USERNAME', 'GW07');
define('DB_PASSWORD', 'password');
define('DB_NAME', 'gw07');

// ==========================================================================
// ESTABLISH CONNECTION
// ==========================================================================

// ──────────────────────────────────────────────────────────────────────────
// Connection: gw07 Database (REQUIRED - must work)
// ──────────────────────────────────────────────────────────────────────────
try {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Database Connection Engine Failure (gw07): " . $e->getMessage());
}

// ==========================================================================
// MAKE $conn WIDELY AVAILABLE ACROSS ALL FILES
// ==========================================================================

// 1. Register in $GLOBALS array for universal access
$GLOBALS['conn'] = $conn;

// 2. Create aliases for backward compatibility with existing code
$GLOBALS['conn_gw07'] = $conn;  // For code expecting $conn_gw07
$GLOBALS['conn_mmdb'] = null;   // For code expecting $conn_mmdb (not used)

// 3. Set as local variables in global scope
$conn_gw07 = $conn;
$conn_mmdb = null;

// 4. Status flags for backward compatibility
$GLOBALS['mmdb_connected'] = true;
$GLOBALS['mmdb_error'] = null;
$mmdb_connected = true;
$mmdb_error = null;

// ==========================================================================
// HELPER FUNCTIONS FOR DATABASE ACCESS
// ==========================================================================

/**
 * Check if database is connected
 * 
 * @return bool True if database is connected
 */
function isDbConnected() {
    global $conn;
    return ($conn !== null && $conn->ping());
}

/**
 * Get the database connection
 * 
 * @return mysqli The database connection object
 */
function getDbConnection() {
    global $conn;
    return $conn;
}

/**
 * Execute a query on the database
 * 
 * @param string $sql SQL query string
 * @return mysqli_result|bool Query result
 */
function executeQuery($sql) {
    global $conn;
    return $conn->query($sql);
}

/**
 * Prepare a statement on the database
 * 
 * @param string $sql SQL query string
 * @return mysqli_stmt|false Prepared statement
 */
function prepareStatement($sql) {
    global $conn;
    return $conn->prepare($sql);
}

/**
 * Get the last inserted ID
 * 
 * @return int|string The last inserted ID
 */
function getLastInsertId() {
    global $conn;
    return $conn->insert_id;
}

/**
 * Escape a string for safe SQL usage
 * 
 * @param string $string The string to escape
 * @return string The escaped string
 */
function escapeString($string) {
    global $conn;
    return $conn->real_escape_string($string);
}

// ==========================================================================
// BACKWARD COMPATIBILITY FUNCTIONS (for code expecting remote DB)
// ==========================================================================

/**
 * Check if remote database is connected (kept for backward compatibility)
 * 
 * @return bool True if database is connected
 */
function isRemoteDbConnected() {
    return true; // Always true since we're using single DB
}

/**
 * Get remote database error message (kept for backward compatibility)
 * 
 * @return string|null Error message or null if no error
 */
function getRemoteDbError() {
    return null; // No error since we're using single DB
}

// ==========================================================================
// OLD LOCALHOST CONFIGURATION (Commented out for reference)
// ==========================================================================
/*
// DATABASE: gw07 (Localhost - Old Configuration)
define('DB1_SERVER', 'localhost');
define('DB1_USERNAME', 'root');
define('DB1_PASSWORD', '');
define('DB1_NAME', 'gw07');

// Connection 1: Local gw07 Database (REQUIRED - must work)
try {
    $conn_gw07 = new mysqli(DB1_SERVER, DB1_USERNAME, DB1_PASSWORD, DB1_NAME);
    $conn_gw07->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Database Connection Engine Failure (gw07): " . $e->getMessage());
}

// Connection 2: Remote mmdb2026 Database (OPTIONAL - can fail gracefully)
$conn_mmdb = null;
$mmdb_connected = false;
$mmdb_error = null;

try {
    $conn_mmdb = new mysqli(DB2_SERVER, DB2_USERNAME, DB2_PASSWORD, DB2_NAME);
    $conn_mmdb->set_charset("utf8mb4");
    $mmdb_connected = true;
} catch (mysqli_sql_exception $e) {
    $mmdb_error = $e->getMessage();
    $mmdb_connected = false;
    error_log("mmdb2026 connection failed: " . $mmdb_error);
}
*/
?>
