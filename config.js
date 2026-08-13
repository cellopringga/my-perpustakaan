// ==========================================
// CONFIGURATION & SESSION MANAGEMENT (JS)
// ==========================================

// 1. Simulasi Data State / LocalStorage (Pengganti Database MySQL & PHP Session)
if (!localStorage.getItem('user_session')) {
    // Session default saat pertama kali dimuat
    localStorage.setItem('user_session', JSON.stringify({
        is_logged_in: false,
        user_id: null,
        role: null // 'admin' atau 'member'
    }));
}

// 2. Fungsi Cek Login
function isLoggedIn() {
    const session = JSON.parse(localStorage.getItem('user_session'));
    return session && session.is_logged_in === true && session.user_id !== null;
}

// 3. Fungsi Cek Admin
function isAdmin() {
    const session = JSON.parse(localStorage.getItem('user_session'));
    return isLoggedIn() && session.role === 'admin';
}

// 4. Fungsi Redirect Jika Belum Login
function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        window.location.href = "index.html"; // Diarahkan ke index.html
    }
}

// 5. Fungsi Redirect Jika Bukan Admin
function redirectIfNotAdmin() {
    redirectIfNotLoggedIn();
    if (!isAdmin()) {
        window.location.href = "dashboard.html"; // Diarahkan ke dashboard.html
    }
}

// 6. Fungsi Logout
function logout() {
    const session = JSON.parse(localStorage.getItem('user_session'));
    
    // Simulasi pencatatan Log Aktivitas ke LocalStorage (Pengganti tabel log_aktivitas)
    if (session && session.user_id) {
        let logs = JSON.parse(localStorage.getItem('log_aktivitas')) || [];
        logs.push({
            user_id: session.user_id,
            aktivitas: 'Logout',
            deskripsi: 'User logout dari sistem',
            waktu: new Date().toISOString()
        });
        localStorage.setItem('log_aktivitas', JSON.stringify(logs));
    }

    // Clear session
    localStorage.removeItem('user_session');
    
    // Redirect ke halaman login/index
    window.location.href = "index.html";
}
