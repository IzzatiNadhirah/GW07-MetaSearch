// ==========================================================================
// MetaSearch — Main JavaScript Module
// Handles:
//   1. ABR (Attribute-Based Retrieval) form submission & result rendering
//   2. Dynamic UI updates (slider values, form field changes)
//   3. Tag chip click handlers
//   4. Utility functions
// ==========================================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ======================================================================
    // 1. ABR FORM HANDLER (for abr_search.html)
    // ======================================================================
    const abrForm = document.getElementById('abrForm');
    const resultsGrid = document.getElementById('resultsGrid');

    if (abrForm && resultsGrid) {
        abrForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            resultsGrid.innerHTML = `
                <div style="text-align:center;padding:40px;color:#888;">
                    <i class="fa-solid fa-spinner fa-spin" style="font-size:2rem;"></i>
                    <p style="margin-top:10px;">Searching...</p>
                </div>
            `;

            const formData = new FormData(abrForm);
            
            fetch('abr_filter.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResults(data.data, resultsGrid);
                } else {
                    resultsGrid.innerHTML = `
                        <div class="no-results">
                            <span class="icon">⚠️</span>
                            <h2>Error</h2>
                            <p>${data.error || 'An error occurred while searching.'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultsGrid.innerHTML = `
                    <div class="no-results">
                        <span class="icon">❌</span>
                        <h2>Connection Error</h2>
                        <p>${error.message || 'Failed to connect to server.'}</p>
                    </div>
                `;
            });
        });

        // Reset button handler
        const resetBtn = abrForm.querySelector('.btn-reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', function(e) {
                e.preventDefault();
                abrForm.reset();
                resultsGrid.innerHTML = '';
                // Reset slider displays
                const sizeSlider = document.getElementById('maxSize');
                const durationSlider = document.getElementById('maxDuration');
                const sizeDisplay = document.getElementById('sizeValue');
                const durationDisplay = document.getElementById('durationValue');
                if (sizeSlider && sizeDisplay) {
                    sizeDisplay.textContent = sizeSlider.value + ' MB';
                }
                if (durationSlider && durationDisplay) {
                    durationDisplay.textContent = durationSlider.value + 's';
                }
            });
        }
    }

    // ======================================================================
    // 2. SLIDER VALUE DISPLAYS (ABR form)
    // ======================================================================
    const sizeSlider = document.getElementById('maxSize');
    const durationSlider = document.getElementById('maxDuration');
    const sizeDisplay = document.getElementById('sizeValue');
    const durationDisplay = document.getElementById('durationValue');

    if (sizeSlider && sizeDisplay) {
        sizeSlider.addEventListener('input', function() {
            sizeDisplay.textContent = this.value + ' MB';
        });
    }

    if (durationSlider && durationDisplay) {
        durationSlider.addEventListener('input', function() {
            durationDisplay.textContent = this.value + 's';
        });
    }

    // ======================================================================
    // 3. TAG CHIP CLICK HANDLERS (TBR page)
    // ======================================================================
    document.querySelectorAll('.tag-chip').forEach(function(chip) {
        chip.addEventListener('click', function(e) {
            e.preventDefault();
            const tag = this.textContent.replace('#', '').trim();
            const searchInput = document.getElementById('q');
            if (searchInput) {
                searchInput.value = tag;
                const form = searchInput.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
    });

    // ======================================================================
    // 4. CBR MEDIA TYPE CHANGE HANDLER (cbr_search.php uses inline JS already)
    //    But we keep this as fallback
    // ======================================================================
    const mediaTypeSelect = document.getElementById('mediaType');
    if (mediaTypeSelect && typeof updateFields === 'function') {
        mediaTypeSelect.addEventListener('change', updateFields);
    }

    // ======================================================================
    // 5. UTILITY: Display results in grid (ABR)
    // ======================================================================
    function displayResults(assets, container) {
        if (!assets || assets.length === 0) {
            container.innerHTML = `
                <div class="no-results">
                    <span class="icon">🔍</span>
                    <h2>No Results Found</h2>
                    <p>Try adjusting your filter criteria.</p>
                </div>
            `;
            return;
        }

        let html = '';
        assets.forEach(function(asset) {
            // Determine file type badge color
            let badgeColor = 'bg-secondary';
            let icon = 'fa-file';
            if (asset.file_type === 'image') {
                badgeColor = 'bg-primary';
                icon = 'fa-file-image';
            } else if (asset.file_type === 'video') {
                badgeColor = 'bg-success';
                icon = 'fa-file-video';
            } else if (asset.file_type === 'audio') {
                badgeColor = 'bg-warning';
                icon = 'fa-file-audio';
            } else if (asset.file_type === 'document') {
                badgeColor = 'bg-danger';
                icon = 'fa-file-lines';
            }

            // Get display values
            const displayResolution = asset.video_resolution || asset.img_resolution || 'N/A';
            const displayDuration = asset.video_duration_seconds || asset.audio_duration_seconds || 'N/A';
            const ownerName = asset.owner_name || asset.full_name || asset.matric_number || 'Unknown';
            const fileSizeMB = (asset.file_size_kb / 1024).toFixed(2);

            html += `
                <div class="result-card">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                        <i class="fa-solid ${icon}" style="font-size:1.2rem;color:var(--accent);"></i>
                        <span class="badge ${badgeColor}" style="font-size:0.7rem;">${asset.file_type.toUpperCase()}</span>
                    </div>
                    <h4>${escapeHtml(asset.title || asset.file_name)}</h4>
                    <p><strong>File:</strong> ${escapeHtml(asset.file_name)}</p>
                    <p><strong>Size:</strong> ${fileSizeMB} MB</p>
                    <p><strong>Owner:</strong> ${escapeHtml(ownerName)}</p>
                    <!-- Student Information from vstu table -->
                    ${asset.full_name ? `<p><strong>Student:</strong> ${escapeHtml(asset.full_name)}</p>` : ''}
                    ${asset.matric_no ? `<p><strong>Matric:</strong> ${escapeHtml(asset.matric_no)}</p>` : ''}
                    ${asset.group_no ? `<p><strong>Group:</strong> ${escapeHtml(asset.group_no)}</p>` : ''}
                    ${asset.phone_no ? `<p><strong>Phone:</strong> ${escapeHtml(asset.phone_no)}</p>` : ''}
                    ${asset.life_motto ? `<p><strong>Motto:</strong> "${escapeHtml(asset.life_motto)}"</p>` : ''}
                    <p><strong>Resolution:</strong> ${displayResolution}</p>
                    <p><strong>Duration:</strong> ${displayDuration !== 'N/A' ? displayDuration + 's' : 'N/A'}</p>
                    ${asset.dominant_color ? `<p><strong>Color:</strong> <span style="display:inline-block;width:20px;height:20px;background:${escapeHtml(asset.dominant_color)};border-radius:4px;vertical-align:middle;border:1px solid #333;"></span> ${escapeHtml(asset.dominant_color)}</p>` : ''}
                    <p><small>Uploaded: ${asset.upload_date}</small></p>
                    ${asset.file_path ? `<a href="${escapeHtml(asset.file_path)}" target="_blank" class="btn btn-primary" style="padding:6px 15px;font-size:0.8rem;min-width:auto;margin-top:8px;display:inline-block;">View File</a>` : ''}
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // ======================================================================
    // 6. UTILITY: HTML escaping
    // ======================================================================
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ======================================================================
    // 7. DASHBOARD: Auto-refresh stats (optional)
    // ======================================================================
    // Uncomment if you want auto-refresh every 30 seconds
    /*
    if (document.querySelector('.card-stat')) {
        setInterval(function() {
            fetch('dashboard_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update stat cards
                        const stats = document.querySelectorAll('.card-stat h3');
                        if (stats.length >= 4) {
                            stats[0].textContent = data.total || 0;
                        }
                    }
                })
                .catch(() => {});
        }, 30000);
    }
    */

    // ======================================================================
    // 8. KEYBOARD SHORTCUT: Ctrl+Enter to submit search forms
    // ======================================================================
    document.querySelectorAll('.search-form input, .filter-form input, .search-form select, .filter-form select').forEach(function(input) {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                const form = this.closest('form');
                if (form) {
                    const submitBtn = form.querySelector('button[type="submit"], .btn-primary');
                    if (submitBtn) {
                        submitBtn.click();
                    } else {
                        form.submit();
                    }
                }
            }
        });
    });

    console.log('MetaSearch: Script loaded successfully!');
    console.log('Available views: ABR, TBR, CBR, Dashboard');
});
