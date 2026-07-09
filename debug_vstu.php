<?php
// ==========================================================================
// debug_vstu.php - Temporary Debug File
// Displays all rows from mmdb2026.vstu table for debugging purposes.
// DELETE THIS FILE AFTER DEBUGGING!
// ==========================================================================

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once __DIR__ . '/config/db_connect.php';

// Get connection from GLOBALS array
$conn = isset($GLOBALS['conn']) ? $GLOBALS['conn'] : null;

// Check if connection exists and is valid
if ($conn === null || !$conn->ping()) {
    die("<h2 style='color:red;'>❌ Database connection failed!</h2>
         <p>Please check your database configuration in config/db_connect.php</p>");
}

// --------------------------------------------------------------------------
// QUERY 1: Get all students from vstu
// --------------------------------------------------------------------------
$sql = "SELECT * FROM mmdb2026.vstu ORDER BY full_name ASC";
$result = $conn->query($sql);

$totalRows = 0;
$students = [];
if ($result) {
    $totalRows = $result->num_rows;
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $result->free();
}

// --------------------------------------------------------------------------
// QUERY 2: Get column information
// --------------------------------------------------------------------------
$columns = [];
$colResult = $conn->query("SHOW COLUMNS FROM mmdb2026.vstu");
if ($colResult) {
    while ($row = $colResult->fetch_assoc()) {
        $columns[] = $row;
    }
    $colResult->free();
}

// --------------------------------------------------------------------------
// QUERY 3: Get group statistics
// --------------------------------------------------------------------------
$groupStats = [];
$groupResult = $conn->query("SELECT group_no, COUNT(*) as count FROM mmdb2026.vstu WHERE group_no IS NOT NULL AND group_no != '' GROUP BY group_no ORDER BY group_no");
if ($groupResult) {
    while ($row = $groupResult->fetch_assoc()) {
        $groupStats[] = $row;
    }
    $groupResult->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - vstu Table Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #0f0f0f;
            color: #e0e0e0;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        h1 {
            color: #00d2ff;
            font-weight: 300;
            border-bottom: 2px solid #2a2a2a;
            padding-bottom: 15px;
        }
        .warning-box {
            background: #2a1414;
            border: 1px solid #5c1f1f;
            color: #ff6b6b;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info-box {
            background: #1a2a14;
            border: 1px solid #4caf50;
            color: #8bc34a;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .stat-box {
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            padding: 15px 20px;
            display: inline-block;
            margin: 5px;
            min-width: 120px;
            text-align: center;
        }
        .stat-box .number {
            font-size: 2rem;
            font-weight: 700;
            color: #00d2ff;
        }
        .stat-box .label {
            color: #888;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        .table-container {
            overflow-x: auto;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            background: #111;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        th {
            background: #0a0a0a;
            color: #00d2ff;
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #2a2a2a;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        td {
            padding: 8px 12px;
            border-bottom: 1px solid #222;
            color: #ccc;
            vertical-align: middle;
        }
        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }
        tr:last-child td {
            border-bottom: none;
        }
        .badge-media {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.6rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-yes { background: #1a3a1a; color: #4caf50; }
        .badge-no { background: #3a1a1a; color: #ff6b6b; }
        .badge-info { background: #1a2a3a; color: #4fc3f7; }
        .badge-warning { background: #3a3a1a; color: #ffd93d; }
        .debug-query {
            background: #0a0a0a;
            border: 1px solid #1a2a3a;
            border-radius: 6px;
            padding: 10px 15px;
            font-family: monospace;
            font-size: 0.85rem;
            color: #4fc3f7;
            margin: 10px 0;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #2a2a2a;
            color: #666;
            font-size: 0.8rem;
            text-align: center;
        }
        .section-title {
            color: #00d2ff;
            margin-top: 25px;
            margin-bottom: 10px;
            font-size: 1.2rem;
            font-weight: 600;
        }
        .column-tag {
            display: inline-block;
            background: #1a1a1a;
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 0.7rem;
            color: #ccc;
            border: 1px solid #2a2a2a;
            margin: 2px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>
        <i class="fa-solid fa-bug me-2"></i>Debug: vstu Table Viewer
        <small style="color:#888;font-size:0.6rem;font-weight:300;">(Temporary - DELETE AFTER DEBUGGING)</small>
    </h1>

    <!-- Warning Box -->
    <div class="warning-box">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <strong>WARNING:</strong> This is a temporary debug file. 
        <strong>DELETE <code>debug_vstu.php</code> after debugging!</strong>
    </div>

    <!-- Connection Status -->
    <div class="info-box">
        <i class="fa-solid fa-check-circle me-2"></i>
        <strong>✅ Database Connection:</strong> Connected to <code>mmdb2026</code> successfully!
    </div>

    <!-- Statistics -->
    <div style="margin: 20px 0; display: flex; flex-wrap: wrap; gap: 10px;">
        <div class="stat-box">
            <div class="number"><?php echo $totalRows; ?></div>
            <div class="label">Total Students</div>
        </div>
        <div class="stat-box">
            <div class="number"><?php echo count($groupStats); ?></div>
            <div class="label">Total Groups</div>
        </div>
        <div class="stat-box">
            <div class="number"><?php echo count($columns); ?></div>
            <div class="label">Total Columns</div>
        </div>
    </div>

    <!-- Group Statistics -->
    <?php if (!empty($groupStats)): ?>
        <div class="section-title"><i class="fa-solid fa-layer-group me-2"></i>Group Statistics</div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
            <?php foreach ($groupStats as $group): ?>
                <div class="stat-box" style="min-width:80px;">
                    <div class="number" style="font-size:1.2rem;"><?php echo htmlspecialchars($group['count']); ?></div>
                    <div class="label"><?php echo htmlspecialchars($group['group_no']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Column Information -->
    <div class="section-title"><i class="fa-solid fa-table-columns me-2"></i>Column Information</div>
    <div style="margin-bottom:20px;display:flex;flex-wrap:wrap;gap:5px;">
        <?php foreach ($columns as $col): ?>
            <span class="column-tag">
                <?php echo htmlspecialchars($col['Field']); ?>
                <span style="color:#555;font-size:0.6rem;">(<?php echo htmlspecialchars($col['Type']); ?>)</span>
            </span>
        <?php endforeach; ?>
    </div>

    <!-- Query Used -->
    <div class="section-title"><i class="fa-solid fa-code me-2"></i>Query Used</div>
    <div class="debug-query"><?php echo htmlspecialchars($sql); ?></div>

    <!-- Results Table -->
    <div class="section-title">
        <i class="fa-solid fa-table me-2"></i>All Rows in vstu (<?php echo $totalRows; ?> rows)
    </div>

    <?php if ($totalRows > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <?php 
                        $columnNames = array_keys($students[0]);
                        foreach ($columnNames as $col): 
                        ?>
                            <th><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $index => $row): ?>
                        <tr>
                            <td><strong><?php echo $index + 1; ?></strong></td>
                            <?php foreach ($row as $key => $value): ?>
                                <td>
                                    <?php 
                                    if ($value === null || $value === '') {
                                        echo '<span style="color:#555;">NULL</span>';
                                    } elseif ($key === 'password') {
                                        echo '<span style="color:#555;font-family:monospace;font-size:0.7rem;">' . htmlspecialchars(substr($value, 0, 20)) . '...</span>';
                                    } elseif ($key === 'photoStu' || $key === 'docStu' || $key === 'audioStu' || $key === 'videoStu') {
                                        if (!empty($value)) {
                                            echo '<span class="badge-media badge-yes">✅ <a href="' . htmlspecialchars($value) . '" target="_blank" style="color:#4caf50;text-decoration:none;">View</a></span>';
                                        } else {
                                            echo '<span class="badge-media badge-no">❌ Empty</span>';
                                        }
                                    } elseif ($key === 'life_motto' && !empty($value)) {
                                        echo '<span style="color:#ffd93d;font-style:italic;">"' . htmlspecialchars($value) . '"</span>';
                                    } elseif (strpos($key, '_date') !== false && !empty($value)) {
                                        echo '<span style="color:#4fc3f7;">' . htmlspecialchars($value) . '</span>';
                                    } else {
                                        echo htmlspecialchars($value);
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align:center;padding:40px;color:#888;background:#1a1a1a;border-radius:8px;border:1px solid #2a2a2a;">
            <span style="font-size:3rem;display:block;margin-bottom:10px;">📭</span>
            <h3>No Data Found</h3>
            <p>The vstu table exists but contains no records.</p>
            <p style="font-size:0.8rem;color:#555;">Run the sync engine or insert data manually.</p>
        </div>
    <?php endif; ?>

    <!-- PHP Info -->
    <div class="section-title"><i class="fa-solid fa-info-circle me-2"></i>PHP Configuration</div>
    <div style="background:#1a1a1a;border:1px solid #2a2a2a;border-radius:8px;padding:15px;">
        <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
        <p><strong>MySQL Version:</strong> <?php echo $conn->server_info; ?></p>
        <p><strong>Database:</strong> <?php echo DB_NAME; ?></p>
        <p><strong>Host:</strong> <?php echo DB_SERVER; ?></p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <strong>debug_vstu.php</strong> — Temporary debug file for mmdb2026.vstu
        <br>
        Created: <?php echo date('Y-m-d H:i:s'); ?>
        <br>
        <span style="color:#ff6b6b;">
            <i class="fa-solid fa-trash me-1"></i>
            DELETE THIS FILE AFTER DEBUGGING!
        </span>
    </div>
</div>

</body>
</html>
