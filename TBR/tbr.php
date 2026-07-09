<?php
// ==========================================================================
// tbr.php
// Text-Based Retrieval (TBR) — Now searches mmdb2026.vstu table only.
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
