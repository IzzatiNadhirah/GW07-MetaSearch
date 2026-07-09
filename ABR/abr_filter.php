<?php
// ==========================================================================
// abr_filter.php
// Backend for abr_search.html's filter form (called via script.js's fetch()).
// Now searches mmdb2026.vstu table only.
// ==========================================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../config/db_queries.php';

global $conn;

try {
    if ($conn === null) {
        throw new Exception("Database connection is not available.");
    }

    // Capture and validate all inputs
    $group       = $_POST['group'] ?? '';
    $searchTerm  = $_POST['searchTerm'] ?? '';
    $hasPhoto    = isset($_POST['hasPhoto']) ? true : false;
    $hasDoc      = isset($_POST['hasDoc']) ? true : false;
    $hasAudio    = isset($_POST['hasAudio']) ? true : false;
    $hasVideo    = isset($_POST['hasVideo']) ? true : false;

    $db_available = isDbConnected();
    if (!$db_available) {
        throw new Exception("Database connection is not available.");
    }

    // Execute ABR filter search using centralized function
    $rows = abrFilterSearch($conn, $group, $searchTerm, $hasPhoto, $hasDoc, $hasAudio, $hasVideo);

    if (empty($rows)) {
        echo json_encode(['success' => true, 'data' => [], 'message' => 'No results found matching your criteria.']);
    } else {
        echo json_encode(['success' => true, 'data' => $rows, 'count' => count($rows)]);
    }

} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
