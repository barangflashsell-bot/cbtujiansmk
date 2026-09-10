// CBT LOCAL SERVER - OFFLINE VANILLA JAVASCRIPT
document.addEventListener('DOMContentLoaded', function () {
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

    // Auto dismiss or allow manual dismiss for alerts
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
