<?php
// ==========================================================================
// tbr.php
// Text-Based Retrieval (TBR) — per proposal section 3B:
// Searches multimedia content using textual information:
// File Title, Keywords, Tags, Captions, Descriptions.
// ==========================================================================

require_once __DIR__ . '/../config/db_connect.php';

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
$suggestedTags = [];
try {
    if ($conn !== null) {
        $tagResult = $conn->query("SELECT tags FROM text_metadata WHERE tags IS NOT NULL AND tags <> '' LIMIT 50");
        if ($tagResult) {
            $seen = [];
            while ($row = $tagResult->fetch_assoc()) {
                // tags column stores comma-separated values (e.g. "nature,travel,sunset")
                foreach (explode(',', $row['tags']) as $tag) {
                    $tag = trim($tag);
                    if ($tag !== '' && !isset($seen[$tag])) {
                        $seen[$tag] = true;
                        $suggestedTags[] = $tag;
                    }
                    if (count($suggestedTags) >= 8) break 2;
                }
            }
        }
    }
} catch (mysqli_sql_exception $e) {
    // Non-critical — suggestions are a convenience feature, so fail quietly here
    // and just show no suggestions rather than breaking the whole page.
    $suggestedTags = [];
}

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
            // ✅ FIXED: Define $sql outside the conditional blocks
            $sql = "";
            
            // Combines two retrieval techniques over the text metadata:
            //  - FULLTEXT MATCH AGAINST on keywords/tags/description (uses the
            //    idx_tbr_search index defined in the schema)
            //  - LIKE on title/captions (not covered by the fulltext index)
            // A row matching either counts as a hit; fulltext hits are ranked
            // higher via the relevance score.
            
            // Query: SELECT ma.*, v.*, tm.keywords, tm.tags, tm.captions, tm.description,
            //        MATCH(tm.keywords, tm.tags, tm.description) AGAINST (? IN NATURAL LANGUAGE MODE) AS relevance
            //        FROM multimedia_asset ma
            //        JOIN text_metadata tm ON ma.asset_id = tm.asset_id
            //        LEFT JOIN mmdb2026.vstu v ON ma.matric_number = v.matric_no
            //        WHERE [conditions] ORDER BY relevance DESC, ma.upload_date DESC
            // Fetches all vstu columns: id, matric_no, full_name, phone_no, group_no,
            // life_motto, password, photoStu, photoStu_date, docStu, docStu_date,
            // audioStu, audioStu_date, videoStu, videoStu_date
            $sql = "
            SELECT
                ma.*,
                v.*,
                tm.keywords,
                tm.tags,
                tm.captions,
                tm.description,
                MATCH(tm.keywords, tm.tags, tm.description) AGAINST (? IN NATURAL LANGUAGE MODE) AS relevance
            FROM multimedia_asset ma
            JOIN text_metadata tm ON ma.asset_id = tm.asset_id
            LEFT JOIN mmdb2026.vstu v ON ma.matric_number = v.matric_no
            WHERE
                MATCH(tm.keywords, tm.tags, tm.description) AGAINST (? IN NATURAL LANGUAGE MODE)
                OR ma.title LIKE ?
                OR tm.captions LIKE ?
            ORDER BY relevance DESC, ma.upload_date DESC
            ";

            // ✅ $sql is now always defined before this point
            if (!empty($sql)) {
                $stmt = mysqli_prepare($conn, $sql);
                if ($stmt) {
                    $likeTerm = '%' . $query_term . '%';
                    mysqli_stmt_bind_param($stmt, "ssss", $query_term, $query_term, $likeTerm, $likeTerm);
                    if (mysqli_stmt_execute($stmt)) {
                        $result = mysqli_stmt_get_result($stmt);
                        if ($result === false) {
                            $error_message = "Query executed but failed to retrieve results.";
                        }
                    } else {
                        $error_message = "Query execution failed: " . mysqli_stmt_error($stmt);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error_message = "Failed to prepare search query.";
                }
            } else {
                $error_message = "Failed to build search query.";
            }
        } catch (mysqli_sql_exception $e) {
            // MATCH AGAINST can throw for very short search terms depending on
            // ft_min_word_len — surface this clearly instead of a raw fatal error.
            $error_message = "Search failed: " . $e->getMessage();
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
