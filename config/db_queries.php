<?php
// ==========================================================================
// db_queries.php - Centralized Database Queries for MetaSearch
// All database queries are consolidated here for easy maintenance.
// ==========================================================================

// ==========================================================================
// QUERY 1: Get All Groups
// ==========================================================================
function getGroups($conn) {
    $groups = [];
    try {
        $sql = "SELECT DISTINCT group_no FROM mmdb2026.vstu WHERE group_no IS NOT NULL AND group_no != '' ORDER BY group_no ASC";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $groups[] = $row['group_no'];
            }
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Failed to get groups: " . $e->getMessage());
    }
    return $groups;
}

// ==========================================================================
// QUERY 2: Get Group Members
// ==========================================================================
function getGroupMembers($conn, $group) {
    $members = [];
    try {
        $sql = "SELECT * FROM mmdb2026.vstu WHERE group_no = ? ORDER BY full_name ASC";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $group);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $members[] = $row;
            }
            $stmt->close();
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Failed to get group members: " . $e->getMessage());
    }
    return $members;
}

// ==========================================================================
// QUERY 3: Get Asset Statistics
// ==========================================================================
function getAssetStatistics($conn) {
    $counts = ['image' => 0, 'video' => 0, 'audio' => 0, 'document' => 0, 'total' => 0];
    try {
        $sql = "SELECT file_type, COUNT(*) as total FROM multimedia_asset GROUP BY file_type";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $counts[$row['file_type']] = $row['total'];
                $counts['total'] += $row['total'];
            }
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Failed to get asset statistics: " . $e->getMessage());
    }
    return $counts;
}

// ==========================================================================
// QUERY 4: Get Total Student Count
// ==========================================================================
function getStudentCount($conn) {
    $count = 0;
    try {
        $sql = "SELECT COUNT(*) as total FROM mmdb2026.vstu";
        $result = $conn->query($sql);
        if ($result) {
            $count = $result->fetch_assoc()['total'];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Failed to get student count: " . $e->getMessage());
    }
    return $count;
}

// ==========================================================================
// QUERY 5: Get Recent Uploads
// ==========================================================================
function getRecentUploads($conn) {
    $assets = [];
    try {
        $sql = "SELECT ma.*, v.full_name AS owner_name 
                FROM multimedia_asset ma
                LEFT JOIN mmdb2026.vstu v ON ma.matric_number = v.matric_no
                ORDER BY ma.last_modified DESC 
                LIMIT 10";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $assets[] = $row;
            }
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Failed to get recent uploads: " . $e->getMessage());
    }
    return $assets;
}

// ==========================================================================
// QUERY 6: Search Assets (ABR + TBR Combined)
// ==========================================================================
function searchAssets($conn, $fileType, $query, $db_available) {
    $results = [];
    $whereClauses = [];
    $params = [];
    $types = "";
    $searchPerformed = false;

    if (!empty($fileType)) {
        $whereClauses[] = "ma.file_type = ?";
        $params[] = $fileType;
        $types .= "s";
        $searchPerformed = true;
    }

    if (!empty($query)) {
        $searchTerm = '%' . $query . '%';
        $whereClauses[] = "(ma.file_name LIKE ? OR ma.title LIKE ? OR ma.matric_number LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
        $searchPerformed = true;
    }

    if ($searchPerformed && $db_available) {
        try {
            $sql = "SELECT ma.*, v.full_name AS owner_name 
                    FROM multimedia_asset ma
                    LEFT JOIN mmdb2026.vstu v ON ma.matric_number = v.matric_no";
            
            if (count($whereClauses) > 0) {
                $sql .= " WHERE " . implode(" AND ", $whereClauses);
            }
            
            $sql .= " ORDER BY ma.last_modified DESC LIMIT 15";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $results[] = $row;
                }
                $stmt->close();
            }
        } catch (mysqli_sql_exception $e) {
            error_log("Search error: " . $e->getMessage());
        }
    }
    
    return ['results' => $results, 'performed' => $searchPerformed];
}

// ==========================================================================
// QUERY 7: Get Full vstu Data by Matric Number
// ==========================================================================
function getStudentByMatric($conn, $matric) {
    $student = null;
    try {
        $sql = "SELECT * FROM mmdb2026.vstu WHERE matric_no = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $matric);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $student = $row;
            }
            $stmt->close();
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Failed to get student by matric: " . $e->getMessage());
    }
    return $student;
}

