<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MetaSearch | Admin Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/viewport/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { min-height: 100vh; background-color: #1e293b; color: #fff; width: 260px; position: fixed; transition: all 0.3s; }
        .sidebar .nav-link { color: #cbd5e1; padding: 12px 20px; display: flex; align-items: center; gap: 10px; border-radius: 4px; margin: 4px 15px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #334155; color: #fff; }
        .main-content { margin-left: 260px; padding: 30px; transition: all 0.3s; }
        .card-stat { border: none; border-radius: 10px; transition: transform 0.2s; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .card-stat:hover { transform: translateY(-3px); }
        .search-box { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar d-flex flex-column justify-content-between pb-4">
        <div>
            <div class="p-4 border-bottom border-secondary d-flex align-items-center gap-2">
                <i class="fa-solid fa-cubes text-info fs-3"></i>
                <span class="fs-4 fw-bold tracking-wide text-white">MetaSearch</span>
            </div>
            <ul class="nav nav-pills flex-column mt-3">
                <li class="nav-item"><a href="#" class="nav-link active"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li class="nav-item"><a href="#abr-section" class="nav-link"><i class="fa-solid fa-sliders"></i> ABR Panel</a></li>
                <li class="nav-item"><a href="#tbr-section" class="nav-link"><i class="fa-solid fa-magnifying-glass"></i> TBR Search</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fa-solid fa-chart-line"></i> Analytics</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fa-solid fa-gear"></i> Settings</a></li>
            </ul>
        </div>
        <div class="px-3">
            <hr class="text-secondary">
            <div class="d-flex align-items-center gap-2 px-3 text-muted fs-7">
                <i class="fa-solid fa-circle-user text-success"></i>
                <span>Logged in as Admin</span>
            </div>
        </div>
    </div>

    <!-- Main Content Work Area -->
    <div class="main-content">
        <!-- Header -->
        <header class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div>
                <h2 class="fw-bold text-dark m-0">Overview Analytics</h2>
                <small class="text-muted">Welcome back to MetaSearch dashboard portal</small>
            </div>
            <div class="text-end">
                <div id="liveClock" class="fw-bold text-secondary fs-5"><i class="fa-regular fa-clock me-1"></i> --:--:--</div>
                <span class="badge bg-light text-dark border"><i class="fa-solid fa-earth-asia text-primary"></i> Asia/Kuala_Lumpur</span>
            </div>
        </header>

        <!-- Metric Counter Cards -->
        <div class="row g-4 mb-5">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card card-stat p-3 d-flex flex-row align-items-center justify-content-between">
                    <div><h6 class="text-muted text-uppercase small">Images</h6><h3 class="fw-bold m-0 text-primary">128</h3></div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-3 fs-3"><i class="fa-solid fa-image"></i></div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card card-stat p-3 d-flex flex-row align-items-center justify-content-between">
                    <div><h6 class="text-muted text-uppercase small">Videos</h6><h3 class="fw-bold m-0 text-success">42</h3></div>
                    <div class="bg-success-subtle text-success p-3 rounded-3 fs-3"><i class="fa-solid fa-video"></i></div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card card-stat p-3 d-flex flex-row align-items-center justify-content-between">
                    <div><h6 class="text-muted text-uppercase small">Audio Tracks</h6><h3 class="fw-bold m-0 text-warning">15</h3></div>
                    <div class="bg-warning-subtle text-warning p-3 rounded-3 fs-3"><i class="fa-solid fa-music"></i></div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card card-stat p-3 d-flex flex-row align-items-center justify-content-between">
                    <div><h6 class="text-muted text-uppercase small">Documents</h6><h3 class="fw-bold m-0 text-danger">34</h3></div>
                    <div class="bg-danger-subtle text-danger p-3 rounded-3 fs-3"><i class="fa-solid fa-file-lines"></i></div>
                </div>
            </div>
        </div>

        <!-- Attribute-Based Retrieval Filter Matrix -->
        <section id="abr-section" class="search-box mb-5">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-sliders text-primary me-2"></i>Attribute-Based Retrieval (ABR)</h5>
            <form action="" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">File Type</label>
                    <select name="file_type" class="form-select">
                        <option value="">All Formats</option>
                        <option value="image">Image</option>
                        <option value="audio">Audio</option>
                        <option value="video">Video</option>
                        <option value="document">Document</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Upload Date</label>
                    <input type="date" name="upload_date" class="form-select">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Owner/Uploader</label>
                    <input type="text" name="owner" class="form-control" placeholder="Search owner...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Resolution</label>
                    <select name="resolution" class="form-select">
                        <option value="">Any Resolution</option>
                        <option value="1080p">1080p (Full HD)</option>
                        <option value="4K">4K UHD</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                    <button type="reset" class="btn btn-light border">Reset</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-filter me-2"></i>Apply Filters</button>
                </div>
            </form>
        </section>

        <!-- Text-Based Retrieval Search Block -->
        <section id="tbr-section" class="search-box mb-5">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-magnifying-glass text-success me-2"></i>Text-Based Retrieval (TBR)</h5>
            <form action="" method="GET">
                <div class="input-group input-group-lg shadow-sm">
                    <input type="text" name="query" class="form-control border-end-0" placeholder="Search multimedia files by keywords, tags, title, descriptions...">
                    <button type="submit" class="btn btn-success px-5"><i class="fa-solid fa-magnifying-glass me-2"></i>Search</button>
                </div>
                <div class="mt-3">
                    <span class="text-muted small me-2">Suggested Tags:</span>
                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1 me-1">#DeepLearning</span>
                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1 me-1">#Dataset</span>
                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1 me-1">#Metadata</span>
                </div>
            </form>
        </section>

        <!-- Recent System Activities Data Table -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0 text-dark">Recent System Uploads</h5>
                <button class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-down-solid-link me-1"></i> Export Data</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>File Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Upload Date</th>
                            <th>Owner</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class="fa-solid fa-file-video text-success me-2"></i>Global_Dynamics_2024.mp4</td>
                            <td><span class="badge bg-success-subtle text-success">Video/MP4</span></td>
                            <td>45.2 MB</td>
                            <td>Oct 12, 2026</td>
                            <td>Nur Izzati</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-light border"><i class="fa-regular fa-eye"></i></button>
                                <button class="btn btn-sm btn-light border text-danger"><i class="fa-regular fa-trash-can"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td><i class="fa-solid fa-file-image text-primary me-2"></i>Infographic_Dataset_A.png</td>
                            <td><span class="badge bg-primary-subtle text-primary">Image/PNG</span></td>
                            <td>3.4 MB</td>
                            <td>Sep 23, 2026</td>
                            <td>Toh Shuai Ting</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-light border"><i class="fa-regular fa-eye"></i></button>
                                <button class="btn btn-sm btn-light border text-danger"><i class="fa-regular fa-trash-can"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Clock Synchronization Script -->
    <script>
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            document.getElementById('liveClock').innerHTML = `<i class="fa-regular fa-clock me-1"></i> ${timeString}`;
        }
        setInterval(updateClock, 1000);
        updateClock(); // Initial pull
    </script>
</body>
</html>