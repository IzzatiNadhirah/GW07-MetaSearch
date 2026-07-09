<?php
// ==========================================================================
// cbr_search.php
// Content-Based Retrieval (CBR) — Now searches mmdb2026.vstu table only.
// Retrieves students based on media availability: photo, doc, audio, video
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

$results = [];
$search_performed = false;
$error_message = null;
$selected_type = isset($_GET['type']) ? $_GET['type'] : 'photo';
$selected_value = isset($_GET['value']) ? $_GET['value'] : '';

$allowed_types = ['photo', 'doc', 'audio', 'video', 'motto'];

if (isset($_GET['search'])) {
    $search_performed = true;
    $type = $_GET['type'] ?? '';
    $value = trim($_GET['value'] ?? '');

    if (!in_array($type, $allowed_types, true)) {
        $error_message = "Invalid media type selected.";
    } elseif ($type === 'motto' && $value === '') {
        $error_message = "Please enter a motto to search for.";
    } elseif ($conn_error !== null) {
        $error_message = $conn_error;
    } else {
        try {
            $results = cbrSearch($conn, $type, $value);
            if (empty($results)) {
                $error_message = "No results found for your search criteria.";
            }
        } catch (mysqli_sql_exception $e) {
            $error_message = "Database error: " . $e->getMessage();
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

function getFeatureInfo($type) {
    $info = [
        'photo' => [
            'label' => 'Has Photo',
            'placeholder' => '',
            'type' => 'text',
            'hint' => 'Shows students who have uploaded a photo',
            'operator' => 'IS NOT NULL'
        ],
        'doc' => [
            'label' => 'Has Document',
            'placeholder' => '',
            'type' => 'text',
            'hint' => 'Shows students who have uploaded a document',
            'operator' => 'IS NOT NULL'
        ],
        'audio' => [
            'label' => 'Has Audio',
            'placeholder' => '',
            'type' => 'text',
            'hint' => 'Shows students who have uploaded audio',
            'operator' => 'IS NOT NULL'
        ],
        'video' => [
            'label' => 'Has Video',
            'placeholder' => '',
            'type' => 'text',
            'hint' => 'Shows students who have uploaded video',
            'operator' => 'IS NOT NULL'
        ],
        'motto' => [
            'label' => 'Life Motto Contains',
            'placeholder' => 'e.g. code, life, dream',
            'type' => 'text',
            'hint' => 'Search students by their life motto (partial match)',
            'operator' => 'LIKE'
        ]
    ];
    return $info[$type] ?? $info['photo'];
}

$featureInfo = getFeatureInfo($selected_type);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content-Based Retrieval | MetaSearch</title>
    <!-- UPDATED: shared external stylesheet -->
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
            <div class="subtitle">Search students by media availability or motto</div>
        </div>

        <div class="panel">
            <form method="GET" class="search-form" id="searchForm">
                <div class="form-group">
                    <label>Search Type</label>
                    <select name="type" id="mediaType" onchange="updateFields()">
                        <option value="photo" <?php echo $selected_type == 'photo' ? 'selected' : ''; ?>>Has Photo</option>
                        <option value="doc" <?php echo $selected_type == 'doc' ? 'selected' : ''; ?>>Has Document</option>
                        <option value="audio" <?php echo $selected_type == 'audio' ? 'selected' : ''; ?>>Has Audio</option>
                        <option value="video" <?php echo $selected_type == 'video' ? 'selected' : ''; ?>>Has Video</option>
                        <option value="motto" <?php echo $selected_type == 'motto' ? 'selected' : ''; ?>>Life Motto</option>
                    </select>
                </div>

                <div class="form-group">
                    <label id="featureLabel"><?php echo $featureInfo['label']; ?></label>
                    <input type="<?php echo $featureInfo['type']; ?>" 
                           id="valueInput"
                           name="value" 
                           placeholder="<?php echo $featureInfo['placeholder']; ?>" 
                           value="<?php echo htmlspecialchars($selected_value); ?>"
                           <?php echo ($selected_type !== 'motto') ? 'disabled' : ''; ?>>
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
                        <span class="feature-label">Photo</span>
                        <span class="feature-value">Has Photo</span>
                        <span class="feature-operator">IS NOT NULL</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">Document</span>
                        <span class="feature-value">Has Document</span>
                        <span class="feature-operator">IS NOT NULL</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">Audio</span>
                        <span class="feature-value">Has Audio</span>
                        <span class="feature-operator">IS NOT NULL</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">Video</span>
                        <span class="feature-value">Has Video</span>
                        <span class="feature-operator">IS NOT NULL</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">Motto</span>
                        <span class="feature-value">Life Motto</span>
                        <span class="feature-operator">LIKE</span>
                    </div>
                </div>
            </div>
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
                        Type: <span><?php echo htmlspecialchars(ucfirst($selected_type)); ?></span>
                        <?php if ($selected_type === 'motto'): ?>
                            | Motto contains: <span>"<?php echo htmlspecialchars($selected_value); ?>"</span>
                        <?php endif; ?>
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
                        <p>Try adjusting your search criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <a href="../index.php" class="btn-back">← Back to Home</a>
    </div>
    </main>

    <script>
        const fieldData = {
            'photo': {
                label: 'Has Photo',
                placeholder: '',
                type: 'text',
                hint: '<strong>Note:</strong> Shows students who have uploaded a photo <span class="operator-badge">IS NOT NULL</span>'
            },
            'doc': {
                label: 'Has Document',
                placeholder: '',
                type: 'text',
                hint: '<strong>Note:</strong> Shows students who have uploaded a document <span class="operator-badge">IS NOT NULL</span>'
            },
            'audio': {
                label: 'Has Audio',
                placeholder: '',
                type: 'text',
                hint: '<strong>Note:</strong> Shows students who have uploaded audio <span class="operator-badge">IS NOT NULL</span>'
            },
            'video': {
                label: 'Has Video',
                placeholder: '',
                type: 'text',
                hint: '<strong>Note:</strong> Shows students who have uploaded video <span class="operator-badge">IS NOT NULL</span>'
            },
            'motto': {
                label: 'Life Motto Contains',
                placeholder: 'e.g. code, life, dream',
                type: 'text',
                hint: '<strong>Note:</strong> Search students by their life motto (partial match) <span class="operator-badge">LIKE</span>'
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
            
            if (type === 'motto') {
                input.disabled = false;
                input.value = '';
            } else {
                input.disabled = true;
                input.value = '';
            }
            
            hint.innerHTML = data.hint;
        }
    </script>
</body>
</html>
