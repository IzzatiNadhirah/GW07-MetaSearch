<?php
// ==========================================================================
// index.php - MetaSearch Main Landing Page
// Combines: Group member listing + Live project analytics + Search interface
// ==========================================================================

session_start();

require_once 'config/db_connect.php';
require_once 'config/db_queries.php';
require_once 'sync_engine.php';

// ✅ FIXED: Get connection from GLOBALS array
$conn = isset($GLOBALS['conn']) ? $GLOBALS['conn'] : null;

// ✅ FIXED: Check if connection exists and is valid
if ($conn === null || !$conn->ping()) {
    $conn_error = "Database connection is not available.";
} else {
    $conn_error = null;
}

// --------------------------------------------------------------------------
// CHECK: Is database available?
// --------------------------------------------------------------------------
$db_available = ($conn !== null && $conn->ping());

// If DB is not available, log it
if (!$db_available) {
    error_log("WARNING: Database connection is not available. Student data will be limited.");
}

// --------------------------------------------------------------------------
// 1. Get Group from URL parameter or POST (from dropdown)
// --------------------------------------------------------------------------
// Get all available groups from mmdb2026.vstu for the dropdown
$allGroups = [];
if ($db_available) {
    $allGroups = getGroups($conn);
}

// Get selected group from POST or GET
$selectedGroup = isset($_POST['group_select']) ? $_POST['group_select'] : (isset($_GET['group']) ? $_GET['group'] : '');

// Sanitize the selected group (only allow alphanumeric and dash)
if (!empty($selectedGroup)) {
    $selectedGroup = preg_replace('/[^a-zA-Z0-9\-]/', '', $selectedGroup);
}

// Check if the selected group exists in the available groups list
$groupExists = false;
if (!empty($selectedGroup) && !empty($allGroups)) {
    $groupExists = in_array($selectedGroup, $allGroups);
}

// If the selected group doesn't exist in the list, treat as "No group selected"
if (empty($selectedGroup) || !$groupExists) {
    $selectedGroup = ''; // No group selected
}

// Set the group for display (empty if no group selected)
$group = $selectedGroup;

// --------------------------------------------------------------------------
// 2. Trigger Dynamic Live Synchronization
// --------------------------------------------------------------------------
$targetRepository = "https://bitp3353.utem.edu.my/2026/all/";
$syncStatus = "Success";
$syncDetails = ['synced' => 0, 'skipped' => 0];

try {
    $syncDetails = syncPlatformData($targetRepository, $conn);
} catch (Exception $e) {
    $syncStatus = "Error: " . $e->getMessage();
}

// --------------------------------------------------------------------------
// 3. Get Group Members from mmdb2026.vstu
// --------------------------------------------------------------------------
$members = [];
$error_message = null;

if ($db_available && !empty($group)) {
    $members = getGroupMembers($conn, $group);
    if (empty($members)) {
        $error_message = "No members found for group: " . htmlspecialchars($group);
    }
} elseif ($db_available && empty($group)) {
    $error_message = null;
} else {
    $error_message = "Database is currently unavailable. Please ensure you have network access or contact your administrator.";
}

// --------------------------------------------------------------------------
// 4. Get Dashboard Statistics
// --------------------------------------------------------------------------
$counts = ['image' => 0, 'video' => 0, 'audio' => 0, 'document' => 0, 'total' => 0];
$studentCount = 0;
$recentAssets = [];

if ($db_available) {
    $counts = getAssetStatistics($conn);
    $studentCount = getStudentCount($conn);
    $recentAssets = getRecentUploads($conn);
} else {
    $studentCount = 'N/A';
}

// --------------------------------------------------------------------------
// 5. Handle Search (ABR + TBR combined)
// --------------------------------------------------------------------------
$searchResults = [];
$searchPerformed = false;

