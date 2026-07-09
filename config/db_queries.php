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
// QUERY 3: Get Dashboard Statistics (from vstu only)
// ==========================================================================
function getDashboardStats($conn) {
    $stats = [
        'total_students' => 0,
        'groups' => 0,
        'with_photo' => 0,
        'with_doc' => 0,
        'with_audio' => 0,
        'with_video' => 0
    ];
    try {
        // Total students
        $result = $conn->query("SELECT COUNT(*) as total FROM mmdb2026.vstu");
        if ($result) {
            $stats['total_students'] = $result->fetch_assoc()['total'];
        }
        
        // Total groups
        $result = $conn->query("SELECT COUNT(DISTINCT group_no) as total FROM mmdb2026.vstu WHERE group_no IS NOT NULL AND group_no != ''");
        if ($result) {
            $stats['groups'] = $result->fetch_assoc()['total'];
        }
        
        // Students with photo
        $result = $conn->query("SELECT COUNT(*) as total FROM mmdb2026.vstu WHERE photoStu IS NOT NULL AND photoStu != ''");
        if ($result) {
            $stats['with_photo'] = $result->fetch_assoc()['total'];
        }
        
        // Students with document
        $result = $conn->query("SELECT COUNT(*) as total FROM mmdb2026.vstu WHERE docStu IS NOT NULL AND docStu != ''");
        if ($result) {
            $stats['with_doc'] = $result->fetch_assoc()['total'];
        }
        
        // Students with audio
        $result = $conn->query("SELECT COUNT(*) as total FROM mmdb2026.vstu WHERE audioStu IS NOT NULL AND audioStu != ''");
        if ($result) {
            $stats['with_audio'] = $result->fetch_assoc()['total'];
        }
        
        // Students with video
        $result = $conn->query("SELECT COUNT(*) as total FROM mmdb2026.vstu WHERE videoStu IS NOT NULL AND videoStu != ''");
        if ($result) {
            $stats['with_video'] = $result->fetch_assoc()['total'];
        }
        
    } catch (mysqli_sql_exception $e) {
        error_log("Failed to get dashboard stats: " . $e->getMessage());
    }
    return $stats;
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
// QUERY 5: Get All Students (Recent/All)
// ==========================================================================
function getAllStudents($conn, $limit = 20) {
    $students = [];
    try {
        $sql = "SELECT * FROM mmdb2026.vstu ORDER BY full_name ASC LIMIT ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
            $stmt->close();
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Failed to get all students: " . $e->getMessage());
    }
    return $students;
}

// ==========================================================================
// QUERY 6: Search Students (ABR - Attribute-Based Retrieval)
// ==========================================================================
function abrFilterSearch($conn, $group, $searchTerm, $hasPhoto, $hasDoc, $hasAudio, $hasVideo) {
    $rows = [];
    try {
        $sql = "SELECT * FROM mmdb2026.vstu WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($group) && $group !== 'All') {
            $sql .= " AND group_no = ?";
            $params[] = $group;
            $types .= "s";
        }

        if (!empty($searchTerm)) {
            $sql .= " AND (full_name LIKE ? OR matric_no LIKE ?)";
            $searchLike = '%' . $searchTerm . '%';
            $params[] = $searchLike;
            $params[] = $searchLike;
            $types .= "ss";
        }

        if ($hasPhoto) {
            $sql .= " AND photoStu IS NOT NULL AND photoStu != ''";
        }

        if ($hasDoc) {
            $sql .= " AND docStu IS NOT NULL AND docStu != ''";
        }

        if ($hasAudio) {
            $sql .= " AND audioStu IS NOT NULL AND audioStu != ''";
        }

        if ($hasVideo) {
            $sql .= " AND videoStu IS NOT NULL AND videoStu != ''";
        }

        $sql .= " ORDER BY full_name ASC";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Failed to prepare query: " . $conn->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();

    } catch (mysqli_sql_exception $e) {
        error_log("ABR filter error: " . $e->getMessage());
        throw $e;
    } catch (Exception $e) {
        error_log("ABR filter general error: " . $e->getMessage());
        throw $e;
    }
    return $rows;
}

// ==========================================================================
// QUERY 7: Search Bar Filter (Alternative ABR Endpoint)
// ==========================================================================
function searchBarFilter($conn, $group, $searchTerm, $hasPhoto, $hasDoc, $hasAudio, $hasVideo) {
    return abrFilterSearch($conn, $group, $searchTerm, $hasPhoto, $hasDoc, $hasAudio, $hasVideo);
}

// ==========================================================================
// QUERY 8: TBR - Text-Based Retrieval Search
// ==========================================================================
function tbrSearch($conn, $query_term) {
    $results = [];
    try {
        $sql = "SELECT * FROM mmdb2026.vstu 
                WHERE full_name LIKE ? 
                OR matric_no LIKE ? 
                OR group_no LIKE ? 
                OR life_motto LIKE ?
                ORDER BY full_name ASC";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            $likeTerm = '%' . $query_term . '%';
            mysqli_stmt_bind_param($stmt, "ssss", $likeTerm, $likeTerm, $likeTerm, $likeTerm);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    } catch (mysqli_sql_exception $e) {
        error_log("TBR search error: " . $e->getMessage());
        throw $e;
    }
    return $results;
}

// ==========================================================================
// QUERY 9: CBR - Content-Based Retrieval Search
// ==========================================================================
function cbrSearch($conn, $type, $value) {
    $results = [];
    try {
        $sql = "";
        $stmt = null;

        switch ($type) {
            case 'photo':
                $sql = "SELECT * FROM mmdb2026.vstu WHERE photoStu IS NOT NULL AND photoStu != ''";
                $stmt = mysqli_prepare($conn, $sql);
                break;
                
            case 'doc':
                $sql = "SELECT * FROM mmdb2026.vstu WHERE docStu IS NOT NULL AND docStu != ''";
                $stmt = mysqli_prepare($conn, $sql);
                break;
                
            case 'audio':
                $sql = "SELECT * FROM mmdb2026.vstu WHERE audioStu IS NOT NULL AND audioStu != ''";
                $stmt = mysqli_prepare($conn, $sql);
                break;
                
            case 'video':
                $sql = "SELECT * FROM mmdb2026.vstu WHERE videoStu IS NOT NULL AND videoStu != ''";
                $stmt = mysqli_prepare($conn, $sql);
                break;
                
            case 'motto':
                $sql = "SELECT * FROM mmdb2026.vstu WHERE life_motto LIKE ?";
                $stmt = mysqli_prepare($conn, $sql);
                if ($stmt) {
                    $likeValue = '%' . $value . '%';
                    mysqli_stmt_bind_param($stmt, "s", $likeValue);
                }
                break;
                
            default:
                throw new Exception("Unsupported CBR type: " . $type);
        }

        if ($stmt) {
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    } catch (mysqli_sql_exception $e) {
        error_log("CBR search error: " . $e->getMessage());
        throw $e;
    }
    return $results;
}

// ==========================================================================
// QUERY 10: Get Student by Matric Number
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
// QUERY 11: Get Suggested Tags (from vstu data)
// ==========================================================================
function getSuggestedTags($conn) {
    $suggestedTags = [];
    try {
        if ($conn !== null) {
            // Get distinct groups as tags
            $result = $conn->query("SELECT DISTINCT group_no FROM mmdb2026.vstu WHERE group_no IS NOT NULL AND group_no != '' LIMIT 10");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $suggestedTags[] = $row['group_no'];
                }
            }
        }
    } catch (mysqli_sql_exception $e) {
        $suggestedTags = [];
    }
    return $suggestedTags;
}
?>
