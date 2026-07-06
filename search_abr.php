<?php
// search_bar.php

header('Content-Type: application/json');

require_once __DIR__ . '/../db_connect.php'; // adjust path if your folder depth differs

try {
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

    // Base query: join video_metadata for resolution/duration, student_users for owner search
    // Note: schema uses file_size_kb (not file_size) and duration_seconds (not duration)
    $sql = "SELECT a.*, su.full_name AS owner_name, v.resolution, v.duration_seconds
            FROM multimedia_asset a
            LEFT JOIN video_metadata v ON a.asset_id = v.asset_id
            LEFT JOIN student_users su ON a.matric_number = su.matric_number
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
        $sql .= " AND (su.full_name LIKE ? OR a.matric_number LIKE ?)";
        $types .= "ss";
        $likeOwner = '%' . $owner . '%';
        $params[] = $likeOwner;
        $params[] = $likeOwner;
    }

    if ($resolution !== 'All') {
        $sql .= " AND v.resolution = ?";
        $types .= "s";
        $params[] = $resolution;
    }

    // Only enforce duration cap on rows that actually have a duration (i.e. video/audio)
    $sql .= " AND (v.duration_seconds IS NULL OR v.duration_seconds <= ?)";
    $types .= "i";
    $params[] = $maxDuration;

    $stmt = $conn->prepare($sql);
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