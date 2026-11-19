/**
 * Smart Plant Dashboard - Vanilla JavaScript Application
 * Menggunakan Fetch API untuk auto-refresh data setiap 5 detik,
 * Chart.js untuk grafik tren kelembapan, SVG radial gauge, dan tema Dark/Light.
 */

// Konfigurasi Endpoint API
const API_BASE = '../backend/api';
const REFRESH_INTERVAL_SECONDS = 5;
const GAUGE_CIRCUMFERENCE = 2 * Math.PI * 65; // r = 65 -> ~408.4

// Status Lokal Aplikasi
let appState = {
    macAddress: '',
    operatingMode: 'AUTO',
    pumpStatus: 'OFF',
    currentMoisture: 0,
    isUpdating: false,
    countdown: REFRESH_INTERVAL_SECONDS,
    isDemoMode: false,
    hasRenderedOnce: false,
    lastLogs: []
};

// Chart.js & Bootstrap Toast Instances
let moistureChartInstance = null;
let toastBootstrap = null;

// Data Simulasi Fallback (Memastikan UI selalu tampil prima saat offline / belum konek DB)
const FALLBACK_DEMO_DATA = {
    device: {
        id: 1,
        mac_address: '24:6F:28:7A:B1:C0',
        name: 'ESP32 - Tanaman Monstera',
        operating_mode: 'AUTO',
        pump_status: 'OFF',
        last_seen: new Date().toISOString().slice(0, 19).replace('T', ' '),
        is_online: false,
        seconds_since_last_seen: 45
    },
    current_moisture: 52.8,
    last_moisture_time: new Date().toISOString().slice(0, 19).replace('T', ' '),
    logs: [
        { id: 10, moisture_level: 52.8, created_at: new Date(Date.now() - 60000).toISOString().slice(0, 19).replace('T', ' ') },
        { id: 9,  moisture_level: 55.4, created_at: new Date(Date.now() - 300000).toISOString().slice(0, 19).replace('T', ' ') },
        { id: 8,  moisture_level: 28.0, created_at: new Date(Date.now() - 600000).toISOString().slice(0, 19).replace('T', ' ') },
        { id: 7,  moisture_level: 31.4, created_at: new Date(Date.now() - 900000).toISOString().slice(0, 19).replace('T', ' ') },
        { id: 6,  moisture_level: 35.1, created_at: new Date(Date.now() - 1200000).toISOString().slice(0, 19).replace('T', ' ') },
        { id: 5,  moisture_level: 38.5, created_at: new Date(Date.now() - 1500000).toISOString().slice(0, 19).replace('T', ' ') },
        { id: 4,  moisture_level: 41.8, created_at: new Date(Date.now() - 1800000).toISOString().slice(0, 19).replace('T', ' ') },
        { id: 3,  moisture_level: 44.0, created_at: new Date(Date.now() - 2100000).toISOString().slice(0, 19).replace('T', ' ') },
        { id: 2,  moisture_level: 46.2, created_at: new Date(Date.now() - 2400000).toISOString().slice(0, 19).replace('T', ' ') },
        { id: 1,  moisture_level: 48.5, created_at: new Date(Date.now() - 2700000).toISOString().slice(0, 19).replace('T', ' ') }
    ],
    stats: {
        avg: 42.2,
        min: 28.0,
        max: 55.4,
        count: 10
    },
    server_time: new Date().toISOString().slice(0, 19).replace('T', ' ')
};

