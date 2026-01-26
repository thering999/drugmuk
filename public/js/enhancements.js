// CSRF fetch interceptor for AJAX safety
const originalFetch = window.fetch;
window.fetch = function (resource, config) {
    if (!config) config = {};
    if (!config.headers) config.headers = {};

    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // Add token to headers for state-changing methods
    const method = (config.method || 'GET').toUpperCase();
    if (csrfToken && !['GET', 'HEAD', 'OPTIONS'].includes(method)) {
        // Support common header names
        config.headers['X-CSRF-TOKEN'] = csrfToken;
        config.headers['X-Requested-With'] = 'XMLHttpRequest';
    }

    return originalFetch(resource, config);
};

// Dark Mode Toggle
function toggleDarkMode() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
}

// Load saved theme
document.addEventListener('DOMContentLoaded', function () {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
});

// Toast Notifications
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Keyboard Shortcuts
let lastKey = null;
let lastKeyTime = 0;

document.addEventListener('keydown', function (e) {
    // Ctrl/Cmd + K: Global Search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        openGlobalSearch();
    }

    // Ctrl/Cmd + /: Show keyboard shortcuts
    if ((e.ctrlKey || e.metaKey) && e.key === '/') {
        e.preventDefault();
        toggleKeyboardShortcuts();
    }

    // Navigation Shortcuts (G + Key)
    const now = Date.now();
    if (lastKey === 'g' && now - lastKeyTime < 1000) {
        switch (e.key.toLowerCase()) {
            case 'd': window.location.href = '/drugs'; break;
            case 'i': window.location.href = '/inventory'; break;
            case 'h': window.location.href = '/dashboard'; break;
            case 'o': window.location.href = '/orders'; break;
            case 'p': window.location.href = '/purchasing'; break;
            case 's': window.location.href = '/scan'; break;
            case 'l': window.location.href = '/admin/intelligence'; break;
        }
    }
    lastKey = e.key.toLowerCase();
    lastKeyTime = now;

    // Escape: Close modals
    if (e.key === 'Escape') {
        closeAllModals();
    }
});

function openGlobalSearch() {
    let searchModal = document.querySelector('#globalSearchModal');
    if (!searchModal) {
        createGlobalSearchModal();
        searchModal = document.querySelector('#globalSearchModal');
    }
    searchModal.style.display = 'block';
    searchModal.querySelector('input').focus();
}

function createGlobalSearchModal() {
    const modal = document.createElement('div');
    modal.id = 'globalSearchModal';
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content search-modal-content">
            <span class="close" onclick="closeAllModals()">&times;</span>
            <h2><i class="fas fa-search"></i> Global Search (Drugs / Patients)</h2>
            <div class="search-input-wrapper">
                <input type="text" id="globalSearchInput" placeholder="Type name, code, HN..." oninput="handleGlobalSearch(this.value)">
                <i class="fas fa-spinner fa-spin" id="globalSearchSpinner" style="display:none"></i>
            </div>
            <div id="globalSearchResults" class="search-results"></div>
            <div class="search-help">
                <small>Tip: Search for drug name or patient HN</small>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function handleGlobalSearch(query) {
    if (query.length < 2) {
        document.getElementById('globalSearchResults').innerHTML = '';
        return;
    }

    const spinner = document.getElementById('globalSearchSpinner');
    spinner.style.display = 'inline-block';

    try {
        // Search drugs and patients in parallel
        const [drugRes, patientRes] = await Promise.all([
            fetch(`/api/drugs?q=${encodeURIComponent(query)}`),
            fetch(`/api/patient/search?q=${encodeURIComponent(query)}`)
        ]);

        const drugs = await drugRes.json();
        const patients = await patientRes.json();

        let html = '';

        if (patients.data && patients.data.length > 0) {
            html += '<h3>Patients</h3>';
            patients.data.slice(0, 5).forEach(p => {
                html += `
                    <div class="search-result-item" onclick="window.location.href='/patient/${p.hn}'">
                        <i class="fas fa-user"></i> <strong>${p.patient_name}</strong> (HN: ${p.hn})
                    </div>
                `;
            });
        }

        if (drugs.success && drugs.data && drugs.data.length > 0) {
            html += '<h3>Drugs</h3>';
            drugs.data.slice(0, 5).forEach(d => {
                html += `
                    <div class="search-result-item" onclick="window.location.href='/drugs/show/${d.id}'">
                        <i class="fas fa-pills"></i> <strong>${d.name}</strong> (${d.code})
                    </div>
                `;
            });
        }

        if (html === '') {
            html = '<div class="no-results">No results found for "' + query + '"</div>';
        }

        document.getElementById('globalSearchResults').innerHTML = html;
    } catch (err) {
        console.error('Search failed', err);
    } finally {
        spinner.style.display = 'none';
    }
}

function toggleKeyboardShortcuts() {
    const shortcuts = document.querySelector('.keyboard-shortcuts');
    if (shortcuts) {
        shortcuts.classList.toggle('show');
    }
}

function closeAllModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
    });
}

// Lazy Loading Images
document.addEventListener('DOMContentLoaded', function () {
    const lazyImages = document.querySelectorAll('img.lazy');

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for browsers without IntersectionObserver
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazy');
        });
    }
});

// Form Validation Helper
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });

    return isValid;
}

// Auto-save Form Data
function autoSaveForm(formId, interval = 30000) {
    const form = document.getElementById(formId);
    if (!form) return;

    setInterval(() => {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        localStorage.setItem(`autosave_${formId}`, JSON.stringify(data));
        showToast('Form auto-saved', 'info');
    }, interval);
}

// Restore Auto-saved Data
function restoreFormData(formId) {
    const saved = localStorage.getItem(`autosave_${formId}`);
    if (!saved) return;

    const data = JSON.parse(saved);
    const form = document.getElementById(formId);

    Object.keys(data).forEach(key => {
        const field = form.querySelector(`[name="${key}"]`);
        if (field) field.value = data[key];
    });
}

// Confirm Before Leave
function confirmBeforeLeave(message = 'You have unsaved changes. Are you sure you want to leave?') {
    let hasUnsavedChanges = false;

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('input', () => {
            hasUnsavedChanges = true;
        });

        form.addEventListener('submit', () => {
            hasUnsavedChanges = false;
        });
    });

    window.addEventListener('beforeunload', (e) => {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = message;
            return message;
        }
    });
}

// Copy to Clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(() => {
        showToast('Failed to copy', 'error');
    });
}

// Print Page
function printPage() {
    window.print();
}

// Export Table to CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => {
            return '"' + col.textContent.trim().replace(/"/g, '""') + '"';
        });
        csv.push(rowData.join(','));
    });

    const csvContent = '\uFEFF' + csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
}

// Initialize all enhancements
document.addEventListener('DOMContentLoaded', function () {
    // Add dark mode toggle button if not exists
    if (!document.querySelector('.dark-mode-toggle')) {
        const toggle = document.createElement('button');
        toggle.className = 'dark-mode-toggle';
        toggle.innerHTML = '🌙';
        toggle.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:999;padding:10px;border-radius:50%;border:none;cursor:pointer;';
        toggle.onclick = toggleDarkMode;
        document.body.appendChild(toggle);
    }

    // Show success/error messages from session
    if (window.sessionMessages) {
        window.sessionMessages.forEach(msg => {
            showToast(msg.message, msg.type);
        });
    }
});
