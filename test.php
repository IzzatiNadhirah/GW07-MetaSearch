<?php
// ==========================================================================
// test.php - Database Viewer for gw07 Database
// Displays all tables in the gw07 database with pagination support.
// Intended as a dev/debug tool to confirm data looks correct.
// ==========================================================================

require_once 'config/db_connect.php';

// --------------------------------------------------------------------------
// Get list of all tables in gw07 database
// --------------------------------------------------------------------------
$tables = [];
$tableResult = $conn->query("SHOW TABLES");
if ($tableResult) {
    while ($row = $tableResult->fetch_array()) {
        $tables[] = $row[0];
    }
}

// --------------------------------------------------------------------------
// View selection (which table to display)
// --------------------------------------------------------------------------
$selectedTable = isset($_GET['table']) ? $_GET['table'] : (isset($tables[0]) ? $tables[0] : '');
if (!in_array($selectedTable, $tables)) {
    $selectedTable = isset($tables[0]) ? $tables[0] : '';
}

// --------------------------------------------------------------------------
// Pagination setup
// --------------------------------------------------------------------------
$limit = 15; // rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$rows = [];
$total_rows = 0;
$total_pages = 0;
$error_message = null;
$columns = [];

// --------------------------------------------------------------------------
// Get column names for the selected table
// --------------------------------------------------------------------------
if (!empty($selectedTable)) {
    $colResult = $conn->query("SHOW COLUMNS FROM $selectedTable");
    if ($colResult) {
        while ($col = $colResult->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
    }
}

// --------------------------------------------------------------------------
// Fetch data for the selected table with pagination
// --------------------------------------------------------------------------
if (!empty($selectedTable)) {
    try {
        // Get total row count
        $total_result = $conn->query("SELECT COUNT(*) AS total FROM $selectedTable");
        if ($total_result) {
            $total_rows = (int) $total_result->fetch_assoc()['total'];
            $total_pages = $total_rows > 0 ? (int) ceil($total_rows / $limit) : 1;
            if ($page > $total_pages) {
                $page = $total_pages;
                $offset = ($page - 1) * $limit;
            }
        }

        // Fetch rows with pagination
        $sql = "SELECT * FROM $selectedTable LIMIT $limit OFFSET $offset";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    } catch (mysqli_sql_exception $e) {
        $error_message = $e->getMessage();
    }
}

// --------------------------------------------------------------------------
// Helper: build a nav link
// --------------------------------------------------------------------------
function tableLink($tableName, $currentTable) {
    $activeStyle = ($tableName === $currentTable) ? " style='font-weight:bold; text-decoration:underline; color:#00d2ff;'" : "";
    return "<a href='?table=" . urlencode($tableName) . "'" . $activeStyle . ">" . htmlspecialchars($tableName) . "</a>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MetaSearch — Database Viewer (test.php)</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #0f0f0f;
            color: #e0e0e0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            font-size: 2rem;
            margin-bottom: 5px;
        }
        .subtitle {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        .nav-bar {
            background: #161616;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        .nav-bar a {
            color: #888;
            text-decoration: none;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .nav-bar a:hover {
            background: rgba(0, 210, 255, 0.1);
            color: #fff;
        }
        .nav-bar .nav-label {
            color: #555;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-right: 5px;
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
        .status-item .value.count { color: #00d2ff; }

        .table-container {
            overflow-x: auto;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            background: #111;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            min-width: 600px;
        }
        th {
            background: #0a0a0a;
            color: #00d2ff;
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.65rem;
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
            word-break: break-word;
            max-width: 300px;
        }
        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }
        tr:last-child td {
            border-bottom: none;
        }

        .pagination {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            margin-top: 20px;
            justify-content: center;
        }
        .pagination a, .pagination span {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 4px;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.2s;
        }
        .pagination a {
            background: #1a1a1a;
            color: #888;
            border: 1px solid #2a2a2a;
        }
        .pagination a:hover {
            background: rgba(0, 210, 255, 0.1);
            color: #fff;
            border-color: #00d2ff;
        }
        .pagination .active {
            background: #00d2ff;
            color: #0f0f0f;
            font-weight: 600;
            border: 1px solid #00d2ff;
        }
        .pagination .disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

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

        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.6rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-yes { background: #1a3a1a; color: #4caf50; }
        .badge-no { background: #3a1a1a; color: #ff6b6b; }
        .badge-info { background: #1a2a3a; color: #4fc3f7; }

        .footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #2a2a2a;
            color: #666;
            font-size: 0.8rem;
            text-align: center;
        }

        .db-info {
            background: #1a2a14;
            border: 1px solid #4caf50;
            color: #8bc34a;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .db-info code {
            background: #0f0f0f;
            padding: 2px 8px;
            border-radius: 4px;
            color: #4fc3f7;
        }

        .table-info {
            color: #888;
            font-size: 0.8rem;
            margin-bottom: 15px;
        }
        .table-info strong {
            color: #00d2ff;
        }

        .column-tag {
            display: inline-block;
            background: #1a1a1a;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.65rem;
            color: #888;
            border: 1px solid #2a2a2a;
            margin: 2px;
        }

        @media (max-width: 768px) {
            body { padding: 15px; }
            .container { padding: 15px; }
            .status-box { flex-direction: column; gap: 10px; }
            th, td { padding: 6px 8px; font-size: 0.7rem; }
            .nav-bar { flex-direction: column; align-items: stretch; }
            .nav-bar .nav-label { display: block; margin-bottom: 5px; }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>📊 gw07 Database Viewer</h1>
    <div class="subtitle">View all tables in the <strong>gw07</strong> database with pagination</div>

    <!-- Database Info -->
    <div class="db-info">
        <i class="fa-solid fa-database me-2"></i>
        Database: <code>gw07</code> | 
        Host: <code>localhost</code> |
        Tables: <strong><?php echo count($tables); ?></strong>
    </div>

    <!-- Navigation Bar -->
    <div class="nav-bar">
        <span class="nav-label">Tables:</span>
        <?php foreach ($tables as $table): ?>
            <?php echo tableLink($table, $selectedTable); ?>
        <?php endforeach; ?>
    </div>

    <!-- Status Box -->
    <div class="status-box">
        <div class="status-item">
            <span class="label">Selected Table</span>
            <span class="value success"><?php echo htmlspecialchars($selectedTable ?: 'None'); ?></span>
        </div>
        <div class="status-item">
            <span class="label">Total Rows</span>
            <span class="value count"><?php echo $total_rows; ?></span>
        </div>
        <div class="status-item">
            <span class="label">Columns</span>
            <span class="value"><?php echo count($columns); ?></span>
        </div>
        <div class="status-item">
            <span class="label">Page</span>
            <span class="value"><?php echo $page; ?> / <?php echo max(1, $total_pages); ?></span>
        </div>
    </div>

    <!-- Error Display -->
    <?php if ($error_message): ?>
        <div class="error-box">
            <strong>❌ Error</strong>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Column Information -->
    <?php if (!empty($columns)): ?>
        <div class="table-info">
            <strong>Columns:</strong>
            <?php foreach ($columns as $col): ?>
                <span class="column-tag"><?php echo htmlspecialchars($col); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Results Table -->
    <?php if (!empty($selectedTable)): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <?php foreach ($columns as $col): ?>
                            <th><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="<?php echo count($columns) + 1; ?>" style="text-align:center;padding:40px;color:#888;">
                                <span style="font-size:2rem;display:block;margin-bottom:10px;">📭</span>
                                No data found in this table.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $index => $row): ?>
                            <tr>
                                <td style="color:#555;font-weight:700;font-size:0.75rem;"><?php echo $offset + $index + 1; ?></td>
                                <?php foreach ($columns as $col): ?>
                                    <td>
                                        <?php 
                                        $value = $row[$col] ?? null;
                                        if ($value === null || $value === '') {
                                            echo '<span style="color:#555;">NULL</span>';
                                        } elseif ($col === 'password') {
                                            echo '<span style="color:#555;font-family:monospace;font-size:0.7rem;">' . htmlspecialchars(substr($value, 0, 20)) . '...</span>';
                                        } elseif (strpos($col, 'date') !== false && !empty($value)) {
                                            echo '<span style="color:#4fc3f7;">' . htmlspecialchars($value) . '</span>';
                                        } elseif (strpos($col, 'file_path') !== false && !empty($value)) {
                                            echo '<a href="' . htmlspecialchars($value) . '" target="_blank" style="color:#4fc3f7;font-size:0.7rem;">View</a>';
                                        } elseif (strpos($col, 'photo') !== false || strpos($col, 'doc') !== false || strpos($col, 'audio') !== false || strpos($col, 'video') !== false) {
                                            if (!empty($value)) {
                                                echo '<span class="badge badge-yes">✅ Uploaded</span>';
                                            } else {
                                                echo '<span class="badge badge-no">❌ Empty</span>';
                                            }
                                        } elseif ($col === 'is_searchable') {
                                            echo $value ? '<span class="badge badge-yes">Yes</span>' : '<span class="badge badge-no">No</span>';
                                        } elseif (strlen($value) > 100) {
                                            echo htmlspecialchars(substr($value, 0, 100)) . '...';
                                        } else {
                                            echo htmlspecialchars($value);
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?table=<?php echo urlencode($selectedTable); ?>&page=1">« First</a>
                    <a href="?table=<?php echo urlencode($selectedTable); ?>&page=<?php echo $page - 1; ?>">‹ Previous</a>
                <?php else: ?>
                    <span class="disabled">« First</span>
                    <span class="disabled">‹ Previous</span>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($total_pages, $page + 2);
                if ($startPage > 1) {
                    echo '<span>...</span>';
                }
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?table=<?php echo urlencode($selectedTable); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($endPage < $total_pages): ?>
                    <span>...</span>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?table=<?php echo urlencode($selectedTable); ?>&page=<?php echo $page + 1; ?>">Next ›</a>
                    <a href="?table=<?php echo urlencode($selectedTable); ?>&page=<?php echo $total_pages; ?>">Last »</a>
                <?php else: ?>
                    <span class="disabled">Next ›</span>
                    <span class="disabled">Last »</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="no-data">
            <span>📭</span>
            <h3>No Table Selected</h3>
            <p>Please select a table from the navigation bar above.</p>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer">
        <strong>test.php</strong> — Database Viewer for gw07
        <br>
        Created: <?php echo date('Y-m-d H:i:s'); ?>
        <br>
        <span style="color:#555;">Total tables: <?php echo count($tables); ?> | Total rows displayed: <?php echo count($rows); ?></span>
    </div>
</div>

</body>
</html>
