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
    // 5. UTILITY: Display results in grid (ABR) - Now displays student data
    // ======================================================================
    function displayResults(students, container) {
        if (!students || students.length === 0) {
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
        students.forEach(function(student) {
            // Build student card with all available data
            html += `
                <div class="result-card" style="background:var(--bg-panel);border:1px solid var(--border-color);border-radius:8px;padding:1rem;transition:transform 0.15s,border-color 0.15s;">
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                        <div style="width:50px;height:50px;border-radius:50%;background:var(--bg-primary);border:2px solid var(--accent);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;">
                            ${student.photoStu ? `<img src="${escapeHtml(student.photoStu)}" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none';this.parentElement.innerHTML='<i class=\\'fa-solid fa-user\\' style=\\'color:var(--accent);font-size:1.5rem;\\'></i>';">` : `<i class="fa-solid fa-user" style="color:var(--accent);font-size:1.5rem;"></i>`}
                        </div>
                        <div>
                            <h4 style="color:var(--accent);font-size:1rem;margin-bottom:2px;">${escapeHtml(student.full_name)}</h4>
                            <p style="color:var(--text-muted);font-size:0.8rem;margin:0;"><strong>Matric:</strong> ${escapeHtml(student.matric_no)}</p>
                        </div>
                    </div>
                    <p style="color:var(--text-muted);font-size:0.8rem;margin:2px 0;"><strong>Group:</strong> ${escapeHtml(student.group_no || 'N/A')}</p>
                    ${student.life_motto ? `<p style="color:var(--text-muted);font-size:0.8rem;margin:2px 0;font-style:italic;">"${escapeHtml(student.life_motto)}"</p>` : ''}
                    <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">
                        ${student.photoStu ? `<a href="${escapeHtml(student.photoStu)}" target="_blank" class="badge" style="background:var(--accent);color:var(--bg-primary);padding:2px 10px;border-radius:12px;font-size:0.7rem;text-decoration:none;font-weight:600;"><i class="fa-solid fa-image"></i> Photo</a>` : ''}
                        ${student.docStu ? `<a href="${escapeHtml(student.docStu)}" target="_blank" class="badge" style="background:#dc3545;color:#fff;padding:2px 10px;border-radius:12px;font-size:0.7rem;text-decoration:none;font-weight:600;"><i class="fa-solid fa-file-pdf"></i> Doc</a>` : ''}
                        ${student.audioStu ? `<a href="${escapeHtml(student.audioStu)}" target="_blank" class="badge" style="background:#ffc107;color:#000;padding:2px 10px;border-radius:12px;font-size:0.7rem;text-decoration:none;font-weight:600;"><i class="fa-solid fa-music"></i> Audio</a>` : ''}
                        ${student.videoStu ? `<a href="${escapeHtml(student.videoStu)}" target="_blank" class="badge" style="background:#198754;color:#fff;padding:2px 10px;border-radius:12px;font-size:0.7rem;text-decoration:none;font-weight:600;"><i class="fa-solid fa-video"></i> Video</a>` : ''}
                    </div>
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
