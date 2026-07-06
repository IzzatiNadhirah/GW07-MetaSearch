<?php
// test.php
// Simple database viewer for the MetaSearch schema (student_users,
// multimedia_asset + per-type metadata tables, system_metadata_analytics).
// Intended as a dev/debug tool to confirm data and joins look correct.

require_once 'db_connect.php';

// --------------------------------------------------------------------------
// View selection (which table/report to display)
// --------------------------------------------------------------------------
$allowedViews = ['students', 'assets', 'analytics'];
$view = isset($_GET['view']) ? $_GET['view'] : 'assets';
if (!in_array($view, $allowedViews, true)) {
    $view = 'assets'; // NEW: fall back to a safe default instead of erroring on a bad ?view= value
}

// --------------------------------------------------------------------------
// Pagination setup (not used for 'analytics', which is a single summary row)
// --------------------------------------------------------------------------
$limit = 10; // rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$rows = [];
$total_rows = 0;
$total_pages = 0;
$error_message = null; // NEW: surfaces query/connection failures instead of a blank page or fatal error

try {
    if ($view === 'students') {
        // ------------------------------------------------------------
        // STUDENT_USERS
        // ------------------------------------------------------------
        $total_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM student_users");
        if ($total_result === false) {
            throw new Exception("Failed to count rows in student_users: " . mysqli_error($conn));
        }
        $total_rows = (int) mysqli_fetch_assoc($total_result)['total'];
        $total_pages = $total_rows > 0 ? (int) ceil($total_rows / $limit) : 1;
        if ($page > $total_pages) {
            $page = $total_pages;
            $offset = ($page - 1) * $limit;
        }

        $sql = "SELECT id, matric_number, full_name, phone_no, group_no, life_motto, password, created_at
                FROM student_users
                ORDER BY id
                LIMIT $limit OFFSET $offset";
        $result = mysqli_query($conn, $sql);
        if ($result === false) {
            throw new Exception("Failed to retrieve student_users rows: " . mysqli_error($conn));
        }
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }

    } elseif ($view === 'assets') {
        // ------------------------------------------------------------
        // MULTIMEDIA_ASSET joined with student_users + all 5 metadata
        // tables (LEFT JOIN, since only one metadata table will match
        // depending on file_type — the others simply come back NULL).
        // ------------------------------------------------------------
        $total_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM multimedia_asset");
        if ($total_result === false) {
            throw new Exception("Failed to count rows in multimedia_asset: " . mysqli_error($conn));
        }
        $total_rows = (int) mysqli_fetch_assoc($total_result)['total'];
        $total_pages = $total_rows > 0 ? (int) ceil($total_rows / $limit) : 1;
        if ($page > $total_pages) {
            $page = $total_pages;
            $offset = ($page - 1) * $limit;
        }

        $sql = "SELECT
                    ma.asset_id,
                    ma.matric_number,
                    su.full_name,
                    ma.title,
                    ma.file_name,
                    ma.file_type,
                    ma.mime_type,
                    ma.file_size_kb,
                    ma.upload_date,
                    ma.last_modified,
                    im.width            AS img_width,
                    im.height           AS img_height,
                    im.resolution       AS img_resolution,
                    im.dominant_color,
                    am.duration_seconds AS audio_duration_seconds,
                    am.bitrate_kbps,
                    am.audio_format,
                    vm.resolution       AS video_resolution,
                    vm.frame_rate,
                    vm.duration_seconds AS video_duration_seconds,
                    dm.page_count,
                    dm.is_searchable,
                    tm.keywords,
                    tm.tags
                FROM multimedia_asset ma
                LEFT JOIN student_users     su ON ma.matric_number = su.matric_number
                LEFT JOIN image_metadata    im ON ma.asset_id = im.asset_id
                LEFT JOIN audio_metadata    am ON ma.asset_id = am.asset_id
                LEFT JOIN video_metadata    vm ON ma.asset_id = vm.asset_id
                LEFT JOIN document_metadata dm ON ma.asset_id = dm.asset_id
                LEFT JOIN text_metadata     tm ON ma.asset_id = tm.asset_id
                ORDER BY ma.asset_id
                LIMIT $limit OFFSET $offset";
        $result = mysqli_query($conn, $sql);
        if ($result === false) {
            throw new Exception("Failed to retrieve multimedia_asset rows: " . mysqli_error($conn));
        }
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }

    } elseif ($view === 'analytics') {
        // ------------------------------------------------------------
        // SYSTEM_METADATA_ANALYTICS — typically a single summary row,
        // so no pagination needed here.
        // ------------------------------------------------------------
        $result = mysqli_query($conn, "SELECT * FROM system_metadata_analytics ORDER BY sys_id DESC");
        if ($result === false) {
            throw new Exception("Failed to query system_metadata_analytics: " . mysqli_error($conn));
        }
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        $total_rows = count($rows);
        $total_pages = 1;
    }

} catch (Exception $e) {
    $error_message = $e->getMessage();
} catch (mysqli_sql_exception $e) {
    $error_message = $e->getMessage();
}

