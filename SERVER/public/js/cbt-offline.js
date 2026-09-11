// CBT LOCAL SERVER - MODERN VANILLA JAVASCRIPT & THEME CONTROLLER

// 1. Theme Management Functions
function setAppTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('cbt_theme', theme);
    updateThemeButtons(theme);
}

function updateThemeButtons(theme) {
    const btnLight = document.getElementById('btnThemeLight');
    const btnDark = document.getElementById('btnThemeDark');
    if (!btnLight || !btnDark) return;
    if (theme === 'dark') {
        btnLight.classList.remove('active');
        btnDark.classList.add('active');
    } else {
        btnLight.classList.add('active');
        btnDark.classList.remove('active');
    }
}

// 2. Clipboard Copy Helper
function copyServerAddress(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(showCopyToast);
    } else {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showCopyToast();
        } catch (err) {
            console.error('Fallback: Gagal menyalin', err);
        }
        document.body.removeChild(textArea);
    }
}

function showCopyToast() {
    let toast = document.getElementById('cbtCopyToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'cbtCopyToast';
        toast.style.position = 'fixed';
        toast.style.bottom = '24px';
        toast.style.right = '24px';
        toast.style.backgroundColor = 'var(--primary)';
        toast.style.color = '#ffffff';
        toast.style.padding = '10px 18px';
        toast.style.borderRadius = '8px';
        toast.style.boxShadow = '0 4px 14px rgba(0,0,0,0.2)';
        toast.style.fontSize = '13px';
        toast.style.fontWeight = '700';
        toast.style.zIndex = '9999';
        toast.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
        toast.style.display = 'flex';
        toast.style.alignItems = 'center';
        toast.style.gap = '8px';
        toast.innerHTML = '<span>✓</span> <span>Alamat Server Berhasil Disalin!</span>';
        document.body.appendChild(toast);
    }
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(8px)';
    }, 2500);
}

// 3. QR Modal Helper
function toggleQrModal(show) {
    const modal = document.getElementById('cbtQrModal');
    if (!modal) return;
    if (show) {
        modal.classList.add('active');
    } else {
        modal.classList.remove('active');
    }
}

// 4. Initialization
document.addEventListener('DOMContentLoaded', function () {
    // Sync theme buttons on load
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    updateThemeButtons(currentTheme);

    // Sidebar Mobile Toggle
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (toggleBtn && sidebar && overlay) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    // Auto dismiss for alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        alert.addEventListener('click', function () {
            alert.style.opacity = '0';
            setTimeout(function () {
                alert.remove();
            }, 200);
        });
    });
});
