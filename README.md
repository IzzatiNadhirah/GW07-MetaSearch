# GW07
# MetaSearch - Intelligent Multimedia Metadata Search Platform
Multimedia Database Group Project

## 📋 Project Overview

MetaSearch is a comprehensive multimedia database management system designed for the Multimedia Database Systems course. It provides intelligent search capabilities across various media types including images, videos, audio files, and documents through three distinct retrieval strategies: Attribute-Based Retrieval (ABR), Text-Based Retrieval (TBR), and Content-Based Retrieval (CBR).

## 👥 Group Members

| No. | Name | Matric Number |
|-----|------|---------------|
| 1 | Nur Izzati Binti Zaidi | - |
| 2 | Nurul Izzati Nadhirah Binti Iskandar Faidzal | - |
| 3 | Toh Shuai Ting | - |
| 4 | Wan Nur Adlin Syauqina Binti Wan Ahmad Fadillah | - |

---

## 🚀 Features

### 1. Attribute-Based Retrieval (ABR)
- Filter multimedia files by:
  - File Type (Image, Video, Audio, Document)
  - File Size (slider)
  - Resolution (480p, 720p, 1080p, 4K, 8K)
  - Duration (seconds slider)
  - Owner/Uploader (name or matric number)

### 2. Text-Based Retrieval (TBR)
- Search using:
  - Keywords
  - Tags
  - Titles
  - Descriptions
  - Captions
- Full-text search with relevance ranking
- Suggested tags for easy discovery
- Clickable tag chips for quick search

### 3. Content-Based Retrieval (CBR)
- Search by:
  - **Images**: Dominant Color (partial match)
  - **Videos**: Duration in minutes (>=)
  - **Audio**: Duration in minutes (>=)
  - **Documents**: Page Count (>=)
- Real-time field updates based on media type selection

### 4. Dashboard Analytics
- Real-time statistics
- Total students count
- File type distribution (Images, Videos, Audio, Documents)
- Total assets count
- Recent uploads with owner information
- Sync status monitoring

### 5. Data Synchronization
- Auto-sync with remote repository
- Manual sync via "Force Resync" button
- Duplicate prevention with UPSERT logic

---

## 🛠️ Technology Stack

| Component | Technology |
|-----------|------------|
| **Backend** | PHP 7.4+ |
| **Database** | MySQL / MariaDB |
| **Frontend** | HTML5, CSS3, JavaScript |
| **CSS Framework** | Bootstrap 5.3 |
| **Icons** | FontAwesome 6.4 |
| **Server** | Apache / XAMPP |
