<?php
// ==========================================================================
// search_bar.php
// Backend for ABR search functionality (alternative endpoint).
// Called via script.js's fetch() for attribute-based retrieval.
// ==========================================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php'; // adjust path if your folder depth differs
require_once __DIR__ . '/../config/db_queries.php';

// Use the appropriate connection
global $conn;

try {
    // ✅ FIXED: Check if connection exists
    if ($conn === null) {
        throw new Exception("Database connection is not available.");
    }

    // Capture and validate all inputs
    $fileType    = $_POST['fileType'] ?? 'All';
    $maxSize     = isset($_POST['maxSize']) ? (float) $_POST['maxSize'] : 500;   // MB, from slider
    $owner       = trim($_POST['owner'] ?? '');
    $resolution  = $_POST['resolution'] ?? 'All';
    $maxDuration = isset($_POST['maxDuration']) ? (int) $_POST['maxDuration'] : 3600; // seconds

    $allowedTypes = ['All', 'image', 'audio', 'video', 'document'];
    if (!in_array($fileType, $allowedTypes, true)) {
        throw new Exception("Invalid file type filter supplied.");
    }
    if ($maxSize <= 0)     { $maxSize = 500; }
    if ($maxDuration <= 0) { $maxDuration = 3600; }

    // Check if database is available
    $db_available = isDbConnected();
    if (!$db_available) {
        throw new Exception("Database connection is not available.");
    }

    // Execute search bar filter using centralized function
    $rows = searchBarFilter($conn, $fileType, $maxSize, $owner, $resolution, $maxDuration);

    echo json_encode(['success' => true, 'data' => $rows]);

} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