// ==========================================================================
// QUERY 8: Get All Students (for dropdown or display)
// ==========================================================================
function getAllStudents($conn) {
    $students = [];
    try {
        $sql = "SELECT * FROM mmdb2026.vstu ORDER BY full_name ASC";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Failed to get all students: " . $e->getMessage());
    }
    return $students;
}

// ==========================================================================
// QUERY 9: ABR Filter Search
// ==========================================================================
function abrFilterSearch($conn, $fileType, $maxSize, $owner, $resolution, $maxDuration) {
    $rows = [];
    try {
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

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Failed to prepare query: " . $conn->error);
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log("ABR filter error: " . $e->getMessage());
        throw $e;
    }
    return $rows;
}

// ==========================================================================
// QUERY 10: Search Bar Filter (Alternative ABR Endpoint)
// ==========================================================================
function searchBarFilter($conn, $fileType, $maxSize, $owner, $resolution, $maxDuration) {
    $rows = [];
    try {
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

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Failed to prepare query: " . $conn->error);
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log("Search bar filter error: " . $e->getMessage());
        throw $e;
    }
    return $rows;
}

// ==========================================================================
// QUERY 11: CBR Image Search by Dominant Color
// ==========================================================================
function cbrImageSearch($conn, $value) {
    $results = [];
    try {
        $sql = "
        SELECT
            ma.title,
            ma.file_name,
            ma.file_path,
            im.width,
            im.height,
            im.resolution,
            im.dominant_color
        FROM multimedia_asset ma
        JOIN image_metadata im ON ma.asset_id = im.asset_id
        WHERE im.dominant_color LIKE ?
        ";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            $likeValue = '%' . $value . '%';
            mysqli_stmt_bind_param($stmt, "s", $likeValue);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    } catch (mysqli_sql_exception $e) {
        error_log("CBR image search error: " . $e->getMessage());
        throw $e;
    }
    return $results;
}

// ==========================================================================
// QUERY 12: CBR Video Search by Duration
// ==========================================================================
function cbrVideoSearch($conn, $seconds) {
    $results = [];
    try {
        $sql = "
        SELECT
            ma.title,
            ma.file_name,
            ma.file_path,
            vm.resolution,
            vm.duration_seconds,
            CONCAT(FLOOR(vm.duration_seconds / 60), 'm ', vm.duration_seconds % 60, 's') as duration_formatted
        FROM multimedia_asset ma
        JOIN video_metadata vm ON ma.asset_id = vm.asset_id
        WHERE vm.duration_seconds >= ?
        ORDER BY vm.duration_seconds DESC
        ";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $seconds);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    } catch (mysqli_sql_exception $e) {
        error_log("CBR video search error: " . $e->getMessage());
        throw $e;
    }
    return $results;
}

// ==========================================================================
// QUERY 13: CBR Audio Search by Duration
// ==========================================================================
function cbrAudioSearch($conn, $seconds) {
    $results = [];
    try {
        $sql = "
        SELECT
            ma.title,
            ma.file_name,
            ma.file_path,
            am.duration_seconds,
            CONCAT(FLOOR(am.duration_seconds / 60), 'm ', am.duration_seconds % 60, 's') as duration_formatted,
            am.bitrate_kbps,
            am.audio_format
        FROM multimedia_asset ma
        JOIN audio_metadata am ON ma.asset_id = am.asset_id
        WHERE am.duration_seconds >= ?
        ORDER BY am.duration_seconds DESC
        ";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $seconds);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    } catch (mysqli_sql_exception $e) {
        error_log("CBR audio search error: " . $e->getMessage());
        throw $e;
    }
    return $results;
}

// ==========================================================================
// QUERY 14: CBR Document Search by Page Count
// ==========================================================================
function cbrDocumentSearch($conn, $pageCount) {
    $results = [];
    try {
        $sql = "
        SELECT
            ma.title,
            ma.file_name,
            ma.file_path,
            dm.page_count,
            dm.is_searchable
        FROM multimedia_asset ma
        JOIN document_metadata dm ON ma.asset_id = dm.asset_id
        WHERE dm.page_count >= ?
        ORDER BY dm.page_count DESC
        ";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $pageCount);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    } catch (mysqli_sql_exception $e) {
        error_log("CBR document search error: " . $e->getMessage());
        throw $e;
    }
    return $results;
}
?>
