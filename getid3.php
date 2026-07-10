<?php
// ==========================================================================
// extract_metadata_simple.php - Simple metadata extraction (no getID3)
// Uses file extensions to generate realistic placeholder metadata
// ==========================================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db_connect.php';

$conn = isset($GLOBALS['conn']) ? $GLOBALS['conn'] : null;

if ($conn === null || !$conn->ping()) {
    die("❌ Database connection failed!");
}

define('BASE_URL', 'https://bitp3353.utem.edu.my/2026/all/');

echo "========================================\n";
echo "  SIMPLE METADATA EXTRACTION\n";
echo "  (No getID3 required - uses file extensions)\n";
echo "========================================\n\n";

// --------------------------------------------------------------------------
// Process IMAGES
// --------------------------------------------------------------------------
echo "📁 Processing images...\n";
$imageQuery = "SELECT ma.asset_id, ma.file_name 
               FROM multimedia_asset ma
               LEFT JOIN image_metadata im ON ma.asset_id = im.asset_id
               WHERE ma.file_type = 'image' AND im.asset_id IS NULL";

$imageResult = $conn->query($imageQuery);
$imageCount = 0;

while ($row = $imageResult->fetch_assoc()) {
    $ext = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));
    
    $width = ($ext == 'png') ? 1200 : 1920;
    $height = ($ext == 'png') ? 800 : 1080;
    $resolution = ($ext == 'png') ? '720p' : '1080p';
    
    $sql = "INSERT INTO image_metadata (asset_id, width, height, resolution, dominant_color) 
            VALUES (?, ?, ?, ?, '#3366FF')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $row['asset_id'], $width, $height, $resolution);
    $stmt->execute();
    $stmt->close();
    $imageCount++;
}

echo "✅ Inserted $imageCount image metadata\n\n";

// --------------------------------------------------------------------------
// Process AUDIO
// --------------------------------------------------------------------------
echo "📁 Processing audio...\n";
$audioQuery = "SELECT ma.asset_id, ma.file_name 
               FROM multimedia_asset ma
               LEFT JOIN audio_metadata am ON ma.asset_id = am.asset_id
               WHERE ma.file_type = 'audio' AND am.asset_id IS NULL";

$audioResult = $conn->query($audioQuery);
$audioCount = 0;

while ($row = $audioResult->fetch_assoc()) {
    $ext = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));
    
    $duration = 180;
    $bitrate = 128;
    $format = 'mp3';
    
    if ($ext == 'mp3') { $duration = 180; $bitrate = 128; $format = 'mp3'; }
    elseif ($ext == 'wav') { $duration = 120; $bitrate = 768; $format = 'wav'; }
    elseif ($ext == 'm4a') { $duration = 200; $bitrate = 256; $format = 'm4a'; }
    
    $sql = "INSERT INTO audio_metadata (asset_id, duration_seconds, bitrate_kbps, audio_format) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $row['asset_id'], $duration, $bitrate, $format);
    $stmt->execute();
    $stmt->close();
    $audioCount++;
}

echo "✅ Inserted $audioCount audio metadata\n\n";

// --------------------------------------------------------------------------
// Process VIDEO
// --------------------------------------------------------------------------
echo "📁 Processing video...\n";
$videoQuery = "SELECT ma.asset_id, ma.file_name 
               FROM multimedia_asset ma
               LEFT JOIN video_metadata vm ON ma.asset_id = vm.asset_id
               WHERE ma.file_type = 'video' AND vm.asset_id IS NULL";

$videoResult = $conn->query($videoQuery);
$videoCount = 0;

while ($row = $videoResult->fetch_assoc()) {
    $filename = strtolower($row['file_name']);
    
    $resolution = '1080p';
    $frameRate = 30;
    $duration = 300;
    
    if (strpos($filename, '720p') !== false) { $resolution = '720p'; }
    elseif (strpos($filename, '4k') !== false) { $resolution = '4K'; }
    if (strpos($filename, '60fps') !== false) { $frameRate = 60; }
    elseif (strpos($filename, '24fps') !== false) { $frameRate = 24; }
    
    $sql = "INSERT INTO video_metadata (asset_id, resolution, frame_rate, duration_seconds) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isii", $row['asset_id'], $resolution, $frameRate, $duration);
    $stmt->execute();
    $stmt->close();
    $videoCount++;
}

echo "✅ Inserted $videoCount video metadata\n\n";

// --------------------------------------------------------------------------
// Process DOCUMENTS
// --------------------------------------------------------------------------
echo "📁 Processing documents...\n";
$docQuery = "SELECT ma.asset_id 
             FROM multimedia_asset ma
             LEFT JOIN document_metadata dm ON ma.asset_id = dm.asset_id
             WHERE ma.file_type = 'document' AND dm.asset_id IS NULL";

$docResult = $conn->query($docQuery);
$docCount = 0;

while ($row = $docResult->fetch_assoc()) {
    $pageCount = rand(5, 50);
    $isSearchable = rand(0, 1);
    
    $sql = "INSERT INTO document_metadata (asset_id, page_count, is_searchable) 
            VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $row['asset_id'], $pageCount, $isSearchable);
    $stmt->execute();
    $stmt->close();
    $docCount++;
}

echo "✅ Inserted $docCount document metadata\n\n";

// --------------------------------------------------------------------------
// FINAL VERIFICATION
// --------------------------------------------------------------------------
echo "========================================\n";
echo "  VERIFICATION REPORT\n";
echo "========================================\n\n";

$tables = ['image_metadata', 'audio_metadata', 'video_metadata', 'document_metadata'];
foreach ($tables as $table) {
    $countResult = $conn->query("SELECT COUNT(*) as count FROM $table");
    if ($countResult) {
        $count = $countResult->fetch_assoc()['count'];
        echo "  $table: $count rows\n";
    }
}

echo "\n✅ Simple metadata extraction complete!\n";
?>
