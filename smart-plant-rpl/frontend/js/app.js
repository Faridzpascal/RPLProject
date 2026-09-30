/**
 * Smart Plant Dashboard - Vanilla JavaScript Application
 * Menggunakan Fetch API untuk auto-refresh data setiap 5 detik
 * dan menangani interaksi kontrol mode & pompa secara real-time.
 */

// Konfigurasi Endpoint API
const API_BASE = '../backend/api';
const REFRESH_INTERVAL_SECONDS = 5;

// Status Lokal Aplikasi
let appState = {
    macAddress: '',
    operatingMode: 'AUTO',
    pumpStatus: 'OFF',
    currentMoisture: 0,
    isUpdating: false,
    countdown: REFRESH_INTERVAL_SECONDS
};

// Referensi Elemen DOM
const dom = {
    // Header & Badges
    deviceName: document.getElementById('deviceName'),
    onlineStatusBadge: document.getElementById('onlineStatusBadge'),
    pulseDot: document.getElementById('pulseDot'),
    onlineStatusText: document.getElementById('onlineStatusText'),
    countdownText: document.getElementById('countdownText'),
    btnManualRefresh: document.getElementById('btnManualRefresh'),

    // Kartu Status
    valMoisture: document.getElementById('valMoisture'),
    progressMoisture: document.getElementById('progressMoisture'),
    moistureStatusLabel: document.getElementById('moistureStatusLabel'),
    moistureUpdateTime: document.getElementById('moistureUpdateTime'),

    valPumpBadge: document.getElementById('valPumpBadge'),
    pumpIconBox: document.getElementById('pumpIconBox'),
    pumpDescription: document.getElementById('pumpDescription'),

    valModeBadge: document.getElementById('valModeBadge'),
    modeDescription: document.getElementById('modeDescription'),

    valLastSync: document.getElementById('valLastSync'),
    valLastSyncDiff: document.getElementById('valLastSyncDiff'),

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

// Bootstrap Toast Instance
let toastBootstrap = null;

// ============================================================================
// INISIALISASI APLIKASI
// ============================================================================
document.addEventListener('DOMContentLoaded', () => {
    // Inisialisasi Toast Bootstrap
    if (dom.liveToast) {
        toastBootstrap = new bootstrap.Toast(dom.liveToast, { delay: 3500 });
    }

    // Pasang Event Listener
    dom.btnManualRefresh.addEventListener('click', () => {
        appState.countdown = REFRESH_INTERVAL_SECONDS;
        fetchDashboardData(true);
    });

    dom.switchOperatingMode.addEventListener('change', handleModeChange);
    dom.btnTogglePump.addEventListener('click', handlePumpToggle);

    // Ambil data pertama kali saat dimuat
    fetchDashboardData();

    // Mulai Timer Countdown & Auto-Refresh setiap 1 detik
    setInterval(handleTimerTick, 1000);
});

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

    try {
        if (isManual) {
            dom.btnManualRefresh.disabled = true;
            dom.btnManualRefresh.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Menyinkronkan...`;
        }

        const response = await fetch(`${API_BASE}/get_dashboard.php?t=${Date.now()}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.status === 'success' && result.data) {
            updateDashboardUI(result.data);
            if (isManual) {
                showToast('Data berhasil diperbarui dari server.', 'success');
            }
        } else {
            console.warn('Gagal memproses data:', result.message);
        }

    } catch (error) {
        console.error('Koneksi backend error:', error);
        updateOfflineUI(error.message);
        if (isManual) {
            showToast('Gagal terhubung ke backend atau database.', 'danger');
        }
    } finally {
        if (isManual) {
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

    // 1. Informasi Header Perangkat & Status Online ESP32
    dom.deviceName.textContent = device.name || 'ESP32 Smart Plant';
    dom.valMacAddress.textContent = device.mac_address || 'Tidak Diketahui';

    if (device.is_online) {
        dom.pulseDot.className = 'pulse-dot online me-1';
        dom.onlineStatusBadge.className = 'badge bg-success px-3 py-2 rounded-pill shadow-sm';
        dom.onlineStatusText.textContent = 'ESP32 Terhubung';
    } else {
        dom.pulseDot.className = 'pulse-dot offline me-1';
        dom.onlineStatusBadge.className = 'badge bg-danger px-3 py-2 rounded-pill shadow-sm';
        dom.onlineStatusText.textContent = 'ESP32 Terputus';
    }

    // 2. Kartu Kelembapan Tanah
    const moistureVal = parseFloat(current_moisture).toFixed(1);
    dom.valMoisture.textContent = `${moistureVal}%`;
    dom.progressMoisture.style.width = `${Math.min(Math.max(moistureVal, 0), 100)}%`;

    // Klasifikasi Kondisi Kelembapan
    let moistLabel = '';
    let moistColorClass = '';
    let moistBadgeClass = '';

    if (moistureVal < 35) {
        moistLabel = 'Kering (Perlu Disiram)';
        moistColorClass = 'bg-danger';
        moistBadgeClass = 'text-danger';
    } else if (moistureVal <= 70) {
        moistLabel = 'Lembab (Kondisi Ideal)';
        moistColorClass = 'bg-success';
        moistBadgeClass = 'text-success';
    } else {
        moistLabel = 'Sangat Basah / Jenuh';
        moistColorClass = 'bg-info';
        moistBadgeClass = 'text-info';
    }

    dom.progressMoisture.className = `progress-bar ${moistColorClass}`;
    dom.moistureStatusLabel.className = `fw-semibold ${moistBadgeClass}`;
    dom.moistureStatusLabel.innerHTML = `<i class="bi bi-circle-fill me-1" style="font-size: 0.6rem;"></i> ${moistLabel}`;
    dom.moistureUpdateTime.textContent = formatDateTime(last_moisture_time);

    // 3. Kartu Status Pompa
    if (device.pump_status === 'ON') {
        dom.valPumpBadge.className = 'badge bg-success fs-6 px-3 py-2 rounded-pill pump-active-badge';
        dom.valPumpBadge.innerHTML = `<i class="bi bi-droplet-fill me-1"></i> MENYALA (ON)`;
        dom.pumpIconBox.className = 'card-icon-box icon-pump-on shadow-sm';
        dom.pumpIcon.className = 'bi bi-water text-success';
        dom.pumpDescription.textContent = 'Pompa air sedang AKTIF mengalirkan air ke tanaman.';
    } else {
        dom.valPumpBadge.className = 'badge bg-secondary fs-6 px-3 py-2 rounded-pill';
        dom.valPumpBadge.innerHTML = `<i class="bi bi-power me-1"></i> MATI (OFF)`;
        dom.pumpIconBox.className = 'card-icon-box icon-pump-off shadow-sm';
        dom.pumpIcon.className = 'bi bi-water text-danger';
        dom.pumpDescription.textContent = 'Relay pompa air saat ini sedang mati (standby).';
    }

    // 4. Kartu Mode Operasi
    if (device.operating_mode === 'AUTO') {
        dom.valModeBadge.className = 'badge bg-primary fs-6 px-3 py-2 rounded-pill';
        dom.valModeBadge.innerHTML = `<i class="bi bi-robot me-1"></i> OTOMATIS (AUTO)`;
        dom.modeDescription.textContent = 'Sistem menyiram otomatis jika kelembapan < 35%.';
    } else {
        dom.valModeBadge.className = 'badge bg-warning text-dark fs-6 px-3 py-2 rounded-pill';
        dom.valModeBadge.innerHTML = `<i class="bi bi-hand-index-thumb me-1"></i> MANUAL`;
        dom.modeDescription.textContent = 'Penyiraman dikendalikan manual oleh pengguna via tombol.';
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

    // 8. Isi Tabel 10 Riwayat Log Sensor
    renderLogsTable(logs);
}

// ============================================================================
// SINKRONISASI KONTROL (TOGGLE MODE & TOMBOL POMPA)
// ============================================================================
function syncControlsUI(mode, pumpStatus) {
    const isManual = (mode === 'MANUAL');

    // Update switch toggle: Checked = MANUAL, Unchecked = OTOMATIS
    dom.switchOperatingMode.checked = isManual;
    dom.switchModeLabel.textContent = isManual ? 'Mode Manual (Aktif)' : 'Mode Otomatis (Aktif)';
    dom.switchModeLabel.className = isManual ? 'form-check-label fw-bold ms-2 text-warning' : 'form-check-label fw-bold ms-2 text-primary';

    // Logika Tombol Pompa
    if (!isManual) {
        // Mode AUTO: Tombol Pompa Dinonaktifkan (Disabled)
        dom.btnTogglePump.disabled = true;
        dom.btnTogglePump.className = 'btn btn-pump-action btn-secondary w-100 opacity-50';
        dom.btnPumpText.textContent = 'Pompa Terkunci (Mode Auto)';
        dom.pumpBtnIcon.className = 'bi bi-lock-fill me-1';
        dom.autoModeNotice.classList.remove('d-none');
        dom.pumpControlHint.innerHTML = 'Pompa dikendalikan secara otomatis oleh mikrokontroler ESP32.';
    } else {
        // Mode MANUAL: Tombol Pompa Aktif (Enabled)
        dom.btnTogglePump.disabled = false;
        dom.autoModeNotice.classList.add('d-none');
        dom.pumpControlHint.innerHTML = 'Klik tombol di bawah untuk mengaktifkan atau mematikan pompa air.';

        if (pumpStatus === 'ON') {
            dom.btnTogglePump.className = 'btn btn-pump-action btn-danger w-100';
            dom.btnPumpText.textContent = 'Matikan Pompa (OFF)';
            dom.pumpBtnIcon.className = 'bi bi-stop-circle me-1';
        } else {
            dom.btnTogglePump.className = 'btn btn-pump-action btn-success w-100';
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
    dom.switchOperatingMode.disabled = true;

    try {
        const response = await fetch(`${API_BASE}/update_mode.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                mac_address: appState.macAddress,
                operating_mode: newMode
            })
        });

        const result = await response.json();

        if (response.ok && result.status === 'success') {
            appState.operatingMode = newMode;
            showToast(`Mode operasi berhasil diubah ke ${newMode === 'AUTO' ? 'Otomatis' : 'Manual'}.`, 'success');
            fetchDashboardData();
        } else {
            throw new Error(result.message || 'Gagal mengubah mode.');
        }

    } catch (error) {
        console.error('Error saat update mode:', error);
        showToast(error.message, 'danger');
        // Kembalikan switch ke kondisi semula
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

    // Tampilkan loading spinner pada tombol
    dom.btnTogglePump.disabled = true;
    dom.pumpSpinner.classList.remove('d-none');

    try {
        const response = await fetch(`${API_BASE}/update_pump.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                mac_address: appState.macAddress,
                pump_status: nextStatus
            })
        });

        const result = await response.json();

        if (response.ok && result.status === 'success') {
            appState.pumpStatus = nextStatus;
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
        const moisture = parseFloat(log.moisture_level).toFixed(1);
        let conditionText = '';
        let badgeColor = '';
        let barColor = '';

        if (moisture < 35) {
            conditionText = 'Kering';
            badgeColor = 'bg-danger-subtle text-danger border-danger-subtle';
            barColor = 'bg-danger';
        } else if (moisture <= 70) {
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
                <td class="fw-bold text-muted">${index + 1}</td>
                <td class="text-start">
                    <div class="fw-semibold text-dark">${formatDateTime(log.created_at)}</div>
                    <small class="text-muted">${getRelativeTime(log.created_at)}</small>
                </td>
                <td>
                    <span class="fs-6 fw-bold text-dark">${moisture}%</span>
                </td>
                <td>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar ${barColor}" style="width: ${Math.min(moisture, 100)}%"></div>
                    </div>
                </td>
                <td>
                    <span class="badge border px-3 py-2 ${badgeColor}">${conditionText}</span>
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
// UI KETIKA BACKEND / DATABASE TERPUTUS
// ============================================================================
function updateOfflineUI(errorMessage) {
    dom.pulseDot.className = 'pulse-dot offline me-1';
    dom.onlineStatusBadge.className = 'badge bg-danger px-3 py-2 rounded-pill shadow-sm';
    dom.onlineStatusText.textContent = 'Server Offline';
    dom.valLastSyncDiff.textContent = 'Gagal terhubung ke database';
}

// ============================================================================
// NOTIFIKASI TOAST
// ============================================================================
function showToast(message, type = 'success') {
    if (!toastBootstrap) return;

    dom.toastText.textContent = message;
    
    if (type === 'success') {
        dom.toastIcon.className = 'bi bi-check-circle-fill fs-5 text-success me-2';
        dom.liveToast.className = 'toast align-items-center text-bg-light border-0 shadow-lg';
    } else if (type === 'danger') {
        dom.toastIcon.className = 'bi bi-exclamation-triangle-fill fs-5 text-danger me-2';
        dom.liveToast.className = 'toast align-items-center text-bg-light border-0 shadow-lg';
    } else if (type === 'warning') {
        dom.toastIcon.className = 'bi bi-info-circle-fill fs-5 text-warning me-2';
        dom.liveToast.className = 'toast align-items-center text-bg-light border-0 shadow-lg';
    }

    toastBootstrap.show();
}