// Referensi Elemen DOM
const dom = {
    // Theme
    btnThemeToggle: document.getElementById('btnThemeToggle'),
    themeIcon: document.getElementById('themeIcon'),

    // Header & Badges
    deviceName: document.getElementById('deviceName'),
    onlineStatusBadge: document.getElementById('onlineStatusBadge'),
    pulseDot: document.getElementById('pulseDot'),
    onlineStatusText: document.getElementById('onlineStatusText'),
    countdownText: document.getElementById('countdownText'),
    btnManualRefresh: document.getElementById('btnManualRefresh'),
    connectionAlert: document.getElementById('connectionAlert'),
    btnRetryConnection: document.getElementById('btnRetryConnection'),

    // Autentikasi & Pengguna
    btnLogout: document.getElementById('btnLogout'),
    userBadge: document.getElementById('userBadge'),
    navUsername: document.getElementById('navUsername'),

    // Kartu Status & Gauge
    valMoisture: document.getElementById('valMoisture'),
    gaugeCircle: document.getElementById('gaugeCircle'),
    progressMoisture: document.getElementById('progressMoisture'),
    moistureStatusLabel: document.getElementById('moistureStatusLabel'),
    moistureUpdateTime: document.getElementById('moistureUpdateTime'),

    valPumpBadge: document.getElementById('valPumpBadge'),
    pumpIconBox: document.getElementById('pumpIconBox'),
    pumpIcon: document.getElementById('pumpIcon'),
    pumpDescription: document.getElementById('pumpDescription'),

    valModeBadge: document.getElementById('valModeBadge'),
    modeDescription: document.getElementById('modeDescription'),

    valLastSync: document.getElementById('valLastSync'),
    valLastSyncDiff: document.getElementById('valLastSyncDiff'),

    // Chart
    moistureChartCanvas: document.getElementById('moistureChart'),

    // Kontrol
    switchOperatingMode: document.getElementById('switchOperatingMode'),
    switchModeLabel: document.getElementById('switchModeLabel'),
    btnTogglePump: document.getElementById('btnTogglePump'),
    btnPumpText: document.getElementById('btnPumpText'),
    pumpSpinner: document.getElementById('pumpSpinner'),
    pumpBtnIcon: document.getElementById('pumpBtnIcon'),
    pumpControlHint: document.getElementById('pumpControlHint'),
    autoModeNotice: document.getElementById('autoModeNotice'),

    // Statistik & Info
    valMacAddress: document.getElementById('valMacAddress'),
    valAvgMoisture: document.getElementById('valAvgMoisture'),
    valMinMoisture: document.getElementById('valMinMoisture'),
    valMaxMoisture: document.getElementById('valMaxMoisture'),
    logCountBadge: document.getElementById('logCountBadge'),

    // Tabel Log
    logsTableBody: document.getElementById('logsTableBody'),

    // Toast
    liveToast: document.getElementById('liveToast'),
    toastText: document.getElementById('toastText'),
    toastIcon: document.getElementById('toastIcon')
};

// ============================================================================
// INISIALISASI APLIKASI
// ============================================================================
document.addEventListener('DOMContentLoaded', () => {
    // Inisialisasi Tema Gelap / Terang
    initThemeToggle();

    // 1. Cek apakah halaman saat ini adalah Halaman Login (login.html)
    if (document.getElementById('loginForm')) {
        initLoginPage();
        return;
    }

    // 2. Jika di Halaman Dashboard (index.html):
    checkAuthSession();

    if (dom.liveToast && typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        toastBootstrap = new bootstrap.Toast(dom.liveToast, { delay: 3500 });
    }

    if (dom.btnLogout) {
        dom.btnLogout.addEventListener('click', handleLogout);
    }

    if (dom.btnManualRefresh) {
        dom.btnManualRefresh.addEventListener('click', () => {
            appState.countdown = REFRESH_INTERVAL_SECONDS;
            fetchDashboardData(true);
        });
    }

    if (dom.btnRetryConnection) {
        dom.btnRetryConnection.addEventListener('click', () => {
            appState.countdown = REFRESH_INTERVAL_SECONDS;
            fetchDashboardData(true);
        });
    }

    if (dom.switchOperatingMode) {
        dom.switchOperatingMode.addEventListener('change', handleModeChange);
    }

    if (dom.btnTogglePump) {
        dom.btnTogglePump.addEventListener('click', handlePumpToggle);
    }

    // Ambil data pertama kali saat dimuat
    fetchDashboardData();

    // Mulai Timer Countdown & Auto-Refresh setiap 1 detik
    setInterval(handleTimerTick, 1000);
});

// ============================================================================
// PENGATURAN TEMA GELAP / TERANG
// ============================================================================
function initThemeToggle() {
    const savedTheme = localStorage.getItem('smartplant_theme') || 'light';
    applyTheme(savedTheme);

    if (dom.btnThemeToggle) {
        dom.btnThemeToggle.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            applyTheme(next);
            localStorage.setItem('smartplant_theme', next);
            if (appState.lastLogs.length > 0) {
                renderMoistureChart(appState.lastLogs);
            }
        });
    }
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    if (dom.themeIcon) {
        dom.themeIcon.className = theme === 'dark' ? 'bi bi-sun-fill text-warning' : 'bi bi-moon-stars';
    }
}

