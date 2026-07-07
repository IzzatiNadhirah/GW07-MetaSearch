<?php
// ==========================================================================
// search_bar.php
// Backend for ABR search functionality (alternative endpoint).
// Called via script.js's fetch() for attribute-based retrieval.
// ==========================================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php'; // adjust path if your folder depth differs

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

    // Base query: join video_metadata for resolution/duration
    // Query: SELECT a.*, v.*, vm.resolution, vm.duration_seconds
    //        FROM multimedia_asset a
    //        LEFT JOIN video_metadata vm ON a.asset_id = vm.asset_id
    //        LEFT JOIN mmdb2026.vstu v ON a.matric_number = v.matric_no
    //        WHERE a.file_size_kb <= ?
    // Fetches all vstu columns: id, matric_no, full_name, phone_no, group_no,
    // life_motto, password, photoStu, photoStu_date, docStu, docStu_date,
    // audioStu, audioStu_date, videoStu, videoStu_date
    $sql = "SELECT a.*, v.*, vm.resolution, vm.duration_seconds
            FROM multimedia_asset a
            LEFT JOIN video_metadata vm ON a.asset_id = vm.asset_id
            LEFT JOIN mmdb2026.vstu v ON a.matric_number = v.matric_no
            WHERE a.file_size_kb <= ?";

    $types  = "d";
    $params = [$maxSize * 1024]; // convert MB (slider) -> KB (schema unit)

    if ($fileType !== 'All') {
        $sql .= " AND a.file_type = ?";
        $types .= "s";
        $params[] = $fileType;
    }

    if ($owner !== '') {
        // "Owner" isn't a column on multimedia_asset — search matched student's name or matric number instead
        $sql .= " AND (v.full_name LIKE ? OR a.matric_number LIKE ?)";
        $types .= "ss";
        $likeOwner = '%' . $owner . '%';
        $params[] = $likeOwner;
        $params[] = $likeOwner;
    }

    if ($resolution !== 'All') {
        $sql .= " AND vm.resolution = ?";
        $types .= "s";
        $params[] = $resolution;
    }

    // Only enforce duration cap on rows that actually have a duration (i.e. video/audio)
    $sql .= " AND (vm.duration_seconds IS NULL OR vm.duration_seconds <= ?)";
    $types .= "i";
    $params[] = $maxDuration;

    // ✅ FIXED: Check if prepare succeeded before using bind_param
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Failed to prepare query: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'data' => $rows]);

} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