if ($db_available) {
    $fileType = $_GET['file_type'] ?? '';
    $query = $_GET['query'] ?? '';
    $searchData = searchAssets($conn, $fileType, $query, $db_available);
    $searchResults = $searchData['results'];
    $searchPerformed = $searchData['performed'];
} else {
    $searchResults = [];
    $searchPerformed = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MetaSearch | Live Project Analytics Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Additional styles for the combined page */
        .group-badge {
            background: rgba(0, 210, 255, 0.15);
            border: 1px solid var(--accent);
            color: var(--accent);
            padding: 8px 25px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1.4rem;
        }
        .group-badge.no-group {
            background: rgba(255, 100, 100, 0.15);
            border: 1px solid #ff6b6b;
            color: #ff6b6b;
        }
        .member-card {
            background: var(--bg-panel);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px 20px;
            transition: all 0.2s;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        .member-card:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
        }
        .member-card .avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--bg-primary);
            border: 2px solid var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: var(--accent);
            flex-shrink: 0;
            overflow: hidden;
        }
        .member-card .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .member-card .info {
            flex: 1;
            min-width: 0;
        }
        .member-card .name {
            color: #fff;
            font-weight: 600;
            font-size: 1.05rem;
        }
        .member-card .matric {
            color: var(--accent);
            font-family: monospace;
            font-size: 0.9rem;
        }
        .member-card .motto {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-style: italic;
        }
        .member-card .media-icons {
            margin-top: 8px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .member-card .media-icons .badge {
            font-size: 0.65rem;
            padding: 3px 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .member-card .media-icons .badge:hover {
            transform: scale(1.05);
            opacity: 0.9;
        }
        .member-card .media-icons .badge a {
            color: inherit;
            text-decoration: none;
        }
        .sync-status {
            font-size: 0.85rem;
        }
        .sync-status .success { color: #4caf50; }
        .sync-status .error { color: #ff6b6b; }
        .stat-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        .stat-label {
            color: #cbd5e1 !important;
            font-weight: 500;
        }
        .stat-value {
            color: #ffffff !important;
            font-weight: 700;
        }
        .sidebar .nav-link {
            color: #cbd5e1;
            padding: 10px 15px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .sidebar .nav-link:hover {
            background: rgba(0, 210, 255, 0.1);
            color: #fff;
        }
        .sidebar .nav-link i {
            width: 20px;
        }
        .sidebar .nav-link.active {
            background: rgba(0, 210, 255, 0.15);
            color: var(--accent);
        }
        .error-banner {
            background: var(--error-bg, #2a1414);
            border: 1px solid var(--error-border, #5c1f1f);
            color: var(--error-text, #ff6b6b);
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .warning-banner {
            background: #1a2a14;
            border: 1px solid #4caf50;
            color: #8bc34a;
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .group-dropdown {
            background: var(--bg-panel);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-main);
            padding: 6px 12px;
            font-size: 0.9rem;
            width: 100%;
        }
        .group-dropdown:focus {
            outline: none;
            border-color: var(--accent);
        }
        .group-dropdown option {
            background: var(--bg-primary);
            color: var(--text-main);
        }
        .group-dropdown option:disabled {
            color: var(--text-dim);
        }
        .sidebar .group-select-form {
            margin-bottom: 15px;
        }
        .sidebar .group-select-form .form-label {
            color: var(--text-muted);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            display: block;
        }

        /* Card text colors */
        .card-custom h6 {
            color: #cbd5e1 !important;
        }
        .card-custom h2 {
            color: #ffffff !important;
        }
        .text-muted {
            color: #94a3b8 !important;
        }
        .text-white-50 {
            color: #94a3b8 !important;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- ==============================================================
        SIDEBAR (Left Panel)
        ============================================================== -->
        <div class="col-md-2 sidebar p-4">
            <h3 class="fw-bold text-cyan mb-4">
                <i class="fa-solid fa-database me-2"></i>MetaSearch
            </h3>
            
            <div class="mb-4">
                <span class="badge bg-success sync-status">
                    <i class="fa-solid fa-rotate me-1"></i> Live Connected
                </span>
            </div>

            <!-- Group Selection Dropdown -->
            <form method="POST" class="group-select-form">
                <label class="form-label">Select Group</label>
                <select name="group_select" class="group-dropdown" onchange="this.form.submit()">
                    <?php if (empty($allGroups)): ?>
                        <option value="" disabled selected>No groups available</option>
                    <?php else: ?>
                        <option value="" <?php echo empty($group) ? 'selected' : ''; ?>>-- No Group Selected --</option>
                        <?php foreach ($allGroups as $g): ?>
                            <option value="<?php echo htmlspecialchars($g); ?>" <?php echo ($group == $g) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($g); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </form>

            <hr class="text-secondary">

            <!-- Team Members -->
            <p class="small text-muted mb-2">DATA STRATEGY PARTNERS</p>
            <div class="small text-white-50">
                <?php if (empty($members)): ?>
                    <p class="text-muted">No members found for this group.</p>
                <?php else: ?>
                    <?php foreach ($members as $index => $member): ?>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="text-cyan"><?php echo $index + 1; ?>.</span>
                            <span><?php echo htmlspecialchars($member['full_name']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <hr class="text-secondary">

            <!-- Navigation -->
            <ul class="nav nav-pills flex-column mt-2">
                <li class="nav-item">
                    <a href="index.php" class="nav-link active">
                        <i class="fa-solid fa-house me-2"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a href="ABR/abr_search.html" class="nav-link">
                        <i class="fa-solid fa-sliders me-2"></i> ABR
                    </a>
                </li>
                <li class="nav-item">
                    <a href="TBR/tbr.php" class="nav-link">
                        <i class="fa-solid fa-magnifying-glass me-2"></i> TBR
                    </a>
                </li>
                <li class="nav-item">
                    <a href="CBR/cbr_search.php" class="nav-link">
                        <i class="fa-solid fa-chart-line me-2"></i> CBR
                    </a>
                </li>
            </ul>

            <hr class="text-secondary mt-4">
            <div class="small text-muted">
                <i class="fa-regular fa-clock me-1"></i>
                <span id="liveClock">--:--:--</span>
            </div>
        </div>

        <!-- ==============================================================
        MAIN CONTENT (Right Panel)
        ============================================================== -->
        <div class="col-md-10 p-4 main-content">
            
            <!-- Header -->
            <header class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary">
                <div>
                    <h1 class="fw-bold text-cyan">PROJECT METADATA DASHBOARD</h1>
                    <p class="text-muted m-0">
                        <i class="fa-solid fa-link me-1"></i>
                        Repository: <code><?php echo htmlspecialchars($targetRepository); ?></code>
                    </p>
                    <p class="text-muted m-0 sync-status">
                        <i class="fa-solid fa-rotate me-1"></i>
                        Sync Status: 
                        <span class="<?php echo $syncStatus === 'Success' ? 'success' : 'error'; ?>">
                            <?php echo htmlspecialchars($syncStatus); ?>
                        </span>
                        <?php if ($syncStatus === 'Success'): ?>
                            (<?php echo $syncDetails['synced']; ?> synced, <?php echo $syncDetails['skipped']; ?> skipped)
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <button onclick="window.location.reload();" class="btn btn-outline-info">
                        <i class="fa-solid fa-arrows-rotate me-2"></i>Force Resync
                    </button>
                </div>
            </header>

            <!-- Database Warning Banner -->
            <?php if (!$db_available): ?>
                <div class="warning-banner">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    <strong>Notice:</strong> Database is currently unavailable. 
                    Student data will be limited until connection is restored.
                </div>
            <?php endif; ?>

            <!-- Error Display -->
            <?php if ($error_message): ?>
                <div class="error-banner">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- ============================================================
            STATISTICS CARDS
            ============================================================ -->
            <div class="row g-4 mb-5">
                <div class="col-md-2">
                    <div class="card-custom p-3 text-center">
                        <i class="fa-solid fa-users text-cyan stat-icon"></i>
                        <h6 class="text-muted mt-2 stat-label">Total Students</h6>
                        <h2 class="fw-bold text-white stat-value"><?php echo $studentCount; ?></h2>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card-custom p-3 text-center">
                        <i class="fa-solid fa-image text-primary stat-icon"></i>
                        <h6 class="text-muted mt-2 stat-label">Images</h6>
                        <h2 class="fw-bold text-white stat-value"><?php echo $counts['image']; ?></h2>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card-custom p-3 text-center">
                        <i class="fa-solid fa-music text-warning stat-icon"></i>
                        <h6 class="text-muted mt-2 stat-label">Audio</h6>
                        <h2 class="fw-bold text-white stat-value"><?php echo $counts['audio']; ?></h2>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card-custom p-3 text-center">
                        <i class="fa-solid fa-file-pdf text-danger stat-icon"></i>
                        <h6 class="text-muted mt-2 stat-label">Documents</h6>
                        <h2 class="fw-bold text-white stat-value"><?php echo $counts['document']; ?></h2>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card-custom p-3 text-center">
                        <i class="fa-solid fa-video text-success stat-icon"></i>
                        <h6 class="text-muted mt-2 stat-label">Videos</h6>
                        <h2 class="fw-bold text-white stat-value"><?php echo $counts['video']; ?></h2>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card-custom p-3 text-center">
                        <i class="fa-solid fa-database text-info stat-icon"></i>
                        <h6 class="text-muted mt-2 stat-label">Total Assets</h6>
                        <h2 class="fw-bold text-white stat-value"><?php echo $counts['total']; ?></h2>
                    </div>
                </div>
            </div>

            <!-- ============================================================
            GROUP MEMBERS SECTION
            ============================================================ -->
            <div class="card-custom p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-cyan mb-0">
                        <i class="fa-solid fa-users me-2"></i>Group Members
                        <?php if (!empty($group)): ?>
                            — <?php echo htmlspecialchars($group); ?>
                        <?php else: ?>
                            <span class="text-muted" style="font-size:0.9rem;">(No group selected)</span>
                        <?php endif; ?>
                    </h5>
                    <span class="text-muted small"><?php echo count($members); ?> member(s)</span>
                </div>
                
                <?php if (empty($members)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="fa-solid fa-user-slash me-2"></i>
                        <?php if (empty($group)): ?>
                            Please select a group from the dropdown above.
                        <?php elseif (!$db_available): ?>
                            No members found for group "<?php echo htmlspecialchars($group); ?>"
                            <br><small>(Database connection unavailable)</small>
                        <?php else: ?>
                            No members found for group "<?php echo htmlspecialchars($group); ?>"
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($members as $member): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="member-card">
                                    <!-- Avatar / Profile Picture -->
                                    <div class="avatar">
                                        <?php if (!empty($member['photoStu'])): ?>
                                            <img src="<?php echo htmlspecialchars($member['photoStu']); ?>" alt="Profile Photo" onerror="this.style.display='none';this.parentElement.innerHTML='<i class=\'fa-solid fa-user\'></i>';">
                                        <?php else: ?>
                                            <i class="fa-solid fa-user"></i>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Student Info -->
                                    <div class="info">
                                        <div class="name">
                                            <?php echo htmlspecialchars(strtoupper($member['full_name'])); ?>
                                        </div>
                                        <div class="matric">
                                            <i class="fa-regular fa-id-card me-1"></i>
                                            <?php echo htmlspecialchars($member['matric_no']); ?>
                                        </div>
                                        <?php if (!empty($member['life_motto'])): ?>
                                            <div class="motto mt-1">
                                                "<?php echo htmlspecialchars($member['life_motto']); ?>"
                                            </div>
                                        <?php endif; ?>
                                        <!-- Media Icons - Clickable to open media -->
                                        <div class="media-icons">
                                            <?php if (!empty($member['photoStu'])): ?>
                                                <a href="<?php echo htmlspecialchars($member['photoStu']); ?>" target="_blank" class="badge bg-primary" title="View Photo">
                                                    <i class="fa-solid fa-image me-1"></i>Photo
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-secondary" title="No Photo Available">
                                                    <i class="fa-solid fa-image me-1"></i>No Photo
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($member['docStu'])): ?>
                                                <a href="<?php echo htmlspecialchars($member['docStu']); ?>" target="_blank" class="badge bg-danger" title="View Document">
                                                    <i class="fa-solid fa-file-pdf me-1"></i>Doc
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-secondary" title="No Document Available">
                                                    <i class="fa-solid fa-file-pdf me-1"></i>No Doc
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($member['audioStu'])): ?>
                                                <a href="<?php echo htmlspecialchars($member['audioStu']); ?>" target="_blank" class="badge bg-warning" title="Play Audio">
                                                    <i class="fa-solid fa-music me-1"></i>Audio
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-secondary" title="No Audio Available">
                                                    <i class="fa-solid fa-music me-1"></i>No Audio
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($member['videoStu'])): ?>
                                                <a href="<?php echo htmlspecialchars($member['videoStu']); ?>" target="_blank" class="badge bg-success" title="Play Video">
                                                    <i class="fa-solid fa-video me-1"></i>Video
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-secondary" title="No Video Available">
                                                    <i class="fa-solid fa-video me-1"></i>No Video
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ============================================================
            SEARCH SECTION (ABR + TBR Combined)
            ============================================================ -->
            <section class="card-custom p-4 mb-4">
                <h5 class="fw-bold mb-3 text-cyan">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>
                    Intelligent Metadata Search Engine
                </h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                        <input type="text" name="query" class="form-control bg-dark text-white border-secondary" 
                               placeholder="Search by filename, title, or matric number..." 
                               value="<?php echo htmlspecialchars($_GET['query'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="file_type" class="form-select bg-dark text-white border-secondary">
                            <option value="">All Media Formats (ABR Filter)</option>
                            <option value="image" <?php echo (($_GET['file_type'] ?? '') == 'image') ? 'selected' : ''; ?>>Image</option>
                            <option value="audio" <?php echo (($_GET['file_type'] ?? '') == 'audio') ? 'selected' : ''; ?>>Audio</option>
                            <option value="video" <?php echo (($_GET['file_type'] ?? '') == 'video') ? 'selected' : ''; ?>>Video</option>
                            <option value="document" <?php echo (($_GET['file_type'] ?? '') == 'document') ? 'selected' : ''; ?>>Document</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-info fw-bold">
                            <i class="fa-solid fa-search me-2"></i>Execute Search
                        </button>
                    </div>
                </form>
                <div class="text-muted small mt-2">
                    <i class="fa-regular fa-lightbulb me-1"></i>
                    Tip: Leave both fields empty to view all recent assets
                </div>
            </section>

            <!-- ============================================================
            SEARCH RESULTS / RECENT ASSETS
            ============================================================ -->
            <div class="card-custom p-4">
                <h5 class="fw-bold text-white mb-3">
                    <?php if ($searchPerformed): ?>
                        <i class="fa-solid fa-search me-2"></i>Search Results
                        <span class="badge bg-info text-dark ms-2"><?php echo count($searchResults); ?> found</span>
                    <?php else: ?>
                        <i class="fa-solid fa-clock me-2"></i>Recent Uploads
                    <?php endif; ?>
                </h5>
                
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle m-0">
                        <thead>
                            <tr class="text-secondary border-secondary">
                                <th>Matric</th>
                                <th>Owner</th>
                                <th>Title / File Name</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Upload Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $displayAssets = $searchPerformed ? $searchResults : $recentAssets;
                            if (empty($displayAssets)): 
                            ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fa-regular fa-folder-open me-2"></i>
                                        <?php echo $searchPerformed ? 'No results found for your search.' : 'No assets found in the database.'; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($displayAssets as $asset): ?>
                                    <tr class="border-secondary">
                                        <td class="fw-bold text-info">
                                            <?php echo htmlspecialchars($asset['matric_number']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($asset['owner_name'] ?? $asset['matric_number']); ?></td>
                                        <td>
                                            <code><?php echo htmlspecialchars($asset['file_name']); ?></code>
                                            <?php if (!empty($asset['title']) && $asset['title'] !== $asset['file_name']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($asset['title']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php 
                                                echo $asset['file_type'] === 'video' ? 'bg-success' : 
                                                    ($asset['file_type'] === 'audio' ? 'bg-warning' : 
                                                    ($asset['file_type'] === 'image' ? 'bg-primary' : 'bg-danger')); 
                                            ?>">
                                                <?php echo strtoupper($asset['file_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($asset['file_size_kb'], 2); ?> KB</td>
                                        <td><?php echo htmlspecialchars($asset['upload_date']); ?></td>
                                        <td>
                                            <?php if (!empty($asset['file_path'])): ?>
                                                <a href="<?php echo htmlspecialchars($asset['file_path']); ?>" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-outline-light">
                                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <footer class="mt-4 pt-3 border-top border-secondary text-muted small text-center">
                <i class="fa-regular fa-copyright me-1"></i>
                MetaSearch — Multimedia Database Project
                <?php if (!empty($group)): ?>
                    | Group <?php echo htmlspecialchars($group); ?>
                <?php endif; ?>
            </footer>
        </div>
    </div>
</div>

<!-- ==============================================================
SCRIPTS
============================================================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Live Clock
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { hour12: false });
        const clockEl = document.getElementById('liveClock');
        if (clockEl) {
            clockEl.textContent = timeString;
        }
    }
    setInterval(updateClock, 1000);
    updateClock();

    console.log('MetaSearch: Index page loaded successfully!');
    console.log('Group: <?php echo htmlspecialchars($group) ?: 'None selected'; ?>');
    console.log('Total Members: <?php echo count($members); ?>');
    console.log('Total Assets: <?php echo $counts['total']; ?>');
    console.log('Database Available: <?php echo $db_available ? 'Yes' : 'No'; ?>');
</script>

</body>
</html>
