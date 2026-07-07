<?php
// ==========================================================================
// sync_engine.php - Remote Data Synchronization Engine
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
        if ($typePrefix === 'p') { $fileType = 'image'; $mimeType = 'image/jpeg'; }
        if ($typePrefix === 'a') { $fileType = 'audio'; $mimeType = 'audio/mpeg'; }
        if ($typePrefix === 'v') { $fileType = 'video'; $mimeType = 'video/mp4'; }

        // Construct synthetic asset properties
        $title = strtoupper($fileType) . " Submission Entry for " . $matricNumber;
        $fullFilePath = rtrim($targetUrl, '/') . '/' . $filename;
        $simulatedSizeKb = ($fileType === 'video') ? 80680.93 : (($fileType === 'audio') ? 3121.66 : 242.95);

        try {
            // 3. Keep User Profile table safely synchronized
            // Query: INSERT INTO mmdb2026.vstu (matric_no, full_name) VALUES (?, ?)
            //        ON DUPLICATE KEY UPDATE matric_no = matric_no
            $dummyName = "Student (" . $matricNumber . ")";
            $userQuery = "INSERT INTO mmdb2026.vstu (matric_no, full_name)
                          VALUES (?, ?)
                          ON DUPLICATE KEY UPDATE matric_no = matric_no";
            $stmt = $conn->prepare($userQuery);
            if ($stmt === false) {
                throw new Exception("Failed to prepare user query: " . $conn->error);
            }
            $stmt->bind_param("ss", $matricNumber, $dummyName);
            $stmt->execute();
            $stmt->close();

            // 4. Upsert key attributes directly inside core Multimedia asset table
            // Query: INSERT INTO multimedia_asset (matric_number, title, file_name, file_path, file_type, mime_type, file_size_kb, upload_date, last_modified)
            //        VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), NOW())
            //        ON DUPLICATE KEY UPDATE last_modified = NOW()
            $assetQuery = "INSERT INTO multimedia_asset (matric_number, title, file_name, file_path, file_type, mime_type, file_size_kb, upload_date, last_modified)
                           VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), NOW())
                           ON DUPLICATE KEY UPDATE last_modified = NOW()";
            $stmt = $conn->prepare($assetQuery);
            if ($stmt === false) {
                throw new Exception("Failed to prepare asset query: " . $conn->error);
            }
            $stmt->bind_param("ssssssd", $matricNumber, $title, $filename, $fullFilePath, $fileType, $mimeType, $simulatedSizeKb);
            $stmt->execute();
            $stmt->close();

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
