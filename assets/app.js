// ============================================================
// DENTAL PORTAL — App JS
// ============================================================

// Sidebar toggle
const sidebar  = document.querySelector('.sidebar');
const overlay  = document.querySelector('.sidebar-overlay');
const toggleBtn = document.querySelector('.topbar-toggle');

if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    });
}
if (overlay) {
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    });
}

// Set active nav item
const currentPath = window.location.pathname.split('/').pop();
document.querySelectorAll('.nav-item').forEach(item => {
    const href = item.getAttribute('href');
    if (href && (href === currentPath || href.includes(currentPath))) {
        item.classList.add('active');
    }
});

// Toast notification
function showToast(msg, type = 'default') {
    const container = document.querySelector('.toast-container') || (() => {
        const c = document.createElement('div');
        c.className = 'toast-container';
        document.body.appendChild(c);
        return c;
    })();

    const toast = document.createElement('div');
    toast.className = `toast-dp ${type}`;
    const icon = type === 'success' ? 'bi-check-circle-fill' : type === 'error' ? 'bi-exclamation-circle-fill' : 'bi-info-circle-fill';
    toast.innerHTML = `<i class="bi ${icon}"></i> ${msg}`;
    container.appendChild(toast);
    setTimeout(() => { toast.remove(); }, 3500);
}

// Check URL for flash message
const urlParams = new URLSearchParams(window.location.search);
const msg = urlParams.get('msg');
if (msg) {
    showToast(decodeURIComponent(msg), 'success');
    // Clean URL
    const url = new URL(window.location);
    url.searchParams.delete('msg');
    window.history.replaceState({}, '', url);
}

// Search table rows
function initTableSearch(inputId, rowClass) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('input', function() {
        const term = this.value.toLowerCase();
        document.querySelectorAll('.' + rowClass).forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
        document.querySelectorAll('.mobile-client-card').forEach(card => {
            card.style.display = card.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    });
}
initTableSearch('tableSearch', 'data-row');

// Confirm delete
document.querySelectorAll('.confirm-delete').forEach(btn => {
    btn.addEventListener('click', e => {
        if (!confirm('Are you sure you want to delete this record? This cannot be undone.')) {
            e.preventDefault();
        }
    });
});

// Auto-resize textarea
document.querySelectorAll('textarea.auto-resize').forEach(ta => {
    ta.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 200) + 'px';
    });
});

// ============================================================
// PWA Install Prompt
// ============================================================
let deferredPrompt;
const pwaBanner = document.querySelector('.pwa-banner');

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    if (pwaBanner) {
        setTimeout(() => pwaBanner.classList.add('show'), 2000);
    }
});

document.querySelector('.pwa-install-btn')?.addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    const result = await deferredPrompt.userChoice;
    deferredPrompt = null;
    if (pwaBanner) pwaBanner.classList.remove('show');
});

document.querySelector('.pwa-dismiss')?.addEventListener('click', () => {
    if (pwaBanner) pwaBanner.classList.remove('show');
});
