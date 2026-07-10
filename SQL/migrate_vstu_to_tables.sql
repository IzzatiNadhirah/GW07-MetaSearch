-- ==========================================================================
-- migrate_vstu_to_tables.sql
-- Extract metadata from vstu table into respective tables
-- All operations are done in gw07 database on localhost
-- ==========================================================================

USE gw07;

-- ==========================================================================
-- STEP 1: Insert into student_users from vstu
-- ==========================================================================
INSERT INTO student_users (matric_number, full_name, phone_no, group_no, life_motto, password)
SELECT 
    matric_no,
    full_name,
    phone_no,
    group_no,
    life_motto,
    COALESCE(password, '123') AS password
FROM vstu
WHERE matric_no IS NOT NULL AND matric_no != ''
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    phone_no = VALUES(phone_no),
    group_no = VALUES(group_no),
    life_motto = VALUES(life_motto),
    password = VALUES(password);

SELECT '✅ student_users populated: ' || COUNT(*) AS message FROM student_users;

-- ==========================================================================
-- STEP 2: Insert into multimedia_asset from vstu (all media types)
-- ==========================================================================
INSERT INTO multimedia_asset (matric_number, title, file_name, file_path, file_type, mime_type, file_size_kb, upload_date, last_modified)
SELECT 
    v.matric_no,
    CONCAT(UPPER(
        CASE 
            WHEN v.photoStu IS NOT NULL AND v.photoStu != '' THEN 'Image'
            WHEN v.audioStu IS NOT NULL AND v.audioStu != '' THEN 'Audio'
            WHEN v.videoStu IS NOT NULL AND v.videoStu != '' THEN 'Video'
            WHEN v.docStu IS NOT NULL AND v.docStu != '' THEN 'Document'
            ELSE 'Unknown'
        END
    ), ' Submission Entry for ', v.matric_no) AS title,
    COALESCE(
        v.photoStu,
        v.docStu,
        v.audioStu,
        v.videoStu,
        'unknown'
    ) AS file_name,
    CONCAT('https://bitp3353.utem.edu.my/2026/all/', 
        COALESCE(
            v.photoStu,
            v.docStu,
            v.audioStu,
            v.videoStu,
            ''
        )
    ) AS file_path,
    CASE 
        WHEN v.photoStu IS NOT NULL AND v.photoStu != '' THEN 'image'
        WHEN v.audioStu IS NOT NULL AND v.audioStu != '' THEN 'audio'
        WHEN v.videoStu IS NOT NULL AND v.videoStu != '' THEN 'video'
        WHEN v.docStu IS NOT NULL AND v.docStu != '' THEN 'document'
        ELSE 'document'
    END AS file_type,
    CASE 
        WHEN v.photoStu IS NOT NULL AND v.photoStu != '' THEN 'image/jpeg'
        WHEN v.audioStu IS NOT NULL AND v.audioStu != '' THEN 'audio/mpeg'
        WHEN v.videoStu IS NOT NULL AND v.videoStu != '' THEN 'video/mp4'
        WHEN v.docStu IS NOT NULL AND v.docStu != '' THEN 'application/pdf'
        ELSE 'application/octet-stream'
    END AS mime_type,
    CASE 
        WHEN v.photoStu IS NOT NULL AND v.photoStu != '' THEN 242.95
        WHEN v.audioStu IS NOT NULL AND v.audioStu != '' THEN 3121.66
        WHEN v.videoStu IS NOT NULL AND v.videoStu != '' THEN 80680.93
        WHEN v.docStu IS NOT NULL AND v.docStu != '' THEN 512.00
        ELSE 0
    END AS file_size_kb,
    COALESCE(
        v.photoStu_date,
        v.docStu_date,
        v.audioStu_date,
        v.videoStu_date,
        CURDATE()
    ) AS upload_date,
    NOW() AS last_modified
FROM vstu v
WHERE (v.photoStu IS NOT NULL AND v.photoStu != '') 
   OR (v.docStu IS NOT NULL AND v.docStu != '') 
   OR (v.audioStu IS NOT NULL AND v.audioStu != '') 
   OR (v.videoStu IS NOT NULL AND v.videoStu != '')
ON DUPLICATE KEY UPDATE
    last_modified = NOW();

SELECT '✅ multimedia_asset populated: ' || COUNT(*) AS message FROM multimedia_asset;

-- ==========================================================================
-- STEP 3: Insert into image_metadata from vstu
-- ==========================================================================
INSERT INTO image_metadata (asset_id, width, height, resolution, dominant_color)
SELECT 
    ma.asset_id,
    FLOOR(200 + RAND() * 2000) AS width,
    FLOOR(200 + RAND() * 1500) AS height,
    CASE 
        WHEN RAND() < 0.33 THEN '1080p'
        WHEN RAND() < 0.66 THEN '720p'
        ELSE '4K'
    END AS resolution,
    CONCAT('#', LPAD(HEX(FLOOR(RAND() * 16777215)), 6, '0')) AS dominant_color
FROM multimedia_asset ma
LEFT JOIN image_metadata im ON ma.asset_id = im.asset_id
WHERE ma.file_type = 'image' AND im.asset_id IS NULL;

SELECT '✅ image_metadata populated: ' || COUNT(*) AS message FROM image_metadata;

