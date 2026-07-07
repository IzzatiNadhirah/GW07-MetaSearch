<?php
// ==========================================================================
// 404.php - Page Not Found Handler
// Displays a user-friendly error page when a requested page is not found.
// ==========================================================================

// Set HTTP response status to 404
http_response_code(404);

// Get the requested URL that caused the 404
$requested_url = $_SERVER['REQUEST_URI'] ?? 'Unknown page';

// Clean up the URL for display
$requested_url = htmlspecialchars($requested_url);

// Get the referring page if available
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

// Determine if we're in a subdirectory
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | MetaSearch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: var(--bg-primary);
            color: var(--text-main);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .error-container {
            text-align: center;
            max-width: 600px;
            padding: 40px;
            background: var(--bg-panel);
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            color: var(--accent);
            opacity: 0.6;
            line-height: 1;
            margin-bottom: 10px;
        }
        .error-icon {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 2rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 10px;
        }
        .error-message {
            color: var(--text-muted);
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
        .error-url {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 10px 15px;
            color: var(--text-secondary);
            font-family: monospace;
            font-size: 0.9rem;
            margin-bottom: 30px;
            word-break: break-all;
        }
        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-home {
            background: var(--accent);
            color: var(--bg-primary);
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-home:hover {
            background: var(--accent-dark);
            color: var(--bg-primary);
            transform: translateY(-2px);
        }
        .btn-back {
            background: var(--border-color);
            color: var(--text-secondary);
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-back:hover {
            background: #3a3a3a;
            color: #fff;
        }
        .footer-links {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            margin: 0 10px;
            font-size: 0.85rem;
            transition: color 0.2s;
        }
        .footer-links a:hover {
            color: var(--accent);
        }
        .footer-copyright {
            margin-top: 20px;
            color: var(--text-dimmer);
            font-size: 0.75rem;
        }

        /* Fade-in animation */
        .fade-in {
            animation: fadeIn 0.4s ease-in-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .error-container {
                padding: 25px;
            }
            .error-code {
                font-size: 5rem;
            }
            .error-title {
                font-size: 1.5rem;
            }
            .btn-home, .btn-back {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

<div class="error-container fade-in">
    <!-- Error Icon -->
    <div class="error-icon">
        <i class="fa-regular fa-compass"></i>
    </div>

    <!-- Error Code -->
    <div class="error-code">404</div>

    <!-- Error Title -->
    <h1 class="error-title">Page Not Found</h1>

    <!-- Error Message -->
    <p class="error-message">
        Oops! The page you're looking for doesn't exist or has been moved.
    </p>

    <!-- Requested URL -->
    <div class="error-url">
        <i class="fa-regular fa-file-lines me-2"></i>
        <?php echo $requested_url; ?>
    </div>

    <!-- Action Buttons -->
    <div class="error-actions">
        <a href="index.php" class="btn-home">
            <i class="fa-solid fa-house"></i> Back to Home
        </a>
        <?php if ($referer): ?>
            <a href="<?php echo htmlspecialchars($referer); ?>" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Go Back
            </a>
        <?php else: ?>
            <a href="javascript:history.back()" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Go Back
            </a>
        <?php endif; ?>
    </div>

    <!-- Quick Navigation Links -->
    <div class="footer-links">
        <a href="index.php">Home</a>
        <a href="ABR/abr_search.html">ABR</a>
        <a href="TBR/tbr.php">TBR</a>
        <a href="CBR/cbr_search.php">CBR</a>
        <a href="test.php">Database Viewer</a>
    </div>

    <!-- Footer -->
    <div class="footer-copyright">
        <i class="fa-regular fa-copyright me-1"></i>
        MetaSearch — Multimedia Database Project
    </div>
</div>

<script>
    // Log the 404 error for debugging
    console.log('404 Page: Requested URL - <?php echo $requested_url; ?>');
    console.log('404 Page: Referer - <?php echo htmlspecialchars($referer ?? 'None'); ?>');
    
    // Optional: Send 404 error to analytics if needed
    // if (typeof gtag !== 'undefined') {
    //     gtag('event', '404', { 'page_path': '<?php echo $requested_url; ?>' });
    // }
</script>

</body>
</html>
