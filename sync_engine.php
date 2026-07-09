<?php
// ==========================================================================
// sync_engine.php - Remote Data Synchronization Engine
// Now only syncs to mmdb2026.vstu table (no multimedia_asset)
// ==========================================================================

function syncPlatformData($targetUrl, $conn) {
    // 1. Fetch live page HTML source markup
    $html = @file_get_contents($targetUrl);
    if ($html === false) {
        throw new Exception("Unable to establish remote stream gateway to target server.");
    }

    // 2. Instantiate native DOM parsing engine
    libxml_use_internal_errors(true); // suppress warnings from non-strict HTML
    $dom = new DOMDocument();
    if (!$dom->loadHTML($html)) {
        libxml_clear_errors();
        throw new Exception("Failed to parse remote directory listing HTML.");
    }
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // Find all hyperlinked anchor tags containing file strings
    $nodes = $xpath->query("//a/@href");
    if ($nodes === false) {
        throw new Exception("XPath query failed while scanning directory listing.");
    }

    $syncedCount = 0;
    $skippedCount = 0;

    foreach ($nodes as $node) {
        $filename = trim($node->nodeValue);

        // Regex expression matches project filenames: type_matric_index.extension
        // Example: p_B032410001_1.jpeg, v_B032410001_1.mp4, etc.
        if (!preg_match('/^([pavd])_([BM]\d{9})_\d+\.(jpeg|jpg|mp3|pdf|mp4)$/i', $filename, $matches)) {
            $skippedCount++;
            continue; // not a recognized project filename, skip silently
        }

        $typePrefix   = strtolower($matches[1]); // p, a, d, v
        $matricNumber = strtoupper($matches[2]); // e.g., B032410001
        $extension    = strtolower($matches[3]);

        // Map standard system attributes based on prefix rules
        $fileType = 'document';
        $mimeType = 'application/pdf';
        $columnName = 'docStu'; // default for document
        $dateColumn = 'docStu_date';
        
        if ($typePrefix === 'p') { 
            $fileType = 'image'; 
            $mimeType = 'image/jpeg';
            $columnName = 'photoStu';
            $dateColumn = 'photoStu_date';
        }
        if ($typePrefix === 'a') { 
            $fileType = 'audio'; 
            $mimeType = 'audio/mpeg';
            $columnName = 'audioStu';
            $dateColumn = 'audioStu_date';
        }
        if ($typePrefix === 'v') { 
            $fileType = 'video'; 
            $mimeType = 'video/mp4';
            $columnName = 'videoStu';
            $dateColumn = 'videoStu_date';
        }

        $fullFilePath = rtrim($targetUrl, '/') . '/' . $filename;

        try {
            // 3. Check if student exists in vstu table
            $checkQuery = "SELECT matric_no FROM mmdb2026.vstu WHERE matric_no = ?";
            $checkStmt = $conn->prepare($checkQuery);
            if ($checkStmt === false) {
                throw new Exception("Failed to prepare check query: " . $conn->error);
            }
            $checkStmt->bind_param("s", $matricNumber);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $studentExists = $checkResult->num_rows > 0;
            $checkStmt->close();

            if ($studentExists) {
                // Update existing student with the file path
                // Query: UPDATE mmdb2026.vstu SET column = ? WHERE matric_no = ?
                $updateQuery = "UPDATE mmdb2026.vstu SET $columnName = ?, $dateColumn = CURDATE() WHERE matric_no = ?";
                $updateStmt = $conn->prepare($updateQuery);
                if ($updateStmt === false) {
                    throw new Exception("Failed to prepare update query: " . $conn->error);
                }
                $updateStmt->bind_param("ss", $fullFilePath, $matricNumber);
                $updateStmt->execute();
                $updateStmt->close();
                
                error_log("syncPlatformData: Updated $columnName for $matricNumber with $fullFilePath");
            } else {
                // Insert new student with the file path
                // Query: INSERT INTO mmdb2026.vstu (matric_no, full_name, column) VALUES (?, ?, ?)
                $dummyName = "Student (" . $matricNumber . ")";
                $insertQuery = "INSERT INTO mmdb2026.vstu (matric_no, full_name, $columnName, $dateColumn) VALUES (?, ?, ?, CURDATE())";
                $insertStmt = $conn->prepare($insertQuery);
                if ($insertStmt === false) {
                    throw new Exception("Failed to prepare insert query: " . $conn->error);
                }
                $insertStmt->bind_param("sss", $matricNumber, $dummyName, $fullFilePath);
                $insertStmt->execute();
                $insertStmt->close();
                
                error_log("syncPlatformData: Inserted new student $matricNumber with $columnName");
            }

            $syncedCount++;
        } catch (mysqli_sql_exception $e) {
            // Log and move on instead of aborting the whole sync over one bad row
            error_log("syncPlatformData: failed to sync '{$filename}' - " . $e->getMessage());
            $skippedCount++;
            continue;
        } catch (Exception $e) {
            // Catch general exceptions as well
            error_log("syncPlatformData: general error syncing '{$filename}' - " . $e->getMessage());
            $skippedCount++;
            continue;
        }
    }

    return ['synced' => $syncedCount, 'skipped' => $skippedCount];
}
?>