// --------------------------------------------------------------------------
// Helper: build a nav link, marking the active view
// --------------------------------------------------------------------------
function viewLink($targetView, $label, $currentView) {
    $activeStyle = ($targetView === $currentView) ? " style='font-weight:bold; text-decoration:underline;'" : "";
    return "<a href='?view=" . htmlspecialchars($targetView) . "'" . $activeStyle . ">" . htmlspecialchars($label) . "</a>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MetaSearch — Database Viewer (test.php)</title>
</head>
<body>

<h2>MetaSearch Database Viewer</h2>
<nav style="margin-bottom:20px;">
    <?php echo viewLink('students', 'Students', $view); ?> |
    <?php echo viewLink('assets', 'Multimedia Assets', $view); ?> |
    <?php echo viewLink('analytics', 'System Analytics', $view); ?>
</nav>

<?php if ($error_message): ?>
    <!-- NEW: clear error output instead of a raw PHP fatal error or blank page -->
    <p style="color:red;"><strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?></p>

<?php elseif (count($rows) === 0): ?>
    <p>No data found for this view.</p>

<?php else: ?>

    <?php if ($view === 'students'): ?>
        <table border="1" cellpadding="10">
            <tr>
                <th>ID</th>
                <th>Matric Number</th>
                <th>Full Name</th>
                <th>Phone No</th>
                <th>Group No</th>
                <th>Life Motto</th>
                <th>Password</th>
                <th>Created At</th>
            </tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['matric_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone_no'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['group_no'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['life_motto'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['password']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

    <?php elseif ($view === 'assets'): ?>
        <table border="1" cellpadding="8">
            <tr>
                <th>Asset ID</th>
                <th>Matric No</th>
                <th>Owner</th>
                <th>Title</th>
                <th>File Name</th>
                <th>Type</th>
                <th>Size (KB)</th>
                <th>Upload Date</th>
                <!-- Image-specific -->
                <th>Img Resolution</th>
                <th>Dominant Color</th>
                <!-- Audio-specific -->
                <th>Audio Duration (s)</th>
                <th>Bitrate</th>
                <!-- Video-specific -->
                <th>Video Resolution</th>
                <th>Video Duration (s)</th>
                <!-- Document-specific -->
                <th>Page Count</th>
                <!-- Text metadata -->
                <th>Keywords</th>
                <th>Tags</th>
            </tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['asset_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['matric_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['full_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                    <td><?php echo htmlspecialchars(strtoupper($row['file_type'])); ?></td>
                    <td><?php echo htmlspecialchars($row['file_size_kb']); ?></td>
                    <td><?php echo htmlspecialchars($row['upload_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['img_resolution'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['dominant_color'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['audio_duration_seconds'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['bitrate_kbps'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['video_resolution'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['video_duration_seconds'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['page_count'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['keywords'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['tags'] ?? '-'); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

    <?php elseif ($view === 'analytics'): ?>
        <table border="1" cellpadding="10">
            <tr>
                <th>Sys ID</th>
                <th>Total Tracked Users</th>
                <th>Upload Frequency Today</th>
                <th>Avg File Size (KB)</th>
                <th>Most Searched Keyword</th>
                <th>Last Updated</th>
            </tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['sys_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['total_tracked_users']); ?></td>
                    <td><?php echo htmlspecialchars($row['upload_frequency_today']); ?></td>
                    <td><?php echo htmlspecialchars($row['avg_file_size_kb']); ?></td>
                    <td><?php echo htmlspecialchars($row['most_searched_keyword'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['last_updated_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <?php // NEW: pagination links only shown for paginated views (students/assets), and only if more than 1 page ?>
    <?php if ($view !== 'analytics' && $total_pages > 1): ?>
        <div style="margin-top:20px;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <strong><?php echo htmlspecialchars($i); ?></strong>
                <?php else: ?>
                    <a href="?view=<?php echo htmlspecialchars($view); ?>&page=<?php echo htmlspecialchars($i); ?>"><?php echo htmlspecialchars($i); ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

</body>
</html>