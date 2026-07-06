<?php
// ==========================================================================
// db_connect.php
// Dual Database Connection for MetaSearch Project
// ==========================================================================
// 
// DATABASE CONFIGURATION:
// ┌─────────────────────────────────────────────────────────────────────────┐
// │ Database 1: gw07 (Localhost)                                          │
// │   - Host: localhost                                                   │
// │   - Username: root                                                    │
// │   - Password: (empty)                                                 │
// │   - Database: gw07                                                    │
// │   - Used for: Main project data (multimedia_asset, metadata tables)   │
// ├─────────────────────────────────────────────────────────────────────────┤
// │ Database 2: mmdb2026 (Remote - bitp3353.utem.edu.my)                  │
// │   - Host: bitp3353.utem.edu.my                                        │
// │   - Username: gw07                                                    │
// │   - Password: password                                                │
// │   - Database: mmdb2026                                                │
// │   - Used for: Student data (vstu table)                               │
// └─────────────────────────────────────────────────────────────────────────┘
// ==========================================================================

// 0. Make mysqli throw exceptions on error instead of returning false silently
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ==========================================================================
// DATABASE 1: gw07 (Localhost)
// ==========================================================================
define('DB1_SERVER', 'localhost');
define('DB1_USERNAME', 'root');
define('DB1_PASSWORD', '');
define('DB1_NAME', 'gw07');

// ==========================================================================
// DATABASE 2: mmdb2026 (Remote Server - bitp3353.utem.edu.my)
// ==========================================================================
define('DB2_SERVER', 'bitp3353.utem.edu.my');
define('DB2_USERNAME', 'gw07');
define('DB2_PASSWORD', 'password');
define('DB2_NAME', 'mmdb2026');

// ==========================================================================
// ESTABLISH CONNECTIONS
// ==========================================================================

// ──────────────────────────────────────────────────────────────────────────
// Connection 1: Local gw07 Database (REQUIRED - must work)
// ──────────────────────────────────────────────────────────────────────────
try {
    $conn_gw07 = new mysqli(DB1_SERVER, DB1_USERNAME, DB1_PASSWORD, DB1_NAME);
    $conn_gw07->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Database Connection Engine Failure (gw07): " . $e->getMessage());
}

// ──────────────────────────────────────────────────────────────────────────
// Connection 2: Remote mmdb2026 Database (OPTIONAL - can fail gracefully)
// ──────────────────────────────────────────────────────────────────────────
$conn_mmdb = null;
$mmdb_connected = false;
$mmdb_error = null;

try {
    $conn_mmdb = new mysqli(DB2_SERVER, DB2_USERNAME, DB2_PASSWORD, DB2_NAME);
    $conn_mmdb->set_charset("utf8mb4");
    $mmdb_connected = true;
} catch (mysqli_sql_exception $e) {
    // Remote DB connection failed - store error for logging but don't crash
    $mmdb_error = $e->getMessage();
    $mmdb_connected = false;
    error_log("mmdb2026 connection failed: " . $mmdb_error);
    // Connection remains null - will be handled gracefully
}

// ==========================================================================
// BACKWARD COMPATIBILITY: $conn points to gw07 (default)
// ==========================================================================
$conn = $conn_gw07;

// ==========================================================================
// HELPER FUNCTIONS FOR DUAL DATABASE ACCESS
// ==========================================================================

/**
 * Get the appropriate database connection based on table name
 * 
 * @param string $tableName The table name to check
 * @return mysqli|null The appropriate connection object or null if unavailable
 */
function getDbConnection($tableName) {
    global $conn_gw07, $conn_mmdb, $mmdb_connected;
    
    // Tables that belong to gw07 (local) - ALWAYS available
    $gw07Tables = [
        'multimedia_asset',
        'image_metadata',
        'audio_metadata',
        'video_metadata',
        'document_metadata',
        'text_metadata',
        'system_metadata_analytics'
    ];
    
    // Tables that belong to mmdb2026 (remote) - may NOT be available
    $mmdbTables = [
        'vstu',
        'student_users'
    ];
    
    if (in_array($tableName, $gw07Tables)) {
        return $conn_gw07;
    } elseif (in_array($tableName, $mmdbTables)) {
        // Only return mmdb connection if it's actually connected
        if ($mmdb_connected && $conn_mmdb !== null) {
            return $conn_mmdb;
        } else {
            return null; // Remote DB not available
        }
    } else {
        // Default to gw07 if unknown
        return $conn_gw07;
    }
}

/**
 * Check if remote database is connected
 * 
 * @return bool True if mmdb2026 is connected
 */
function isRemoteDbConnected() {
    global $mmdb_connected;
    return $mmdb_connected;
}

/**
 * Get remote database error message
 * 
 * @return string|null Error message or null if no error
 */
function getRemoteDbError() {
    global $mmdb_error;
    return $mmdb_error;
}

/**
 * Execute a query on the appropriate database based on table name
 * 
 * @param string $sql SQL query string
 * @param string $tableName The primary table name in the query
 * @return mysqli_result|bool|false Query result or false on failure
 */
function executeQuery($sql, $tableName) {
    $conn = getDbConnection($tableName);
    if ($conn === null) {
        return false;
    }
    return $conn->query($sql);
}

/**
 * Prepare a statement on the appropriate database based on table name
 * 
 * @param string $sql SQL query string
 * @param string $tableName The primary table name in the query
 * @return mysqli_stmt|false Prepared statement or false on failure
 */
function prepareStatement($sql, $tableName) {
    $conn = getDbConnection($tableName);
    if ($conn === null) {
        return false;
    }
    return $conn->prepare($sql);
}
?>
