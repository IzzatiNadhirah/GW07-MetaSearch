<?php
// ==========================================================================
// test_vstu.php
// Standalone database tester for mmdb2026.vstu table.
// This file is independent and does NOT use the project's db_connect.php.
// ==========================================================================

// ==========================================================================
// DATABASE CONFIGURATION (Direct connection to mmdb2026)
// ==========================================================================
define('DB_SERVER', 'localhost'); // bitp3353.utem.edu.my (Server Madam)
define('DB_USERNAME', 'gw07');
define('DB_PASSWORD', 'password');
define('DB_NAME', 'mmdb2026');

// ==========================================================================
// ESTABLISH CONNECTION
// ==========================================================================
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = null;
$error_message = null;
$rows = [];
$total_rows = 0;
$query_time = 0;
$columns = [];
$sql = "SELECT * FROM mmdb2026.vstu ORDER BY full_name ASC"; // ✅ FIXED: Added database prefix

try {
    // Connect to the database
    $start_time = microtime(true);
    
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    $conn->set_charset("utf8mb4");
    
    $connect_time = microtime(true) - $start_time;

    // --------------------------------------------------------------------------
    // Test Query: SELECT * FROM mmdb2026.vstu
    // --------------------------------------------------------------------------
    $query_start = microtime(true);
    $result = $conn->query($sql);
    $query_time = microtime(true) - $query_start;
    
    if ($result) {
        $total_rows = $result->num_rows;
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
    }

} catch (mysqli_sql_exception $e) {
    $error_message = "Database Error: " . $e->getMessage();
} catch (Exception $e) {
    $error_message = "General Error: " . $e->getMessage();
} finally {
    if ($conn) {
        $conn->close();
    }
}

