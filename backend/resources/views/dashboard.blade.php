<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Plant IoT - Monitoring & Otomatisasi Penyiraman</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --bg-body: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --primary-green: #10b981;
            --primary-green-dark: #059669;
            --primary-blue: #3b82f6;
            --danger-red: #ef4444;
            --warning-amber: #f59e0b;
            --border-color: #e2e8f0;
            --radius-xl: 18px;
            --shadow-subtle: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
            --shadow-hover: 0 10px 25px -4px rgba(15, 23, 42, 0.1);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
        }

        .navbar-custom {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
        }

        .brand-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 8px 12px;
            border-radius: 12px;
            font-weight: 700;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            box-shadow: var(--shadow-subtle);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .stat-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        .icon-box {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .icon-box-green { background: #d1fae5; color: #059669; }
        .icon-box-blue { background: #dbeafe; color: #2563eb; }
        .icon-box-purple { background: #ede9fe; color: #7c3aed; }
        .icon-box-amber { background: #fef3c7; color: #d97706; }

        .stat-title {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 0.4rem;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1.2;
            color: var(--text-main);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .badge-dry { background: #fee2e2; color: #dc2626; }
        .badge-moderate { background: #fef3c7; color: #d97706; }
        .badge-good { background: #d1fae5; color: #059669; }

        .online-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
            animation: pulse-dot 2s infinite;
        }

        .online-dot.offline {
            background-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
            animation: none;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.15); }
        }

        .progress-moisture {
            height: 10px;
            border-radius: 6px;
            background-color: #e2e8f0;
            overflow: hidden;
            margin-top: 12px;
        }

        .progress-bar-moisture {
            transition: width 0.6s ease;
        }

        .btn-water {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            padding: 10px 20px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35);
        }

        .btn-water:hover:not(:disabled) {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.45);
            color: white;
        }

        .btn-water:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-pill-filter {
            border: 1px solid var(--border-color);
            background: white;
            color: var(--text-muted);
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-pill-filter.active, .btn-pill-filter:hover {
            background: var(--text-main);
            color: white;
            border-color: var(--text-main);
        }

        .table-custom {
            --bs-table-bg: transparent;
            margin-bottom: 0;
        }

        .table-custom th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
            padding: 12px 16px;
        }

        .table-custom td {
            padding: 14px 16px;
            font-size: 0.88rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .simulator-bar {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border-radius: var(--radius-xl);
            color: white;
            padding: 1.25rem 1.5rem;
            box-shadow: var(--shadow-subtle);
        }

        .pulse-watering {
            animation: water-glow 1s infinite alternate;
        }

        @keyframes water-glow {
            from { box-shadow: 0 0 10px rgba(59, 130, 246, 0.5); }
            to { box-shadow: 0 0 25px rgba(59, 130, 246, 0.8); }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-custom sticky-top">
        <div class="container-xl d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="brand-badge">
                    <i class="bi bi-flower1 me-1"></i> SMART PLANT
                </div>
                <div>
                    <h6 class="mb-0 fw-bold" id="headerPlantName">{{ $plant->plant_name ?? 'Smart Plant Monitor' }}</h6>
                    <small class="text-muted">Device ID: <span class="badge bg-light text-dark border" id="headerDeviceId">{{ $plant->device_id ?? 'SP001' }}</span></small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2 px-3 py-1 bg-white border rounded-pill shadow-sm">
                    <span id="deviceOnlineDot" class="online-dot bg-success"></span>
                    <span id="deviceOnlineText" class="fw-semibold small">Online</span>
                </div>
                <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#settingsModal">
                    <i class="bi bi-gear-fill me-1"></i> Settings
                </button>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTAINER -->
    <div class="container-xl py-4">

        <!-- SIMULATOR CONTROL BAR (Fase Dummy Data) -->
        <div class="simulator-bar mb-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-6 col-md-12">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 bg-white bg-opacity-10 rounded-3 text-warning fs-4">
                            <i class="bi bi-cpu"></i>
                        </div>
                        <div>
                            <span class="badge bg-warning text-dark mb-1">Dummy Data Simulator</span>
                            <h6 class="mb-0 fw-semibold text-white">Mode Simulasi Sensor Aktif (Tahap Pra-Hardware)</h6>
                            <small class="text-white-50">Generate dummy soil moisture data untuk mensimulasikan sensor tanah.</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-12 text-lg-end">
                    <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                        <button class="btn btn-sm btn-light fw-bold px-3 py-2 rounded-3 shadow-sm" onclick="generateSimulatedData(25)" title="Simulasi tanah kering < 30%">
                            <i class="bi bi-droplet text-danger me-1"></i> Test 25% (Kering)
                        </button>
                        <button class="btn btn-sm btn-light fw-bold px-3 py-2 rounded-3 shadow-sm" onclick="generateSimulatedData(50)">
                            <i class="bi bi-droplet-half text-warning me-1"></i> Test 50% (Sedang)
                        </button>
                        <button class="btn btn-sm btn-light fw-bold px-3 py-2 rounded-3 shadow-sm" onclick="generateSimulatedData(75)">
                            <i class="bi bi-droplet-fill text-success me-1"></i> Test 75% (Lembap)
                        </button>
                        <button class="btn btn-sm btn-success fw-bold px-3 py-2 rounded-3 shadow-sm" onclick="generateSimulatedData(null)">
                            <i class="bi bi-arrow-repeat me-1"></i> Random Fluktuasi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 TOP STAT CARDS -->
        <div class="row g-3 mb-4">
            <!-- 1. Plant Status -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="icon-box icon-box-green">
                        <i class="bi bi-tree"></i>
                    </div>
                    <div class="stat-title">🌱 Plant Status</div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span id="plantStatusBadge" class="status-badge badge-good">
                            <i class="bi bi-check-circle-fill"></i> <span id="plantStatusText">GOOD</span>
                        </span>
                    </div>
                    <small id="plantStatusMsg" class="text-muted d-block">Soil moisture level is optimal.</small>
                </div>
            </div>

            <!-- 2. Soil Moisture -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="icon-box icon-box-blue">
                        <i class="bi bi-droplet-fill"></i>
                    </div>
                    <div class="stat-title">💧 Soil Moisture</div>
                    <div class="stat-value">
                        <span id="soilMoistureValue">68</span><span class="fs-4 text-muted fw-normal">%</span>
                    </div>
                    <div class="progress-moisture">
                        <div id="soilMoistureProgress" class="progress-bar progress-bar-moisture bg-primary" role="progressbar" style="width: 68%;"></div>
                    </div>
                    <small class="text-muted d-block mt-2">Threshold: <span id="displayThreshold" class="fw-bold">30%</span></small>
                </div>
            </div>

            <!-- 3. Water Pump Status & Control -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card" id="pumpCard">
                    <div class="icon-box icon-box-purple">
                        <i class="bi bi-water"></i>
                    </div>
                    <div class="stat-title">🚿 Water Pump</div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span id="pumpStatusValue" class="fs-3 fw-bold text-secondary">OFF</span>
                        <small id="pumpDurationCounter" class="text-primary fw-bold" style="display: none;"></small>
                    </div>
                    <button id="btnWaterNow" class="btn btn-water w-100 btn-sm" onclick="triggerManualWatering()">
                        <i class="bi bi-lightning-charge-fill me-1"></i> WATER NOW
                    </button>
                </div>
            </div>

            <!-- 4. Device Status & Last Update -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="icon-box icon-box-amber">
                        <i class="bi bi-activity"></i>
                    </div>
                    <div class="stat-title">📡 Device Status</div>
                    <div class="stat-value fs-3" id="deviceStatusSummary">
                        ONLINE
                    </div>
                    <div class="mt-2 text-muted small">
                        <i class="bi bi-clock-history me-1"></i> Last update:
                        <div id="lastUpdateText" class="fw-semibold text-dark">Just now</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN ROW: CHART (8 cols) & QUICK CONFIG/INFO (4 cols) -->
        <div class="row g-4 mb-4">
            <!-- Soil Moisture History Chart -->
            <div class="col-lg-8">
                <div class="stat-card">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bi bi-graph-up text-primary me-2"></i>Soil Moisture History</h5>
                            <small class="text-muted">Grafik fluktuasi kelembapan tanah (%) dari database</small>
                        </div>
                        <div class="d-flex gap-1 mt-2 mt-sm-0">
                            <button id="btnFilterToday" class="btn btn-pill-filter active" onclick="loadChartHistory('today')">Today</button>
                            <button id="btnFilter7Days" class="btn btn-pill-filter" onclick="loadChartHistory('7days')">Last 7 Days</button>
                            <button class="btn btn-pill-filter" onclick="loadChartHistory(currentFilter)" title="Refresh Chart"><i class="bi bi-arrow-clockwise"></i></button>
                        </div>
                    </div>
                    <div style="position: relative; height: 320px; width: 100%;">
                        <canvas id="soilMoistureChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Automatic Watering Config & Overview -->
            <div class="col-lg-4">
                <div class="stat-card">
                    <h5 class="fw-bold mb-3 pb-2 border-bottom">
                        <i class="bi bi-sliders text-success me-2"></i>Otomatisasi Sistem
                    </h5>

                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold">Auto Watering:</span>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="toggleAutoWatering" onchange="toggleAutoWateringStatus(this.checked)" checked>
                            </div>
                        </div>
                        <small class="text-muted d-block" id="autoWateringDesc">
                            Pompa akan otomatis menyala selama <strong id="quickDurationText">5</strong> detik saat kelembapan < <strong id="quickThresholdText">30</strong>%.
                        </small>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Ambang Batas (Threshold)</span>
                            <span class="fw-bold text-dark" id="sliderThresholdValue">30%</span>
                        </div>
                        <input type="range" class="form-range" min="10" max="60" step="5" id="sliderThreshold" oninput="document.getElementById('sliderThresholdValue').innerText = this.value + '%'" onchange="quickUpdateThreshold(this.value)">
                    </div>

                    <div class="p-3 rounded-3" style="background-color: #f0fdf4; border: 1px dashed #86efac;">
                        <h6 class="fw-bold text-success mb-1"><i class="bi bi-shield-check me-1"></i> Safety Cooldown</h6>
                        <small class="text-muted">Sistem dilengkapi jeda proteksi 45 detik untuk mencegah pompa banjir atau menyala beruntun.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- WATERING HISTORY TABLE -->
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-journal-text text-primary me-2"></i>Watering History</h5>
                    <small class="text-muted">Catatan riwayat penyiraman manual dan otomatis yang tersimpan di tabel <code>pump_logs</code></small>
                </div>
                <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="loadPumpLogs()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh Log
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Waktu & Tanggal</th>
                            <th>Mode</th>
                            <th>Durasi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="pumpLogsTableBody">
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Memuat data riwayat...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- SETTINGS MODAL -->
    <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: var(--radius-xl);">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="settingsModalLabel"><i class="bi bi-sliders me-2 text-primary"></i>Plant Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="settingsForm" onsubmit="savePlantSettings(event)">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Plant Name</label>
                            <input type="text" class="form-control" id="inputPlantName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Device ID</label>
                            <input type="text" class="form-control bg-light" id="inputDeviceId" readonly>
                            <small class="text-muted">Device ID diatur pada microcontroller/simulator.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Soil Moisture Threshold (%)</label>
                            <input type="number" class="form-control" id="inputThreshold" min="1" max="100" required>
                            <small class="text-muted">Batas bawah kelembapan untuk memicu penyiraman otomatis.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Watering Duration (detik)</label>
                            <input type="number" class="form-control" id="inputDuration" min="1" max="60" required>
                            <small class="text-muted">Lama waktu pompa aktif setiap kali menyiram.</small>
                        </div>
                        <div class="mb-4 form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="inputAutoWatering">
                            <label class="form-check-label fw-semibold" for="inputAutoWatering">Aktifkan Automatic Watering</label>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnSaveSettings">
                                <i class="bi bi-save me-1"></i> Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST NOTIFICATION -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="liveToast" class="toast align-items-center text-white bg-dark border-0 rounded-3 shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toastMessage">
                    Pemberitahuan sistem.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DASHBOARD LOGIC SCRIPT -->
    <script>
        const PLANT_ID = 1; // Default single plant
        let currentFilter = 'today';
        let chartInstance = null;
        let isPumpActive = false;

        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            initChart();
            loadPlantDetails();
            loadChartHistory(currentFilter);
            loadPumpLogs();

            // Set auto-refresh interval every 5 seconds for real-time monitoring
            setInterval(() => {
                refreshRealtimeData();
            }, 5000);
        });

        // Show Toast Helper
        function showToast(msg, isSuccess = true) {
            const toastEl = document.getElementById('liveToast');
            const toastBody = document.getElementById('toastMessage');
            toastEl.className = `toast align-items-center text-white ${isSuccess ? 'bg-success' : 'bg-danger'} border-0 rounded-3 shadow`;
            toastBody.innerHTML = msg;
            const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
            toast.show();
        }

        // Initialize Chart.js
        function initChart() {
            const ctx = document.getElementById('soilMoistureChart').getContext('2d');
            
            // Gradient fill
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.35)');
            gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Soil Moisture (%)',
                        data: [],
                        borderColor: '#3b82f6',
                        backgroundColor: gradient,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#2563eb'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            min: 0,
                            max: 100,
                            ticks: {
                                callback: function(val) { return val + '%'; },
                                stepSize: 20
                            },
                            grid: { color: '#f1f5f9' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) { return ` Kelembapan: ${context.parsed.y}%`; }
                            }
                        }
                    }
                }
            });
        }

        // Load Plant Details & Settings
        async function loadPlantDetails() {
            try {
                const res = await fetch(`/api/plants/${PLANT_ID}`);
                const result = await res.json();
                if (result.success) {
                    const plant = result.data;
                    document.getElementById('headerPlantName').innerText = plant.plant_name;
                    document.getElementById('headerDeviceId').innerText = plant.device_id;
                    document.getElementById('displayThreshold').innerText = plant.soil_threshold + '%';
                    document.getElementById('quickThresholdText').innerText = plant.soil_threshold;
                    document.getElementById('quickDurationText').innerText = plant.watering_duration;
                    document.getElementById('toggleAutoWatering').checked = plant.automatic_watering;
                    document.getElementById('sliderThreshold').value = plant.soil_threshold;
                    document.getElementById('sliderThresholdValue').innerText = plant.soil_threshold + '%';

                    // Populate Settings Modal inputs
                    document.getElementById('inputPlantName').value = plant.plant_name;
                    document.getElementById('inputDeviceId').value = plant.device_id;
                    document.getElementById('inputThreshold').value = plant.soil_threshold;
                    document.getElementById('inputDuration').value = plant.watering_duration;
                    document.getElementById('inputAutoWatering').checked = plant.automatic_watering;

                    updateStatusCards(plant);
                }
            } catch (err) {
                console.error('Error fetching plant details:', err);
            }
        }

        // Realtime Data Polling
        async function refreshRealtimeData() {
            try {
                const res = await fetch(`/api/plants/${PLANT_ID}/latest`);
                const result = await res.json();
                if (result.success) {
                    updateStatusCards(result.data);
                }
            } catch (err) {
                console.error('Error refreshing realtime data:', err);
            }
        }

        // Update UI Cards
        function updateStatusCards(data) {
            // Moisture
            const moisture = data.latest_soil_moisture ?? data.soil_moisture ?? 0;
            document.getElementById('soilMoistureValue').innerText = moisture;
            const progress = document.getElementById('soilMoistureProgress');
            progress.style.width = moisture + '%';

            // Progress bar color based on moisture
            if (moisture <= 30) {
                progress.className = 'progress-bar progress-bar-moisture bg-danger';
            } else if (moisture <= 60) {
                progress.className = 'progress-bar progress-bar-moisture bg-warning';
            } else {
                progress.className = 'progress-bar progress-bar-moisture bg-success';
            }

            // Plant Status
            const statusBadge = document.getElementById('plantStatusBadge');
            const statusText = document.getElementById('plantStatusText');
            const statusMsg = document.getElementById('plantStatusMsg');
            const status = data.plant_status || 'GOOD';

            statusText.innerText = status;
            statusMsg.innerText = data.plant_status_message || '';

            if (status === 'DRY') {
                statusBadge.className = 'status-badge badge-dry';
                statusBadge.innerHTML = `<i class="bi bi-exclamation-triangle-fill"></i> <span id="plantStatusText">DRY</span>`;
            } else if (status === 'MODERATE') {
                statusBadge.className = 'status-badge badge-moderate';
                statusBadge.innerHTML = `<i class="bi bi-info-circle-fill"></i> <span id="plantStatusText">MODERATE</span>`;
            } else {
                statusBadge.className = 'status-badge badge-good';
                statusBadge.innerHTML = `<i class="bi bi-check-circle-fill"></i> <span id="plantStatusText">GOOD</span>`;
            }

            // Online Status
            const isOnline = data.is_online;
            const dot = document.getElementById('deviceOnlineDot');
            const onlineText = document.getElementById('deviceOnlineText');
            const summary = document.getElementById('deviceStatusSummary');

            if (isOnline) {
                dot.className = 'online-dot bg-success';
                onlineText.innerText = 'Online';
                summary.innerHTML = '<span class="text-success"><i class="bi bi-wifi me-1"></i>ONLINE</span>';
            } else {
                dot.className = 'online-dot offline';
                onlineText.innerText = 'Offline';
                summary.innerHTML = '<span class="text-danger"><i class="bi bi-wifi-off me-1"></i>OFFLINE</span>';
            }

            // Last Update
            document.getElementById('lastUpdateText').innerText = data.last_update || 'Just now';
        }

        // Load Chart Data
        async function loadChartHistory(filter) {
            currentFilter = filter;
            document.getElementById('btnFilterToday').className = filter === 'today' ? 'btn btn-pill-filter active' : 'btn btn-pill-filter';
            document.getElementById('btnFilter7Days').className = filter === '7days' ? 'btn btn-pill-filter active' : 'btn btn-pill-filter';

            try {
                const res = await fetch(`/api/plants/${PLANT_ID}/history?filter=${filter}`);
                const result = await res.json();
                if (result.success && chartInstance) {
                    chartInstance.data.labels = result.labels;
                    chartInstance.data.datasets[0].data = result.data;
                    chartInstance.update();
                }
            } catch (err) {
                console.error('Error fetching history:', err);
            }
        }

        // Load Pump Logs
        async function loadPumpLogs() {
            try {
                const res = await fetch(`/api/plants/${PLANT_ID}/pump-logs`);
                const result = await res.json();
                const tbody = document.getElementById('pumpLogsTableBody');
                if (result.success && result.data.length > 0) {
                    tbody.innerHTML = result.data.map(log => {
                        const dateFormatted = new Date(log.created_at).toLocaleString('id-ID', {
                            day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit', second: '2-digit'
                        });
                        const modeBadge = log.mode === 'automatic' 
                            ? '<span class="badge bg-primary-subtle text-primary border border-primary"><i class="bi bi-magic me-1"></i>Automatic</span>' 
                            : '<span class="badge bg-secondary-subtle text-secondary border border-secondary"><i class="bi bi-hand-index-thumb me-1"></i>Manual</span>';
                        
                        return `
                            <tr>
                                <td class="fw-semibold text-dark">${dateFormatted}</td>
                                <td>${modeBadge}</td>
                                <td><i class="bi bi-stopwatch me-1 text-muted"></i>${log.duration} sec</td>
                                <td><span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check2 me-1"></i>Completed</span></td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Belum ada riwayat penyiraman</td></tr>';
                }
            } catch (err) {
                console.error('Error loading pump logs:', err);
            }
        }

        // Trigger Manual Watering (Simulated Pump ON -> Wait Duration -> Pump OFF)
        async function triggerManualWatering() {
            const btn = document.getElementById('btnWaterNow');
            const card = document.getElementById('pumpCard');
            const pumpStatus = document.getElementById('pumpStatusValue');
            const counter = document.getElementById('pumpDurationCounter');
            const duration = parseInt(document.getElementById('inputDuration').value) || 5;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Starting...';

            try {
                const res = await fetch(`/api/plants/${PLANT_ID}/pump`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ duration: duration })
                });
                const result = await res.json();

                if (!res.ok) {
                    showToast(result.message || 'Pompa sedang aktif atau cooldown!', false);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-lightning-charge-fill me-1"></i> WATER NOW';
                    return;
                }

                // Simulate UI Animation for duration
                pumpStatus.innerText = 'ON';
                pumpStatus.className = 'fs-3 fw-bold text-primary';
                card.classList.add('pulse-watering');
                counter.style.display = 'inline';

                let timeLeft = duration;
                counter.innerText = `(${timeLeft}s)`;

                const countdown = setInterval(() => {
                    timeLeft--;
                    if (timeLeft > 0) {
                        counter.innerText = `(${timeLeft}s)`;
                    } else {
                        clearInterval(countdown);
                        // Reset Pump State
                        pumpStatus.innerText = 'OFF';
                        pumpStatus.className = 'fs-3 fw-bold text-secondary';
                        card.classList.remove('pulse-watering');
                        counter.style.display = 'none';
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-lightning-charge-fill me-1"></i> WATER NOW';
                        showToast(`Penyiraman manual berhasil selesai (${duration} detik)!`, true);
                        loadPumpLogs();
                    }
                }, 1000);

            } catch (err) {
                console.error(err);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-lightning-charge-fill me-1"></i> WATER NOW';
                showToast('Gagal menghubungi backend API', false);
            }
        }

        // Toggle Auto Watering Status
        async function toggleAutoWateringStatus(isEnabled) {
            try {
                const res = await fetch(`/api/plants/${PLANT_ID}/settings`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ automatic_watering: isEnabled })
                });
                const result = await res.json();
                if (result.success) {
                    showToast(`Automatic Watering berhasil ${isEnabled ? 'diaktifkan' : 'dinonaktifkan'}.`, true);
                    document.getElementById('inputAutoWatering').checked = isEnabled;
                }
            } catch (err) {
                console.error(err);
            }
        }

        // Quick Update Threshold via Slider
        async function quickUpdateThreshold(val) {
            try {
                const res = await fetch(`/api/plants/${PLANT_ID}/settings`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ soil_threshold: parseInt(val) })
                });
                const result = await res.json();
                if (result.success) {
                    document.getElementById('displayThreshold').innerText = val + '%';
                    document.getElementById('quickThresholdText').innerText = val;
                    document.getElementById('inputThreshold').value = val;
                    showToast(`Threshold diperbarui menjadi ${val}%.`, true);
                    refreshRealtimeData();
                }
            } catch (err) {
                console.error(err);
            }
        }

        // Save Settings via Modal Form
        async function savePlantSettings(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveSettings');
            btn.disabled = true;
            btn.innerText = 'Menyimpan...';

            const payload = {
                plant_name: document.getElementById('inputPlantName').value,
                soil_threshold: parseInt(document.getElementById('inputThreshold').value),
                watering_duration: parseInt(document.getElementById('inputDuration').value),
                automatic_watering: document.getElementById('inputAutoWatering').checked
            };

            try {
                const res = await fetch(`/api/plants/${PLANT_ID}/settings`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();

                if (result.success) {
                    showToast('Pengaturan tanaman berhasil disimpan!', true);
                    const modal = bootstrap.Modal.getInstance(document.getElementById('settingsModal'));
                    if (modal) modal.hide();
                    loadPlantDetails();
                } else {
                    showToast(result.message || 'Gagal menyimpan pengaturan', false);
                }
            } catch (err) {
                console.error(err);
                showToast('Error koneksi ke API', false);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Pengaturan';
            }
        }

        // Generate Simulated Sensor Data (Dummy Generator Trigger)
        async function generateSimulatedData(customMoisture) {
            const payload = {
                device_id: document.getElementById('headerDeviceId').innerText.trim() || 'SP001'
            };

            if (customMoisture !== null) {
                payload.soil_moisture = customMoisture;
            }

            try {
                const res = await fetch('/api/simulator/generate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();

                if (result.success) {
                    const moisture = result.data.soil_moisture;
                    let msg = `Dummy data diterima: Kelembapan <strong>${moisture}%</strong> (${result.plant_status})`;
                    if (result.auto_watering_triggered) {
                        msg += '<br>🚀 <span class="badge bg-warning text-dark">Auto Watering Dipicu! Pompa aktif!</span>';
                    }
                    showToast(msg, true);
                    refreshRealtimeData();
                    loadChartHistory(currentFilter);
                    if (result.auto_watering_triggered) {
                        loadPumpLogs();
                    }
                } else {
                    showToast(result.message || 'Gagal menghasilkan dummy data', false);
                }
            } catch (err) {
                console.error(err);
                showToast('Error saat menghubungi endpoint simulator', false);
            }
        }
    </script>
</body>
</html>
