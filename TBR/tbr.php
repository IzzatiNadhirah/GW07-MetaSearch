<?php
// ==========================================================================
// tbr.php
// Text-Based Retrieval (TBR) — Now searches gw07.vstu table only.
// Searches: full_name, matric_no, group_no, life_motto
// ==========================================================================

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../config/db_queries.php';

$conn = isset($GLOBALS['conn']) ? $GLOBALS['conn'] : null;

if ($conn === null || !$conn->ping()) {
    $conn_error = "Database connection is not available.";
} else {
    $conn_error = null;
}

$search_performed = false;
$error_message = null;
$results = [];
$query_term = isset($_GET['q']) ? trim($_GET['q']) : '';

// --------------------------------------------------------------------------
// Suggested tags: pull distinct groups from vstu
// --------------------------------------------------------------------------
$suggestedTags = getSuggestedTags($conn);

// --------------------------------------------------------------------------
// Main search
// --------------------------------------------------------------------------
if (isset($_GET['search'])) {
    $search_performed = true;

    if ($query_term === '') {
        $error_message = "Please enter a keyword, tag, or name to search for.";
    } elseif ($conn_error !== null) {
        $error_message = $conn_error;
    } else {
        try {
            $results = tbrSearch($conn, $query_term);
            if (empty($results)) {
                $error_message = "No results found for your search.";
            }
        } catch (mysqli_sql_exception $e) {
            $error_message = "Search failed: " . $e->getMessage();
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Text-Based Retrieval | MetaSearch</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Ensure all text is white on dark background */
        body, .main-content, .container {
            color: #ffffff !important;
        }
        .header h1 {
            color: #00d2ff !important;
        }
        .header .subtitle {
            color: #c0c0c0 !important;
        }
        .panel {
            background: var(--bg-panel);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .form-group label {
            color: #ffffff !important;
        }
        .form-group input {
            color: #ffffff !important;
            background-color: #1a1a1a !important;
            border: 1px solid #2a2a2a !important;
            border-radius: 6px;
            padding: 10px 12px;
            width: 100%;
        }
        .form-group input:focus {
            border-color: #00d2ff !important;
            outline: none;
            box-shadow: 0 0 0 0.25rem rgba(0, 210, 255, 0.25);
        }
        .form-group input::placeholder {
            color: #888888 !important;
        }
        .hint {
            color: #c0c0c0 !important;
        }
        .hint strong {
            color: #ffffff !important;
        }
        .operator-badge {
            display: inline-block;
            background: var(--accent);
            color: var(--bg-primary);
            padding: 1px 10px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.7rem;
        }
        .features-section h3 {
            color: #ffffff !important;
        }
        .tag-chip {
            display: inline-block;
            background: rgba(0, 210, 255, 0.1);
            color: #00d2ff !important;
            border: 1px solid rgba(0, 210, 255, 0.3);
            padding: 4px 12px;
            border-radius: 14px;
            font-size: 0.78rem;
            margin: 3px 4px 3px 0;
            text-decoration: none;
            cursor: pointer;
            transition: 0.2s;
        }
        .tag-chip:hover {
            background: rgba(0, 210, 255, 0.2);
            color: #ffffff !important;
        }
        .error-banner {
            background: var(--error-bg, #2a1414);
            border: 1px solid var(--error-border, #5c1f1f);
            color: #ff6b6b;
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 18px;
            background: var(--bg-panel);
            border-radius: 6px;
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .results-count {
            font-size: 0.95rem;
            color: #c0c0c0 !important;
        }
        .results-count strong {
            color: #00d2ff !important;
        }
        .results-info {
            color: #c0c0c0 !important;
            font-size: 0.85rem;
        }
        .results-info span {
            color: #00d2ff !important;
        }
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--bg-panel);
            font-size: 0.9rem;
        }
        th {
            background: var(--bg-primary);
            color: #00d2ff !important;
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
        }
        td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            color: #ffffff !important;
        }
        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }
        tr:last-child td {
            border-bottom: none;
        }
        .btn-reset {
            display: inline-block;
            padding: 10px 20px;
            background: transparent;
            color: #c0c0c0 !important;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-reset:hover {
            border-color: #c0c0c0;
            color: #ffffff !important;
        }
        .btn-primary {
            background: var(--accent);
            color: var(--bg-primary);
            padding: 10px 35px;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            min-width: 120px;
        }
        .btn-primary:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
        }
        .btn-back {
            display: inline-block;
            padding: 12px 28px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            background: var(--border-color);
            color: #c0c0c0 !important;
            transition: 0.2s;
            margin-top: 30px;
            font-size: 0.95rem;
        }
        .btn-back:hover {
            background: #3a3a3a;
            color: #ffffff !important;
        }
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #c0c0c0 !important;
        }
        .no-results .icon {
            font-size: 3rem;
            display: block;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        .no-results h2 {
            color: #ffffff !important;
            font-weight: 300;
            margin-bottom: 8px;
        }
        .no-results p {
            color: #888888 !important;
            font-size: 0.9rem;
        }
        .text-muted {
            color: #c0c0c0 !important;
        }
        .search-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .fade-in {
            animation: fadeIn 0.4s ease-in-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h2>MetaSearch</h2>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="../ABR/abr_search.html">ABR</a></li>
            <li><a href="tbr.php" class="active">TBR</a></li>
            <li><a href="../CBR/cbr_search.php">CBR</a></li>
        </ul>
    </aside>

    <main class="main-content fade-in">
    <div class="container">
        <div class="header">
            <h1>Text-Based Retrieval (TBR)</h1>
            <div class="subtitle">Search students by name, matric number, group, or motto</div>
        </div>

        <div class="panel">
            <form method="GET" class="search-form">
                <div class="form-group" style="flex: 3;">
                    <label for="q">Search Term</label>
                    <input type="text" id="q" name="q" placeholder="e.g. student name, matric number, group..."
                           value="<?php echo htmlspecialchars($query_term); ?>" required>
                </div>
                <div class="search-actions">
                    <button type="submit" name="search" class="btn btn-primary">Search</button>
                    <a href="tbr.php" class="btn-reset">Reset</a>
                </div>
            </form>

            <div class="hint">
                <strong>Note:</strong> Searches against full_name, matric_no, group_no, and life_motto.
                <span class="operator-badge">LIKE</span>
            </div>

            <?php if (!empty($suggestedTags)): ?>
                <div class="features-section">
                    <h3>Suggested Groups</h3>
                    <div>
                        <?php foreach ($suggestedTags as $tag): ?>
                            <a class="tag-chip" href="tbr.php?search=1&q=<?php echo urlencode($tag); ?>">#<?php echo htmlspecialchars($tag); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($search_performed && $error_message): ?>
            <div class="error-banner">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($search_performed && !$error_message): ?>
            <div class="results-section">
                <div class="results-header">
                    <span class="results-count">
                        Found <strong><?php echo count($results); ?></strong> result(s)
                    </span>
                    <span class="results-info">
                        Query: <span>"<?php echo htmlspecialchars($query_term); ?>"</span>
                    </span>
                </div>

                <?php if (!empty($results)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Matric No</th>
                                    <th>Full Name</th>
                                    <th>Group</th>
                                    <th>Motto</th>
                                    <th>Photo</th>
                                    <th>Doc</th>
                                    <th>Audio</th>
                                    <th>Video</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $row): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['matric_no']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['group_no'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['life_motto'] ?? '-'); ?></td>
                                        <td>
                                            <?php if (!empty($row['photoStu'])): ?>
                                                <a href="<?php echo htmlspecialchars($row['photoStu']); ?>" target="_blank" class="btn-reset">View</a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['docStu'])): ?>
                                                <a href="<?php echo htmlspecialchars($row['docStu']); ?>" target="_blank" class="btn-reset">View</a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['audioStu'])): ?>
                                                <a href="<?php echo htmlspecialchars($row['audioStu']); ?>" target="_blank" class="btn-reset">Play</a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['videoStu'])): ?>
                                                <a href="<?php echo htmlspecialchars($row['videoStu']); ?>" target="_blank" class="btn-reset">Play</a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <span class="icon">🔍</span>
                        <h2>No Results Found</h2>
                        <p>Try a different keyword, or check the suggested groups above.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <a href="../index.php" class="btn-back">← Back to Home</a>
    </div>
    </main>

</body>
</html>