// Get column names if rows exist
if (!empty($rows)) {
    $columns = array_keys($rows[0]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test vstu Table - mmdb2026</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0f0f0f;
            color: #e0e0e0;
            padding: 30px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #1a1a1a;
            border-radius: 12px;
            padding: 30px;
            border: 1px solid #2a2a2a;
        }
        h1 {
            color: #00d2ff;
            font-weight: 300;
            margin-bottom: 5px;
            font-size: 2rem;
        }
        .subtitle {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        .status-box {
            background: #161616;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        .status-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-item .label {
            color: #888;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-item .value {
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
        }
        .status-item .value.success { color: #4caf50; }
        .status-item .value.error { color: #ff6b6b; }
        .status-item .value.warning { color: #ffd93d; }

        .error-box {
            background: #2a1414;
            border: 1px solid #5c1f1f;
            color: #ff6b6b;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .error-box strong {
            display: block;
            margin-bottom: 5px;
        }
        .error-box ul {
            margin-left: 20px;
            margin-top: 5px;
            color: #ff8a8a;
        }

        .table-container {
            overflow-x: auto;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            background: #111;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            min-width: 800px;
        }
        th {
            background: #0a0a0a;
            color: #00d2ff;
            padding: 12px 15px;
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
            padding: 10px 15px;
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

        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-yes { background: #1a3a1a; color: #4caf50; }
        .badge-no { background: #3a1a1a; color: #ff6b6b; }
        .badge-info { background: #1a2a3a; color: #4fc3f7; }

        .count {
            color: #00d2ff;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #2a2a2a;
            color: #666;
            font-size: 0.8rem;
            text-align: center;
        }

        .query-info {
            color: #888;
            font-size: 0.8rem;
            font-family: monospace;
        }

        .col-summary {
            margin-top: 20px;
            padding: 15px;
            background: #111;
            border-radius: 8px;
            border: 1px solid #222;
        }
        .col-summary h4 {
            color: #888;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .col-tag {
            background: #1a1a1a;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.75rem;
            color: #ccc;
            border: 1px solid #2a2a2a;
            display: inline-block;
            margin: 2px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #888;
        }
        .no-data span {
            font-size: 3rem;
            display: block;
            margin-bottom: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 15px; }
            .container { padding: 15px; }
            .status-box { flex-direction: column; gap: 10px; }
            th, td { padding: 8px 10px; font-size: 0.75rem; }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <h1>📊 vstu Table Test</h1>
    <div class="subtitle">Testing SELECT * FROM <strong>mmdb2026.vstu</strong></div>

    <!-- Status Box -->
    <div class="status-box">
        <div class="status-item">
            <span class="label">Connection</span>
            <span class="value <?php echo $error_message ? 'error' : 'success'; ?>">
                <?php echo $error_message ? 'FAILED' : 'CONNECTED'; ?>
            </span>
        </div>
        <div class="status-item">
            <span class="label">Server</span>
            <span class="value"><?php echo DB_SERVER; ?></span>
        </div>
        <div class="status-item">
            <span class="label">Database</span>
            <span class="value"><?php echo DB_NAME; ?></span>
        </div>
        <div class="status-item">
            <span class="label">Total Rows</span>
            <span class="value count"><?php echo $total_rows; ?></span>
        </div>
        <div class="status-item">
            <span class="label">Query Time</span>
            <span class="value"><?php echo number_format($query_time, 4); ?>s</span>
        </div>
    </div>

    <!-- Error Display -->
    <?php if ($error_message): ?>
        <div class="error-box">
            <strong>❌ Connection Error</strong>
            <?php echo htmlspecialchars($error_message); ?>
            <br><br>
            <strong>Possible causes:</strong>
            <ul>
                <li>Server is not accessible (check network/VPN)</li>
                <li>Username or password is incorrect</li>
                <li>Database name is incorrect</li>
                <li>MySQL server is not running</li>
                <li>Firewall is blocking the connection</li>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Query Info -->
    <div class="query-info" style="margin-bottom:15px;">
        <strong>Query:</strong> <code><?php echo htmlspecialchars($sql); ?></code>
    </div>

    <!-- Results Table -->
    <?php if ($rows && count($rows) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <?php 
                        $columns = array_keys($rows[0]);
                        foreach ($columns as $col): 
                        ?>
                            <th><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($row as $key => $value): ?>
                                <td>
                                    <?php 
                                    if ($value === null) {
                                        echo '<span style="color:#555;">NULL</span>';
                                    } elseif ($key === 'password') {
                                        echo '<span style="color:#555;font-family:monospace;font-size:0.7rem;">' . htmlspecialchars(substr($value, 0, 20)) . '...</span>';
                                    } elseif ($key === 'photoStu' || $key === 'docStu' || $key === 'audioStu' || $key === 'videoStu') {
                                        if (!empty($value)) {
                                            echo '<span class="badge badge-yes">✅ Uploaded</span>';
                                        } else {
                                            echo '<span class="badge badge-no">❌ Empty</span>';
                                        }
                                    } elseif ($key === 'life_motto' && !empty($value)) {
                                        echo '<span style="color:#ffd93d;font-style:italic;">"' . htmlspecialchars($value) . '"</span>';
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
    <?php elseif (!$error_message): ?>
        <div class="no-data">
            <span>📭</span>
            <h3>No Data Found</h3>
            <p>The vstu table exists but contains no records.</p>
        </div>
    <?php endif; ?>

    <!-- Column Summary -->
    <?php if ($rows && count($rows) > 0): ?>
        <div class="col-summary">
            <h4>📋 Column Information (<?php echo count($columns); ?> columns)</h4>
            <div style="display:flex;flex-wrap:wrap;gap:5px;">
                <?php foreach ($columns as $col): ?>
                    <span class="col-tag"><?php echo htmlspecialchars($col); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer">
        <strong>test_vstu.php</strong> — Standalone database tester for mmdb2026.vstu
        <br>
        Created: <?php echo date('Y-m-d H:i:s'); ?>
    </div>
</div>

</body>
</html>