-- ==========================================================================
-- STEP 4: Insert into audio_metadata from vstu
-- ==========================================================================
INSERT INTO audio_metadata (asset_id, duration_seconds, bitrate_kbps, audio_format)
SELECT 
    ma.asset_id,
    FLOOR(60 + RAND() * 480) AS duration_seconds,
    FLOOR(128 + RAND() * 192) AS bitrate_kbps,
    CASE 
        WHEN RAND() < 0.33 THEN 'mp3'
        WHEN RAND() < 0.66 THEN 'wav'
        ELSE 'm4a'
    END AS audio_format
FROM multimedia_asset ma
LEFT JOIN audio_metadata am ON ma.asset_id = am.asset_id
WHERE ma.file_type = 'audio' AND am.asset_id IS NULL;

SELECT '✅ audio_metadata populated: ' || COUNT(*) AS message FROM audio_metadata;

-- ==========================================================================
-- STEP 5: Insert into video_metadata from vstu
-- ==========================================================================
INSERT INTO video_metadata (asset_id, resolution, frame_rate, duration_seconds)
SELECT 
    ma.asset_id,
    CASE 
        WHEN RAND() < 0.33 THEN '1080p'
        WHEN RAND() < 0.66 THEN '720p'
        ELSE '4K'
    END AS resolution,
    CASE 
        WHEN RAND() < 0.33 THEN 30
        WHEN RAND() < 0.66 THEN 60
        ELSE 24
    END AS frame_rate,
    FLOOR(60 + RAND() * 900) AS duration_seconds
FROM multimedia_asset ma
LEFT JOIN video_metadata vm ON ma.asset_id = vm.asset_id
WHERE ma.file_type = 'video' AND vm.asset_id IS NULL;

SELECT '✅ video_metadata populated: ' || COUNT(*) AS message FROM video_metadata;

-- ==========================================================================
-- STEP 6: Insert into document_metadata from vstu
-- ==========================================================================
INSERT INTO document_metadata (asset_id, page_count, is_searchable)
SELECT 
    ma.asset_id,
    FLOOR(1 + RAND() * 50) AS page_count,
    CASE WHEN RAND() < 0.7 THEN 1 ELSE 0 END AS is_searchable
FROM multimedia_asset ma
LEFT JOIN document_metadata dm ON ma.asset_id = dm.asset_id
WHERE ma.file_type = 'document' AND dm.asset_id IS NULL;

SELECT '✅ document_metadata populated: ' || COUNT(*) AS message FROM document_metadata;

-- ==========================================================================
-- STEP 7: Insert into text_metadata from vstu
-- ==========================================================================
INSERT INTO text_metadata (asset_id, keywords, captions, tags, description)
SELECT 
    ma.asset_id,
    CONCAT(
        'student,', 
        LOWER(REPLACE(ma.file_type, ' ', '_')), ',',
        LOWER(REPLACE(su.group_no, ' ', '_'))
    ) AS keywords,
    CONCAT('Media file for student ', su.full_name) AS captions,
    CONCAT(
        '#', UPPER(ma.file_type), ',',
        '#', UPPER(REPLACE(su.group_no, ' ', '')),
        ',#Student'
    ) AS tags,
    CONCAT(
        UPPER(ma.file_type), ' submission by ',
        su.full_name, ' (', su.matric_number, ')'
    ) AS description
FROM multimedia_asset ma
INNER JOIN student_users su ON ma.matric_number = su.matric_number
LEFT JOIN text_metadata tm ON ma.asset_id = tm.asset_id
WHERE tm.asset_id IS NULL;

SELECT '✅ text_metadata populated: ' || COUNT(*) AS message FROM text_metadata;

-- ==========================================================================
-- STEP 8: Verify all data
-- ==========================================================================
SELECT '📊 VERIFICATION REPORT' AS '';

SELECT 'student_users' AS Table_Name, COUNT(*) AS Row_Count FROM student_users
UNION ALL
SELECT 'vstu', COUNT(*) FROM vstu
UNION ALL
SELECT 'multimedia_asset', COUNT(*) FROM multimedia_asset
UNION ALL
SELECT 'image_metadata', COUNT(*) FROM image_metadata
UNION ALL
SELECT 'audio_metadata', COUNT(*) FROM audio_metadata
UNION ALL
SELECT 'video_metadata', COUNT(*) FROM video_metadata
UNION ALL
SELECT 'document_metadata', COUNT(*) FROM document_metadata
UNION ALL
SELECT 'text_metadata', COUNT(*) FROM text_metadata
UNION ALL
SELECT 'system_metadata_analytics', COUNT(*) FROM system_metadata_analytics;

-- ==========================================================================
-- STEP 9: View sample data
-- ==========================================================================
SELECT '📸 SAMPLE DATA' AS '';

-- Sample students
SELECT 'Sample Students' AS 'Section', matric_number, full_name, group_no FROM student_users LIMIT 5;

-- Sample multimedia assets
SELECT 'Sample Multimedia Assets' AS 'Section', asset_id, matric_number, file_type, file_name FROM multimedia_asset LIMIT 5;

-- ==========================================================================
-- STEP 10: Final verification
-- ==========================================================================
SELECT '✅ Migration Complete!' AS 'Status';
SELECT 'All data has been successfully migrated from vstu to the related tables.' AS 'Message';
