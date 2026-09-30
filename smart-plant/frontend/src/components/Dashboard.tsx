import { useState, useEffect } from 'react';
import axios from 'axios';
import { DeviceDashboard } from '../types';

interface DashboardProps {
  deviceId: number | null;
  baseUrl: string;
}

export default function Dashboard({ deviceId, baseUrl }: DashboardProps) {
  const [data, setData] = useState<DeviceDashboard | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  const fetchDashboard = async () => {
    if (!deviceId) return;
    try {
      const response = await axios.get<DeviceDashboard>(`${baseUrl}/devices/${deviceId}/dashboard`);
      setData(response.data);
    } catch (err) {
      setError('Gagal memuat data dashboard');
    }
  };

  useEffect(() => {
    fetchDashboard();
    const interval = setInterval(fetchDashboard, 5000);
    return () => clearInterval(interval);
  }, [deviceId]);

  const toggleMode = async () => {
    if (!deviceId || !data) return;
    setLoading(true);
    setError('');
    setSuccessMsg('');
    const newMode = data.CurrentOperatingMode === 'AUTO' ? 'MANUAL' : 'AUTO';
    try {
      await axios.put(`${baseUrl}/devices/${deviceId}/mode`, { OperatingMode: newMode });
      setData({ ...data, CurrentOperatingMode: newMode });
      setSuccessMsg(`Berhasil mengubah mode ke ${newMode === 'AUTO' ? 'Otomatis' : 'Manual'}`);
    } catch (err) {
      setError('Gagal mengubah mode');
    } finally {
      setLoading(false);
    }
  };

  const togglePump = async (status: 'ON' | 'OFF') => {
    if (!deviceId || !data || data.CurrentOperatingMode === 'AUTO') return;
    setLoading(true);
    setError('');
    setSuccessMsg('');
    try {
      await axios.post(`${baseUrl}/devices/${deviceId}/pump`, { PumpStatus: status });
      setData({ ...data, CurrentPumpStatus: status });
      setSuccessMsg(`Pompa berhasil ${status === 'ON' ? 'dinyalakan' : 'dimatikan'}`);
    } catch (err) {
      setError('Gagal mengubah status pompa');
    } finally {
      setLoading(false);
    }
  };

  if (!deviceId) return <div className="text-gray-500 text-center py-10">Pilih atau tambah perangkat terlebih dahulu</div>;
  if (!data) return <div className="text-center py-10">Memuat...</div>;

  let moistureColor = 'bg-yellow-100 text-yellow-800';
  const ml = data.CurrentMoistureLevel;
  if (ml !== null) {
    if (ml < 30) moistureColor = 'bg-red-100 text-red-800';
    else if (ml > 70) moistureColor = 'bg-blue-100 text-blue-800';
  }

  const pumpColor = data.CurrentPumpStatus === 'ON' ? 'bg-green-100 text-green-800 border-green-300' : 'bg-gray-100 text-gray-800 border-gray-300';
  const modeColor = data.CurrentOperatingMode === 'AUTO' ? 'bg-purple-100 text-purple-800 border-purple-300' : 'bg-orange-100 text-orange-800 border-orange-300';

  return (
    <div className="space-y-6">
      <h2 className="text-2xl font-bold mb-4">Dashboard</h2>
      
      {error && <div className="bg-red-50 text-red-600 p-3 rounded-md mb-4">{error}</div>}
      {successMsg && <div className="bg-green-50 text-green-600 p-3 rounded-md mb-4">{successMsg}</div>}

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="p-4 rounded-lg border bg-white shadow-sm flex flex-col items-center justify-center">
          <span className="text-gray-500 text-sm">Kelembapan Tanah Saat Ini</span>
          <div className={`mt-2 px-4 py-2 rounded-full font-semibold ${moistureColor}`}>
            {ml !== null ? `${ml}%` : 'Tidak ada data'}
          </div>
        </div>
        <div className={`p-4 rounded-lg border shadow-sm flex flex-col items-center justify-center ${pumpColor}`}>
          <span className="text-sm opacity-80">Status Pompa</span>
          <div className="mt-2 text-xl font-bold">
            {data.CurrentPumpStatus === 'ON' ? 'NYALA' : 'MATI'}
          </div>
        </div>
        <div className={`p-4 rounded-lg border shadow-sm flex flex-col items-center justify-center ${modeColor}`}>
          <span className="text-sm opacity-80">Mode Operasi</span>
          <div className="mt-2 text-xl font-bold">
            {data.CurrentOperatingMode === 'AUTO' ? 'OTOMATIS' : 'MANUAL'}
          </div>
        </div>
        <div className="p-4 rounded-lg border bg-white shadow-sm flex flex-col items-center justify-center text-center">
          <span className="text-gray-500 text-sm">Terakhir Aktif</span>
          <div className="mt-2 font-medium text-gray-700">
            {data.LastSyncTime ? new Date(data.LastSyncTime).toLocaleString('id-ID') : '-'}
          </div>
        </div>
      </div>

      <div className="mt-8 border-t pt-6">
        <h3 className="text-lg font-semibold mb-4">Kontrol Manual</h3>
        <div className="flex flex-wrap gap-4 items-center">
          <button 
            onClick={toggleMode}
            disabled={loading}
            className="px-4 py-2 rounded-md bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-50"
          >
            Ubah ke Mode {data.CurrentOperatingMode === 'AUTO' ? 'Manual' : 'Otomatis'}
          </button>

          <div className="flex space-x-2 border-l pl-4">
            <button 
              onClick={() => togglePump('ON')}
              disabled={loading || data.CurrentOperatingMode === 'AUTO' || data.CurrentPumpStatus === 'ON'}
              className="px-4 py-2 rounded-md bg-green-600 text-white font-medium hover:bg-green-700 disabled:opacity-50"
            >
              Nyalakan Pompa
            </button>
            <button 
              onClick={() => togglePump('OFF')}
              disabled={loading || data.CurrentOperatingMode === 'AUTO' || data.CurrentPumpStatus === 'OFF'}
              className="px-4 py-2 rounded-md bg-gray-600 text-white font-medium hover:bg-gray-700 disabled:opacity-50"
            >
              Matikan Pompa
            </button>
          </div>
        </div>
        {data.CurrentOperatingMode === 'AUTO' && (
          <p className="text-sm text-gray-500 mt-2">Tombol pompa dinonaktifkan karena perangkat berada dalam Mode Otomatis.</p>
        )}
      </div>
    </div>
  );
}