// ============================================================================
// TIMER COUNTDOWN & SINKRONISASI PERIODIK (5 DETIK)
// ============================================================================
function handleTimerTick() {
    if (appState.countdown > 1) {
        appState.countdown--;
        if (dom.countdownText) {
            dom.countdownText.textContent = `${appState.countdown}s`;
        }
    } else {
        appState.countdown = REFRESH_INTERVAL_SECONDS;
        if (dom.countdownText) {
            dom.countdownText.textContent = `${REFRESH_INTERVAL_SECONDS}s`;
        }
        fetchDashboardData(false);
    }
}

// ============================================================================
// AMBIL DATA DARI BACKEND (GET /backend/api/get_dashboard.php)
// ============================================================================
async function fetchDashboardData(isManual = false) {
    if (appState.isUpdating && !isManual) return;
    appState.isUpdating = true;

    try {
        if (isManual && dom.btnManualRefresh) {
            dom.btnManualRefresh.disabled = true;
            dom.btnManualRefresh.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Menyinkronkan...`;
        }

        const response = await fetch(`${API_BASE}/get_dashboard.php?t=${Date.now()}`, {
            credentials: 'include'
        });

        if (response.status === 401) {
            console.warn('Sesi tidak aktif atau telah berakhir. Mengalihkan ke login.html...');
            window.location.href = 'login.html';
            return;
        }

        const result = await response.json();

        if (response.ok && result.status === 'success' && result.data) {
            appState.isDemoMode = false;
            appState.hasRenderedOnce = true;
            if (dom.connectionAlert) {
                dom.connectionAlert.classList.add('d-none');
            }
            updateDashboardUI(result.data);
            if (isManual) {
                showToast('Data berhasil diperbarui dari server.', 'success');
            }
        } else {
            const msg = result?.message || `HTTP error ${response.status}`;
            console.warn('Gagal memproses data:', msg);
            handleOfflineFallback(msg);
            if (isManual) {
                showToast(msg, 'danger');
            }
        }

    } catch (error) {
        console.error('Koneksi backend error:', error);
        handleOfflineFallback(error.message);
        if (isManual) {
            showToast(error.message || 'Gagal terhubung ke backend atau database.', 'danger');
        }
    } finally {
        appState.isUpdating = false;
        if (isManual && dom.btnManualRefresh) {
            dom.btnManualRefresh.disabled = false;
            dom.btnManualRefresh.innerHTML = `<i class="bi bi-arrow-clockwise me-1"></i> Segarkan Sekarang`;
        }
    }
}

// ============================================================================
// PERBARUI SELURUH TAMPILAN DASHBOARD
// ============================================================================
function updateDashboardUI(data) {
    const { device, current_moisture, last_moisture_time, logs, stats } = data;

    // Simpan status lokal
    appState.macAddress = device.mac_address;
    appState.operatingMode = device.operating_mode;
    appState.pumpStatus = device.pump_status;
    appState.currentMoisture = current_moisture;
    appState.lastLogs = logs || [];

    // 1. Informasi Header Perangkat & Status Online ESP32
    dom.deviceName.textContent = device.name || 'ESP32 Smart Plant';
    dom.valMacAddress.textContent = device.mac_address || 'Tidak Diketahui';

    if (device.is_online) {
        dom.pulseDot.className = 'pulse-dot online';
        dom.onlineStatusBadge.className = 'badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill d-inline-flex align-items-center gap-2';
        dom.onlineStatusText.textContent = 'ESP32 Terhubung';
    } else {
        dom.pulseDot.className = 'pulse-dot offline';
        dom.onlineStatusBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill d-inline-flex align-items-center gap-2';
        dom.onlineStatusText.textContent = 'ESP32 Terputus';
    }

    // 2. Kartu & Gauge Kelembapan Tanah
    const moistureNum = Math.min(Math.max(parseFloat(current_moisture) || 0, 0), 100);
    const moistureVal = moistureNum.toFixed(1);
    dom.valMoisture.textContent = `${moistureVal}%`;
    dom.progressMoisture.style.width = `${moistureNum}%`;

    let moistLabel = '';
    let moistColorClass = '';
    let moistTextClass = '';
    let gaugeStrokeColor = '#10b981';

    if (moistureNum < 35) {
        moistLabel = 'Kering (Perlu Disiram)';
        moistColorClass = 'bg-danger';
        moistTextClass = 'text-danger';
        gaugeStrokeColor = '#f43f5e';
    } else if (moistureNum <= 70) {
        moistLabel = 'Lembab (Kondisi Ideal)';
        moistColorClass = 'bg-success';
        moistTextClass = 'text-success';
        gaugeStrokeColor = '#10b981';
    } else {
        moistLabel = 'Sangat Basah / Jenuh';
        moistColorClass = 'bg-info';
        moistTextClass = 'text-info';
        gaugeStrokeColor = '#0ea5e9';
    }

    if (dom.gaugeCircle) {
        const offset = GAUGE_CIRCUMFERENCE - (moistureNum / 100) * GAUGE_CIRCUMFERENCE;
        dom.gaugeCircle.style.strokeDashoffset = offset;
        dom.gaugeCircle.style.stroke = gaugeStrokeColor;
    }

    dom.progressMoisture.className = `progress-bar rounded-pill ${moistColorClass}`;
    dom.moistureStatusLabel.className = `fw-semibold ${moistTextClass}`;
    dom.moistureStatusLabel.innerHTML = `<i class="bi bi-circle-fill me-1" style="font-size: 0.55rem;"></i> ${moistLabel}`;
    dom.moistureUpdateTime.textContent = formatTimeOnly(last_moisture_time);

    // 3. Kartu Status Pompa
    if (device.pump_status === 'ON') {
        dom.valPumpBadge.className = 'badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill pump-active-badge';
        dom.valPumpBadge.innerHTML = `<i class="bi bi-droplet-fill me-1"></i> MENYALA (ON)`;
        dom.pumpIconBox.className = 'icon-tile tile-emerald';
        dom.pumpDescription.textContent = 'Pompa air sedang AKTIF mengalirkan air ke tanaman.';
    } else {
        dom.valPumpBadge.className = 'badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill';
        dom.valPumpBadge.innerHTML = `<i class="bi bi-power me-1"></i> MATI (OFF)`;
        dom.pumpIconBox.className = 'icon-tile tile-rose';
        dom.pumpDescription.textContent = 'Relay pompa air saat ini sedang mati (standby).';
    }

    // 4. Kartu Mode Operasi
    if (device.operating_mode === 'AUTO') {
        dom.valModeBadge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill';
        dom.valModeBadge.innerHTML = `<i class="bi bi-robot me-1"></i> OTOMATIS`;
        dom.modeDescription.textContent = 'Menyiram otomatis jika kelembapan < 35%.';
    } else {
        dom.valModeBadge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill';
        dom.valModeBadge.innerHTML = `<i class="bi bi-hand-index-thumb me-1"></i> MANUAL`;
        dom.modeDescription.textContent = 'Penyiraman dikendalikan manual oleh pengguna.';
    }

    // 5. Kartu Sinkronisasi Terakhir
    dom.valLastSync.textContent = formatTimeOnly(device.last_seen);
    const secondsAgo = device.seconds_since_last_seen;
    if (secondsAgo < 10) {
        dom.valLastSyncDiff.textContent = 'Baru saja disinkronkan';
    } else if (secondsAgo < 60) {
        dom.valLastSyncDiff.textContent = `${secondsAgo} detik yang lalu`;
    } else {
        const minsAgo = Math.floor(secondsAgo / 60);
        dom.valLastSyncDiff.textContent = `${minsAgo} menit yang lalu`;
    }

    // 6. Sinkronkan Elemen Kontrol
    syncControlsUI(device.operating_mode, device.pump_status);

    // 7. Statistik Cepat
    if (stats) {
        dom.valAvgMoisture.textContent = `${stats.avg}%`;
        dom.valMinMoisture.textContent = `${stats.min}%`;
        dom.valMaxMoisture.textContent = `${stats.max}%`;
    }

    // 8. Render Grafik Tren & Tabel 10 Riwayat Log Sensor
    renderMoistureChart(appState.lastLogs);
    renderLogsTable(appState.lastLogs);
}

// ============================================================================
// GRAFIK TREN KELEMBAPAN TANAH (CHART.JS)
// ============================================================================
function renderMoistureChart(logs) {
    if (!dom.moistureChartCanvas || typeof Chart === 'undefined') return;

    const ordered = [...(logs || [])].reverse();
    const labels = ordered.map(l => formatTimeOnly(l.created_at).slice(0, 5));
    const values = ordered.map(l => parseFloat(l.moisture_level));
    const thresholdLine = ordered.map(() => 35);

    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const gridColor = isDark ? 'rgba(148, 163, 184, 0.12)' : 'rgba(15, 23, 42, 0.06)';
    const tickColor = isDark ? '#94a3b8' : '#64748b';

    const ctx = dom.moistureChartCanvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 240);
    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
    gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

    if (moistureChartInstance) {
        moistureChartInstance.destroy();
    }

    moistureChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Kelembapan (%)',
                    data: values,
                    borderColor: '#10b981',
                    backgroundColor: gradient,
                    borderWidth: 2.5,
                    pointBackgroundColor: values.map(v => v < 35 ? '#f43f5e' : (v > 70 ? '#0ea5e9' : '#10b981')),
                    pointBorderColor: isDark ? '#111822' : '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4.5,
                    pointHoverRadius: 6.5,
                    tension: 0.35,
                    fill: true
                },
                {
                    label: 'Batas Kering (35%)',
                    data: thresholdLine,
                    borderColor: 'rgba(244, 63, 94, 0.65)',
                    borderWidth: 1.5,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 350 },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark ? '#1e293b' : '#0f172a',
                    titleColor: '#f8fafc',
                    bodyColor: '#e2e8f0',
                    padding: 10,
                    cornerRadius: 10,
                    displayColors: true
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: tickColor, font: { family: 'JetBrains Mono', size: 11 } }
                },
                y: {
                    min: 0,
                    max: 100,
                    grid: { color: gridColor },
                    ticks: {
                        color: tickColor,
                        stepSize: 25,
                        callback: val => `${val}%`,
                        font: { family: 'JetBrains Mono', size: 11 }
                    }
                }
            }
        }
    });
}

// ============================================================================
// SINKRONISASI KONTROL (TOGGLE MODE & TOMBOL POMPA)
// ============================================================================
function syncControlsUI(mode, pumpStatus) {
    const isManual = (mode === 'MANUAL');

    dom.switchOperatingMode.checked = isManual;
    dom.switchModeLabel.textContent = isManual ? 'Manual (Aktif)' : 'Otomatis (Aktif)';
    dom.switchModeLabel.className = isManual
        ? 'form-check-label fw-bold small text-warning'
        : 'form-check-label fw-bold small text-success';

    if (!isManual) {
        dom.btnTogglePump.disabled = true;
        dom.btnTogglePump.className = 'btn btn-pump-modern btn-pump-locked w-100';
        dom.btnPumpText.textContent = 'Pompa Terkunci (Mode Auto)';
        dom.pumpBtnIcon.className = 'bi bi-lock-fill me-1';
        dom.autoModeNotice.classList.remove('d-none');
        dom.pumpControlHint.innerHTML = 'Pompa dikendalikan secara otomatis oleh mikrokontroler ESP32.';
    } else {
        dom.btnTogglePump.disabled = false;
        dom.autoModeNotice.classList.add('d-none');
        dom.pumpControlHint.innerHTML = 'Klik tombol di samping untuk menyalakan atau mematikan pompa air.';

        if (pumpStatus === 'ON') {
            dom.btnTogglePump.className = 'btn btn-pump-modern btn-pump-off w-100';
            dom.btnPumpText.textContent = 'Matikan Pompa (OFF)';
            dom.pumpBtnIcon.className = 'bi bi-stop-circle me-1';
        } else {
            dom.btnTogglePump.className = 'btn btn-pump-modern btn-pump-on w-100';
            dom.btnPumpText.textContent = 'Nyalakan Pompa (ON)';
            dom.pumpBtnIcon.className = 'bi bi-play-circle me-1';
        }
    }
}

// ============================================================================
// EVENT HANDLER: PERUBAHAN MODE OPERASI (AUTO / MANUAL)
// ============================================================================
async function handleModeChange(event) {
    const newMode = event.target.checked ? 'MANUAL' : 'AUTO';

    if (appState.isDemoMode) {
        FALLBACK_DEMO_DATA.device.operating_mode = newMode;
        if (newMode === 'AUTO') {
            FALLBACK_DEMO_DATA.device.pump_status = FALLBACK_DEMO_DATA.current_moisture < 35 ? 'ON' : 'OFF';
        }
        updateDashboardUI(FALLBACK_DEMO_DATA);
        updateOfflineUI();
        showToast(`Mode operasi diubah ke ${newMode === 'AUTO' ? 'Otomatis' : 'Manual'} (Simulasi Mode Demo).`, 'warning');
        return;
    }

    dom.switchOperatingMode.disabled = true;

    try {
        const response = await fetch(`${API_BASE}/update_mode.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                mac_address: appState.macAddress,
                operating_mode: newMode
            })
        });

        if (response.status === 401) {
            window.location.href = 'login.html';
            return;
        }

        const result = await response.json();

        if (response.ok && result.status === 'success') {
            appState.operatingMode = newMode;
            if (result.data && result.data.mac_address) {
                appState.macAddress = result.data.mac_address;
            }
            showToast(`Mode operasi berhasil diubah ke ${newMode === 'AUTO' ? 'Otomatis' : 'Manual'}.`, 'success');
            fetchDashboardData();
        } else {
            throw new Error(result.message || 'Gagal mengubah mode.');
        }

    } catch (error) {
        console.error('Error saat update mode:', error);
        showToast(error.message, 'danger');
        dom.switchOperatingMode.checked = (appState.operatingMode === 'MANUAL');
    } finally {
        dom.switchOperatingMode.disabled = false;
    }
}

