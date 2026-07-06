<?php
// ==========================================================================
// cbr_search.php
// Content-Based Retrieval (CBR) — per proposal section 3C:
// Retrieves multimedia based on inherent content characteristics
// represented by metadata.
// ==========================================================================

// UPDATED: use the single shared connection file (was: include 'db.php')
require_once __DIR__ . '/../config/db_connect.php';

// ✅ FIXED: Get connection from GLOBALS array
$conn = isset($GLOBALS['conn']) ? $GLOBALS['conn'] : null;

// ✅ FIXED: Check if connection exists and is valid
if ($conn === null || !$conn->ping()) {
    $conn_error = "Database connection is not available.";
} else {
    $conn_error = null;
}

$result = "";
$search_performed = false;
$error_message = null; // NEW: holds validation/query errors to show the user instead of failing silently
$selected_type = isset($_GET['type']) ? $_GET['type'] : 'image';
$selected_value = isset($_GET['value']) ? $_GET['value'] : '';

// NEW: whitelist of valid media types — prevents undefined $sql if an unexpected type is passed
$allowed_types = ['image', 'video', 'audio', 'document'];

if (isset($_GET['search'])) {
    $search_performed = true;
    $type = $_GET['type'] ?? '';
    $value = trim($_GET['value'] ?? '');

    // NEW: validate type against whitelist
    if (!in_array($type, $allowed_types, true)) {
        $error_message = "Invalid media type selected.";
    }
    // NEW: reject empty search value
    elseif ($value === '') {
        $error_message = "Please enter a search value.";
    }
    // ✅ FIXED: Check for connection error before proceeding
    elseif ($conn_error !== null) {
        $error_message = $conn_error;
    } else {

        switch ($type) {
            case "image":
                // Search by dominant color (LIKE for partial match)
                // NEW: prepared statement — previous version concatenated $value directly into SQL (SQL injection risk)
                $sql = "
                SELECT
                ma.title,
                ma.file_name,
                ma.file_path,
                im.width,
                im.height,
                im.resolution,
                im.dominant_color
                FROM multimedia_asset ma
                JOIN image_metadata im
                ON ma.asset_id = im.asset_id
                WHERE im.dominant_color LIKE ?
                ";
                $stmt = mysqli_prepare($conn, $sql);
                if ($stmt) {
                    $likeValue = '%' . $value . '%';
                    mysqli_stmt_bind_param($stmt, "s", $likeValue);
                } else {
                    $error_message = "Failed to prepare search query.";
                }
                break;

            case "video":
                // NEW: validate that value is numeric before using it as a duration filter
                if (!is_numeric($value) || (int)$value < 0) {
                    $error_message = "Duration must be a positive number (in minutes).";
                    break;
                }
                // Convert minutes to seconds for comparison
                $seconds = (int)$value * 60;
                $sql = "
                SELECT
                ma.title,
                ma.file_name,
                ma.file_path,
                vm.resolution,
                vm.duration_seconds,
                CONCAT(FLOOR(vm.duration_seconds / 60), 'm ', vm.duration_seconds % 60, 's') as duration_formatted
                FROM multimedia_asset ma
                JOIN video_metadata vm
                ON ma.asset_id = vm.asset_id
                WHERE vm.duration_seconds >= ?
                ORDER BY vm.duration_seconds DESC
                ";
                $stmt = mysqli_prepare($conn, $sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "i", $seconds);
                } else {
                    $error_message = "Failed to prepare search query.";
                }
                break;

            case "audio":
                // NEW: validate that value is numeric before using it as a duration filter
                if (!is_numeric($value) || (int)$value < 0) {
                    $error_message = "Duration must be a positive number (in minutes).";
                    break;
                }
                // Convert minutes to seconds for comparison
                $seconds = (int)$value * 60;
                $sql = "
                SELECT
                ma.title,
                ma.file_name,
                ma.file_path,
                am.duration_seconds,
                CONCAT(FLOOR(am.duration_seconds / 60), 'm ', am.duration_seconds % 60, 's') as duration_formatted,
                am.bitrate_kbps,
                am.audio_format
                FROM multimedia_asset ma
                JOIN audio_metadata am
                ON ma.asset_id = am.asset_id
                WHERE am.duration_seconds >= ?
                ORDER BY am.duration_seconds DESC
                ";
                $stmt = mysqli_prepare($conn, $sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "i", $seconds);
                } else {
                    $error_message = "Failed to prepare search query.";
                }
                break;

            case "document":
                // NEW: validate that value is numeric before using it as a page count filter
                if (!is_numeric($value) || (int)$value < 0) {
                    $error_message = "Page count must be a positive number.";
                    break;
                }
                // Search by page count
                $pageCount = (int)$value;
                $sql = "
                SELECT
                ma.title,
                ma.file_name,
                ma.file_path,
                dm.page_count,
                dm.is_searchable
                FROM multimedia_asset ma
                JOIN document_metadata dm
                ON ma.asset_id = dm.asset_id
                WHERE dm.page_count >= ?
                ORDER BY dm.page_count DESC
                ";
                $stmt = mysqli_prepare($conn, $sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "i", $pageCount);
                } else {
                    $error_message = "Failed to prepare search query.";
                }
                break;

            // NEW: default case guards against unexpected $type values reaching mysqli_query with an undefined $sql
            default:
                $error_message = "Unsupported media type.";
                break;
        }

        // NEW: execute the prepared statement (if one was successfully built) and capture the result set
        if (!$error_message && isset($stmt) && $stmt) {
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                if ($result === false) {
                    $error_message = "Query executed but failed to retrieve results.";
                }
            } else {
                $error_message = "Query execution failed: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

function getFeatureInfo($type) {
    $info = [
        'image' => [
            'label' => 'Dominant Color',
            'placeholder' => 'e.g. #FF0000 or red',
            'type' => 'color',
            'hint' => 'Search images by dominant color (partial match)',
            'operator' => 'LIKE'
        ],
        'video' => [
            'label' => 'Duration (minutes)',
            'placeholder' => 'e.g. 5',
            'type' => 'number',
            'hint' => 'Shows videos with duration >= value (in minutes)',
            'operator' => '>='
        ],
        'audio' => [
            'label' => 'Duration (minutes)',
            'placeholder' => 'e.g. 3',
            'type' => 'number',
            'hint' => 'Shows audio with duration >= value (in minutes)',
            'operator' => '>='
        ],
        'document' => [
            'label' => 'Page Count',
            'placeholder' => 'e.g. 10',
            'type' => 'number',
            'hint' => 'Shows documents with page count >= value',
            'operator' => '>='
        ]
    ];
    return $info[$type] ?? $info['image'];
}

$featureInfo = getFeatureInfo($selected_type);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content-Based Retrieval | MetaSearch</title>
    <!-- UPDATED: shared external stylesheet (moved out of inline <style>, now project-root style.css) -->
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
            <li><a href="../TBR/tbr.php">TBR</a></li>
            <li><a href="cbr_search.php" class="active">CBR</a></li>
        </ul>
    </aside>

    <!-- ==============================================================
    MAIN CONTENT
    ============================================================== -->
    <main class="main-content fade-in">
    <div class="container">
        <div class="header">
            <h1>Content-Based Retrieval (CBR)</h1>
            <div class="subtitle">Database: <span>gw07</span></div>
        </div>

        <div class="panel">
            <form method="GET" class="search-form" id="searchForm">
                <div class="form-group">
                    <label>Media Type</label>
                    <select name="type" id="mediaType" onchange="updateFields()">
                        <option value="image" <?php echo $selected_type == 'image' ? 'selected' : ''; ?>>Image</option>
                        <option value="video" <?php echo $selected_type == 'video' ? 'selected' : ''; ?>>Video</option>
                        <option value="audio" <?php echo $selected_type == 'audio' ? 'selected' : ''; ?>>Audio</option>
                        <option value="document" <?php echo $selected_type == 'document' ? 'selected' : ''; ?>>Document</option>
                    </select>
                </div>

                <div class="form-group">
                    <label id="featureLabel"><?php echo $featureInfo['label']; ?></label>
                    <input type="<?php echo $featureInfo['type']; ?>" 
                           id="valueInput"
                           name="value" 
                           placeholder="<?php echo $featureInfo['placeholder']; ?>" 
                           value="<?php echo htmlspecialchars($selected_value); ?>" 
                           required>
                </div>

                <div class="search-actions">
                    <button type="submit" name="search" class="btn btn-primary">Search</button>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn-reset">Reset</a>
                </div>
            </form>

            <div class="hint" id="hintText">
                <strong>Note:</strong> <?php echo $featureInfo['hint']; ?>
                <span class="operator-badge"><?php echo $featureInfo['operator']; ?></span>
            </div>

            <div class="features-section">
                <h3>Search Features</h3>
                <div class="features-grid">
                    <div class="feature-item">
                        <span class="feature-label">Image</span>
                        <span class="feature-value">Dominant Color</span>
                        <span class="feature-operator">LIKE</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">Video</span>
                        <span class="feature-value">Duration (minutes)</span>
                        <span class="feature-operator">>=</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">Audio</span>
                        <span class="feature-value">Duration (minutes)</span>
                        <span class="feature-operator">>=</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">Document</span>
                        <span class="feature-value">Page Count</span>
                        <span class="feature-operator">>=</span>
                    </div>
                </div>
            </div>
        </div>

        <?php /* NEW: show validation/query errors clearly instead of a silent "0 results" */ ?>
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
                        Type: <span><?php echo htmlspecialchars(ucfirst($selected_type)); ?></span> |
                        <?php echo $featureInfo['label']; ?> <?php echo $featureInfo['operator']; ?> <span><?php echo htmlspecialchars($selected_value); ?></span>
                    </span>
                </div>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <?php while ($field = mysqli_fetch_field($result)): ?>
                                        <th>
                                            <?php
                                            $labels = [
                                                'title' => 'Title',
                                                'file_name' => 'File Name',
                                                'file_path' => 'File Path',
                                                'width' => 'Width (px)',
                                                'height' => 'Height (px)',
                                                'resolution' => 'Resolution',
                                                'dominant_color' => 'Dominant Color',
                                                'duration_seconds' => 'Duration (s)',
                                                'duration_formatted' => 'Duration',
                                                'bitrate_kbps' => 'Bitrate (kbps)',
                                                'audio_format' => 'Format',
                                                'page_count' => 'Pages',
                                                'is_searchable' => 'Searchable'
                                            ];
                                            echo htmlspecialchars($labels[$field->name] ?? $field->name);
                                            ?>
                                        </th>
                                    <?php endwhile; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($result, 0);
                                while ($row = mysqli_fetch_assoc($result)):
                                ?>
                                    <tr>
                                        <?php foreach ($row as $key => $data): ?>
                                            <td>
                                                <?php
                                                if ($key == 'dominant_color' && !empty($data)) {
                                                    // NEW: escape the color value before echoing raw, and again when used in the style attribute
                                                    echo htmlspecialchars($data) . ' <span class="color-preview" style="background:' . htmlspecialchars($data) . ';"></span>';
                                                } elseif ($key == 'is_searchable') {
                                                    echo $data ? '<span class="badge badge-yes">Yes</span>' : '<span class="badge badge-no">No</span>';
                                                } elseif ($key == 'duration_formatted') {
                                                    echo htmlspecialchars($data);
                                                } elseif ($key == 'duration_seconds') {
                                                    echo htmlspecialchars($data) . 's';
                                                } else {
                                                    echo htmlspecialchars($data);
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <span class="icon">🔍</span>
                        <h2>No Results Found</h2>
                        <p>Try adjusting your search criteria or adding data to the database.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <a href="../index.php" class="btn-back">← Back to Home</a>
    </div>
    </main>

    <script>
        const fieldData = {
            'image': {
                label: 'Dominant Color',
                placeholder: 'e.g. #FF0000 or red',
                type: 'color',
                hint: '<strong>Note:</strong> Search images by dominant color (partial match) <span class="operator-badge">LIKE</span>'
            },
            'video': {
                label: 'Duration (minutes)',
                placeholder: 'e.g. 5',
                type: 'number',
                hint: '<strong>Note:</strong> Shows videos with duration >= value (in minutes) <span class="operator-badge">>=</span>'
            },
            'audio': {
                label: 'Duration (minutes)',
                placeholder: 'e.g. 3',
                type: 'number',
                hint: '<strong>Note:</strong> Shows audio with duration >= value (in minutes) <span class="operator-badge">>=</span>'
            },
            'document': {
                label: 'Page Count',
                placeholder: 'e.g. 10',
                type: 'number',
                hint: '<strong>Note:</strong> Shows documents with page count >= value <span class="operator-badge">>=</span>'
            }
        };

        function updateFields() {
            const type = document.getElementById('mediaType').value;
            const data = fieldData[type];
            const label = document.getElementById('featureLabel');
            const input = document.getElementById('valueInput');
            const hint = document.getElementById('hintText');

            label.textContent = data.label;
            input.placeholder = data.placeholder;
            input.type = data.type;
            if (data.type === 'color') {
                input.value = '#000000';
            } else {
                input.value = '';
            }
            hint.innerHTML = data.hint;
        }
    </script>
</body>
</html>
