<?php
// ==========================================================================
// abr_filter.php
// Backend for abr_search.html's filter form (called via script.js's fetch()).
// Now searches gw07.vstu table only (consistent with database schema).
// ==========================================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../config/db_queries.php';

global $conn;

try {
    // Check if database connection exists
    if ($conn === null) {
        throw new Exception("Database connection is not available.");
    }

    // Check if database is connected and responsive
    $db_available = isDbConnected();
    if (!$db_available) {
        throw new Exception("Database connection is not available.");
    }

    // Capture and validate all inputs
    $group       = $_POST['group'] ?? '';
    $searchTerm  = $_POST['searchTerm'] ?? '';
    $hasPhoto    = isset($_POST['hasPhoto']) ? true : false;
    $hasDoc      = isset($_POST['hasDoc']) ? true : false;
    $hasAudio    = isset($_POST['hasAudio']) ? true : false;
    $hasVideo    = isset($_POST['hasVideo']) ? true : false;

    // Execute ABR filter search using centralized function
    // Query searches gw07.vstu table for student data
    $rows = abrFilterSearch($conn, $group, $searchTerm, $hasPhoto, $hasDoc, $hasAudio, $hasVideo);

    // Return results as JSON
    if (empty($rows)) {
        echo json_encode([
            'success' => true, 
            'data' => [], 
            'message' => 'No results found matching your criteria.',
            'count' => 0
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'data' => $rows, 
            'count' => count($rows)
        ]);
    }

} catch (mysqli_sql_exception $e) {
    // Database-specific error handling
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // General error handling
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>
