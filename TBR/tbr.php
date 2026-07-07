<?php
// ==========================================================================
// tbr.php
// Text-Based Retrieval (TBR) — per proposal section 3B:
// Searches multimedia content using textual information:
// File Title, Keywords, Tags, Captions, Descriptions.
// ==========================================================================

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../config/db_queries.php';

// ✅ FIXED: Get connection from GLOBALS array
$conn = isset($GLOBALS['conn']) ? $GLOBALS['conn'] : null;

// ✅ FIXED: Check if connection exists and is valid
if ($conn === null || !$conn->ping()) {
    $conn_error = "Database connection is not available.";
} else {
    $conn_error = null;
}

$search_performed = false;
$error_message = null;
$result = null;
$query_term = isset($_GET['q']) ? trim($_GET['q']) : '';

// --------------------------------------------------------------------------
// Suggested tags: pull a handful of distinct tags from text_metadata so users
// have something to click instead of guessing keywords cold.
// --------------------------------------------------------------------------
$suggestedTags = getSuggestedTags($conn);

// --------------------------------------------------------------------------
// Main search
// --------------------------------------------------------------------------
if (isset($_GET['search'])) {
    $search_performed = true;

    if ($query_term === '') {
        $error_message = "Please enter a keyword, tag, or title to search for.";
    } elseif ($conn_error !== null) {
        $error_message = $conn_error;
    } else {
        try {
            // Execute TBR search using centralized function
            $result = tbrSearch($conn, $query_term);
            
            if ($result === false) {
                $error_message = "Failed to execute search query.";
            }
        } catch (mysqli_sql_exception $e) {
            // MATCH AGAINST can throw for very short search terms depending on
            // ft_min_word_len — surface this clearly instead of a raw fatal error.
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

    <!-- ==============================================================
    SIDEBAR (Unified Navigation)
    ============================================================== -->
    <aside class="sidebar">
        <h2>MetaSearch</h2>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="../ABR/abr_search.html">ABR</a></li>
            <li><a href="tbr.php" class="active">TBR</a></li>
            <li><a href="../CBR/cbr_search.php">CBR</a></li>
        </ul>
    </aside>

    <!-- ==============================================================
    MAIN CONTENT
    ============================================================== -->
    <main class="main-content fade-in">
    <div class="container">
        <div class="header">
            <h1>Text-Based Retrieval (TBR)</h1>
            <div class="subtitle">Search multimedia files by keywords, tags, title, or description</div>
        </div>

        <div class="panel">
            <form method="GET" class="search-form">
                <div class="form-group" style="flex: 3;">
                    <label for="q">Keyword / Tag / Title</label>
                    <input type="text" id="q" name="q" placeholder="e.g. sunset, lecture notes, product demo..."
                           value="<?php echo htmlspecialchars($query_term); ?>" required>
                </div>
                <div class="search-actions">
                    <button type="submit" name="search" class="btn btn-primary">Search</button>
                    <a href="tbr.php" class="btn-reset">Reset</a>
                </div>
            </form>

            <div class="hint">
                <strong>Note:</strong> Matches against title, keywords, tags, captions, and descriptions.
                <span class="operator-badge">MATCH / LIKE</span>
            </div>

            <?php if (!empty($suggestedTags)): ?>
                <div class="features-section">
                    <h3>Suggested Tags</h3>
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
                        Found <strong><?php echo $result ? mysqli_num_rows($result) : 0; ?></strong> result(s)
                    </span>
                    <span class="results-info">
                        Query: <span>"<?php echo htmlspecialchars($query_term); ?>"</span>
                    </span>
                </div>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>File Name</th>
                                    <th>Type</th>
                                    <th>Owner</th>
                                    <th>Tags</th>
                                    <th>Keywords</th>
                                    <th>Upload Date</th>
                                    <th>Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                                        <td><span class="badge badge-yes"><?php echo htmlspecialchars(strtoupper($row['file_type'])); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['full_name'] ?? 'Unknown'); ?></td>
                                        <td><?php echo htmlspecialchars($row['tags'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['keywords'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['upload_date']); ?></td>
                                        <td><a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn-reset">Open</a></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <span class="icon">🔍</span>
                        <h2>No Results Found</h2>
                        <p>Try a different keyword, or check the suggested tags above.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <a href="../index.php" class="btn-back">← Back to Home</a>
    </div>
    </main>

</body>
</html>
