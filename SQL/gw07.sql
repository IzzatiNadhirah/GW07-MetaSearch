-- ==========================================================================
-- gw07.sql - MetaSearch Database Schema
-- ==========================================================================
-- This script creates all necessary tables for the MetaSearch project.
-- Tables are dropped if they already exist to ensure a clean installation.
-- ==========================================================================

-- ==========================================================================
-- 1. STUDENT CORE TABLE (dictionary compliant)
-- ==========================================================================
DROP TABLE IF EXISTS student_users;

CREATE TABLE student_users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    matric_number VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    phone_no VARCHAR(20) DEFAULT NULL,
    group_no VARCHAR(10) DEFAULT NULL,
    life_motto TEXT DEFAULT NULL,
    password VARCHAR(100) NOT NULL DEFAULT '123',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================================================
-- 2. VSTU TABLE (Copy of mmdb2026.vstu for local use)
-- ==========================================================================
DROP TABLE IF EXISTS vstu;

CREATE TABLE vstu (
    id INT(11),
    matric_no VARCHAR(20),
    full_name VARCHAR(100),
    phone_no VARCHAR(20),
    group_no VARCHAR(10),
    life_motto TEXT,
    password VARCHAR(100),
    photoStu VARCHAR(255),
    photoStu_date DATE,
    docStu VARCHAR(255),
    docStu_date DATE,
    audioStu VARCHAR(255),
    audioStu_date DATE,
    videoStu VARCHAR(255),
    videoStu_date DATE
);

-- ==========================================================================
-- 3. CENTRAL MULTIMEDIA ASSET TABLE
-- ==========================================================================
DROP TABLE IF EXISTS multimedia_asset;

CREATE TABLE multimedia_asset (
    asset_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    matric_number VARCHAR(20) NOT NULL,
    title VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type ENUM('image','audio','video','document') NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size_kb DECIMAL(12,2) NOT NULL,
    upload_date DATE NOT NULL,
    last_modified DATETIME NOT NULL,
    UNIQUE KEY uq_matric_file (matric_number, file_name),
    FOREIGN KEY (matric_number) REFERENCES student_users(matric_number) ON DELETE CASCADE
);

-- ==========================================================================
-- 4. IMAGE METADATA
-- ==========================================================================
DROP TABLE IF EXISTS image_metadata;

CREATE TABLE image_metadata (
    image_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    asset_id INT(11) NOT NULL UNIQUE,
    width INT(11) DEFAULT NULL,
    height INT(11) DEFAULT NULL,
    resolution VARCHAR(50) DEFAULT NULL,
    dominant_color VARCHAR(7) DEFAULT NULL,
    FOREIGN KEY (asset_id) REFERENCES multimedia_asset(asset_id) ON DELETE CASCADE
);

-- ==========================================================================
-- 5. AUDIO METADATA
-- ==========================================================================
DROP TABLE IF EXISTS audio_metadata;

CREATE TABLE audio_metadata (
    audio_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    asset_id INT(11) NOT NULL UNIQUE,
    duration_seconds INT(11) NOT NULL,
    bitrate_kbps INT(11) DEFAULT NULL,
    audio_format VARCHAR(50) DEFAULT NULL,
    FOREIGN KEY (asset_id) REFERENCES multimedia_asset(asset_id) ON DELETE CASCADE
);

-- ==========================================================================
-- 6. VIDEO METADATA
-- ==========================================================================
DROP TABLE IF EXISTS video_metadata;

CREATE TABLE video_metadata (
    video_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    asset_id INT(11) NOT NULL UNIQUE,
    resolution VARCHAR(50) DEFAULT NULL,
    frame_rate INT(11) DEFAULT NULL,
    duration_seconds INT(11) NOT NULL,
    FOREIGN KEY (asset_id) REFERENCES multimedia_asset(asset_id) ON DELETE CASCADE
);

-- ==========================================================================
-- 7. DOCUMENT METADATA
-- ==========================================================================
DROP TABLE IF EXISTS document_metadata;

CREATE TABLE document_metadata (
    document_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    asset_id INT(11) NOT NULL UNIQUE,
    page_count INT(11) NOT NULL DEFAULT 1,
    is_searchable TINYINT(1) DEFAULT 1,
    FOREIGN KEY (asset_id) REFERENCES multimedia_asset(asset_id) ON DELETE CASCADE
);

-- ==========================================================================
-- 8. TEXT METADATA (TBR)
-- ==========================================================================
DROP TABLE IF EXISTS text_metadata;

CREATE TABLE text_metadata (
    text_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    asset_id INT(11) NOT NULL UNIQUE,
    keywords VARCHAR(255) DEFAULT NULL,
    captions TEXT DEFAULT NULL,
    tags VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    FOREIGN KEY (asset_id) REFERENCES multimedia_asset(asset_id) ON DELETE CASCADE,
    FULLTEXT INDEX idx_tbr_search (keywords, tags, description)
);

-- ==========================================================================
-- 9. SYSTEM ANALYTICS (Dashboard)
-- ==========================================================================
DROP TABLE IF EXISTS system_metadata_analytics;

CREATE TABLE system_metadata_analytics (
    sys_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    total_tracked_users INT(11) DEFAULT 0,
    upload_frequency_today INT(11) DEFAULT 0,
    avg_file_size_kb DECIMAL(12,2) DEFAULT 0.00,
    most_searched_keyword VARCHAR(255) DEFAULT NULL,
    last_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