// ============================================================================
// EVENT HANDLER: TOGGLE STATUS POMPA MANUAL (ON / OFF)
// ============================================================================
async function handlePumpToggle() {
    if (appState.operatingMode !== 'MANUAL') {
        showToast('Pompa hanya dapat dikendalikan dalam Mode Manual.', 'warning');
        return;
    }

    const nextStatus = (appState.pumpStatus === 'ON') ? 'OFF' : 'ON';

    if (appState.isDemoMode) {
        FALLBACK_DEMO_DATA.device.pump_status = nextStatus;
        updateDashboardUI(FALLBACK_DEMO_DATA);
        updateOfflineUI();
        showToast(`Pompa berhasil ${nextStatus === 'ON' ? 'dinyalakan (ON)' : 'dimatikan (OFF)'} (Simulasi Mode Demo).`, 'warning');
        return;
    }

    dom.btnTogglePump.disabled = true;
    dom.pumpSpinner.classList.remove('d-none');

    try {
        const response = await fetch(`${API_BASE}/update_pump.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                mac_address: appState.macAddress,
                pump_status: nextStatus
            })
        });

        if (response.status === 401) {
            window.location.href = 'login.html';
            return;
        }

        const result = await response.json();

        if (response.ok && result.status === 'success') {
            appState.pumpStatus = nextStatus;
            if (result.data && result.data.mac_address) {
                appState.macAddress = result.data.mac_address;
            }
            showToast(`Pompa berhasil ${nextStatus === 'ON' ? 'dinyalakan (ON)' : 'dimatikan (OFF)'}.`, 'success');
            fetchDashboardData();
        } else {
            throw new Error(result.message || 'Gagal mengubah status pompa.');
        }

    } catch (error) {
        console.error('Error saat update pompa:', error);
        showToast(error.message, 'danger');
    } finally {
        dom.btnTogglePump.disabled = false;
        dom.pumpSpinner.classList.add('d-none');
    }
}

// ============================================================================
// RENDER TABEL 10 RIWAYAT LOG TERAKHIR
// ============================================================================
function renderLogsTable(logs) {
    if (!logs || logs.length === 0) {
        dom.logsTableBody.innerHTML = `
            <tr>
                <td colspan="5" class="py-4 text-muted">
                    <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                    Belum ada data riwayat sensor yang tercatat.
                </td>
            </tr>
        `;
        dom.logCountBadge.textContent = '0 Data';
        return;
    }

    dom.logCountBadge.textContent = `${logs.length} Data Tersedia`;

    let html = '';
    logs.forEach((log, index) => {
        const moistureNum = parseFloat(log.moisture_level);
        const moisture = moistureNum.toFixed(1);
        let conditionText = '';
        let badgeColor = '';
        let barColor = '';

        if (moistureNum < 35) {
            conditionText = 'Kering';
            badgeColor = 'bg-danger-subtle text-danger border-danger-subtle';
            barColor = 'bg-danger';
        } else if (moistureNum <= 70) {
            conditionText = 'Lembab';
            badgeColor = 'bg-success-subtle text-success border-success-subtle';
            barColor = 'bg-success';
        } else {
            conditionText = 'Basah';
            badgeColor = 'bg-info-subtle text-info border-info-subtle';
            barColor = 'bg-info';
        }

        html += `
            <tr>
                <td class="fw-bold font-mono text-muted">${index + 1}</td>
                <td class="text-start">
                    <div class="fw-semibold">${formatDateTime(log.created_at)}</div>
                    <small class="text-muted">${getRelativeTime(log.created_at)}</small>
                </td>
                <td>
                    <span class="font-mono fw-bold">${moisture}%</span>
                </td>
                <td>
                    <div class="progress rounded-pill" style="height: 7px;">
                        <div class="progress-bar rounded-pill ${barColor}" style="width: ${Math.min(Math.max(moistureNum, 0), 100)}%"></div>
                    </div>
                </td>
                <td>
                    <span class="badge border rounded-pill px-3 py-2 ${badgeColor}">${conditionText}</span>
                </td>
            </tr>
        `;
    });

    dom.logsTableBody.innerHTML = html;
}

// ============================================================================
// FORMATTER WAKTU & TANGGAL (INDONESIAN LOCALE)
// ============================================================================
function formatDateTime(dateStr) {
    if (!dateStr) return '--';
    const date = new Date(dateStr.replace(/-/g, '/'));
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    });
}

function formatTimeOnly(dateStr) {
    if (!dateStr) return '--:--:--';
    const date = new Date(dateStr.replace(/-/g, '/'));
    return date.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    });
}

function getRelativeTime(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr.replace(/-/g, '/'));
    const seconds = Math.floor((new Date() - date) / 1000);

    if (seconds < 10) return 'Baru saja';
    if (seconds < 60) return `${seconds} detik lalu`;
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes} menit lalu`;
    const hours = Math.floor(minutes / 60);
    return `${hours} jam lalu`;
}

// ============================================================================
// PENANGANAN FALLBACK DEMO / OFFLINE
// ============================================================================
function handleOfflineFallback(errorMessage) {
    appState.isDemoMode = true;

    if (dom.connectionAlert) {
        dom.connectionAlert.classList.remove('d-none');
    }

    if (!appState.hasRenderedOnce) {
        appState.hasRenderedOnce = true;
        updateDashboardUI(FALLBACK_DEMO_DATA);
    }

    updateOfflineUI(errorMessage);
}

function updateOfflineUI() {
    dom.pulseDot.className = 'pulse-dot offline';
    dom.onlineStatusBadge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill d-inline-flex align-items-center gap-2';
    dom.onlineStatusText.textContent = 'Mode Demo (Offline)';
    dom.valLastSyncDiff.textContent = 'Menampilkan data simulasi visual';
}

// ============================================================================
// NOTIFIKASI TOAST
// ============================================================================
function showToast(message, type = 'success') {
    if (!toastBootstrap) return;

    dom.toastText.textContent = message;

    if (type === 'success') {
        dom.toastIcon.className = 'bi bi-check-circle-fill fs-5 text-success';
    } else if (type === 'danger') {
        dom.toastIcon.className = 'bi bi-exclamation-triangle-fill fs-5 text-danger';
    } else if (type === 'warning') {
        dom.toastIcon.className = 'bi bi-info-circle-fill fs-5 text-warning';
    }

    toastBootstrap.show();
}

// ============================================================================
// LOGIKA AUTENTIKASI & MANAJEMEN SESI (AUTH GUARD & SESSION)
// ============================================================================
async function checkAuthSession() {
    try {
        const response = await fetch(`${API_BASE}/check_session.php?t=${Date.now()}`, {
            credentials: 'include'
        });

        if (response.status === 401) {
            console.warn('Sesi tidak aktif atau telah berakhir. Mengalihkan ke login.html...');
            window.location.href = 'login.html';
            return false;
        }

        let result;
        try {
            result = await response.json();
        } catch (_) {
            return false;
        }

        if (response.ok && result.authenticated) {
            if (dom.userBadge && dom.navUsername) {
                dom.navUsername.textContent = result.user?.username || 'admin';
                dom.userBadge.classList.remove('d-none');
            }
            return true;
        } else {
            window.location.href = 'login.html';
            return false;
        }
    } catch (err) {
        console.warn('Gagal memvalidasi sesi via check_session (server offline):', err);
        return null;
    }
}

async function handleLogout() {
    if (dom.btnLogout) {
        dom.btnLogout.disabled = true;
        dom.btnLogout.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Keluar...`;
    }

    try {
        await fetch(`${API_BASE}/logout.php`, {
            method: 'POST',
            credentials: 'include'
        });
    } catch (err) {
        console.warn('Logout request warning:', err);
    } finally {
        window.location.href = 'login.html';
    }
}

function initLoginPage() {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const btnLogin = document.getElementById('btnLogin');
    const loginBtnSpinner = document.getElementById('loginBtnSpinner');
    const loginBtnIcon = document.getElementById('loginBtnIcon');
    const loginBtnText = document.getElementById('loginBtnText');
    const loginAlert = document.getElementById('loginAlert');
    const loginAlertText = document.getElementById('loginAlertText');
    const btnTogglePassword = document.getElementById('btnTogglePassword');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');
    const btnAutofillDemo = document.getElementById('btnAutofillDemo');

    // 1. Cek jika sesi sudah aktif, langsung alihkan ke index.html
    fetch(`${API_BASE}/check_session.php?t=${Date.now()}`, { credentials: 'include' })
        .then(res => res.json())
        .then(data => {
            if (data && data.authenticated) {
                window.location.href = 'index.html';
            }
        })
        .catch(() => {});

    // 2. Tombol Show/Hide Password
    if (btnTogglePassword && passwordInput) {
        btnTogglePassword.addEventListener('click', () => {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            if (togglePasswordIcon) {
                togglePasswordIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
        });
    }

    // 3. Tombol Autofill Akun Demo
    if (btnAutofillDemo && usernameInput && passwordInput) {
        btnAutofillDemo.addEventListener('click', () => {
            usernameInput.value = 'admin';
            passwordInput.value = 'password123';
            hideLoginError();
            usernameInput.focus();
        });
    }

    // 4. Handler Submit Form Login
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const username = (usernameInput.value || '').trim();
            const password = passwordInput.value || '';

            if (!username || !password) {
                showLoginError('Username dan password wajib diisi.');
                return;
            }

            btnLogin.disabled = true;
            if (loginBtnSpinner) loginBtnSpinner.classList.remove('d-none');
            if (loginBtnIcon) loginBtnIcon.classList.add('d-none');
            if (loginBtnText) loginBtnText.textContent = 'Memverifikasi...';
            hideLoginError();

            try {
                const response = await fetch(`${API_BASE}/login.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ username, password })
                });

                let result;
                try {
                    result = await response.json();
                } catch (_) {
                    throw new Error(`Respon server tidak valid (HTTP ${response.status})`);
                }

                if (response.ok && result.status === 'success') {
                    if (loginBtnText) loginBtnText.textContent = 'Berhasil! Mengalihkan...';
                    setTimeout(() => {
                        window.location.href = 'index.html';
                    }, 400);
                } else {
                    showLoginError(result.message || 'Username atau password salah.');
                    resetLoginButton();
                }
            } catch (err) {
                console.error('Login error:', err);
                showLoginError(err.message || 'Gagal terhubung ke backend server.');
                resetLoginButton();
            }
        });
    }

    function resetLoginButton() {
        btnLogin.disabled = false;
        if (loginBtnSpinner) loginBtnSpinner.classList.add('d-none');
        if (loginBtnIcon) loginBtnIcon.classList.remove('d-none');
        if (loginBtnText) loginBtnText.textContent = 'Masuk ke Dashboard';
    }

    function showLoginError(msg) {
        if (loginAlert && loginAlertText) {
            loginAlertText.textContent = msg;
            loginAlert.classList.remove('d-none');
        }
    }

    function hideLoginError() {
        if (loginAlert) {
            loginAlert.classList.add('d-none');
        }
    }
}
