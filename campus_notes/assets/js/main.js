// =====================================================
// CAMPUS NOTES SHARING - Script Utama
// =====================================================

// Auto-dismiss: tutup alert otomatis setelah 4 detik
document.addEventListener('DOMContentLoaded', function () {
    // Cari semua alert yang bisa ditutup
    document.querySelectorAll('.alert.alert-success, .alert.alert-danger, .alert.alert-info').forEach(function (alert) {
        setTimeout(function () {
            // Pakai Bootstrap fade-out
            alert.classList.add('fade');
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity    = '0';
            setTimeout(function () { alert.remove(); }, 500);
        }, 4000); // 4000ms = 4 detik
    });
});
