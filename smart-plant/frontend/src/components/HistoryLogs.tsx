import { useState, useEffect } from 'react';
import axios from 'axios';
import { SensorLog, ActionLog } from '../types';

interface HistoryLogsProps {
  deviceId: number | null;
  baseUrl: string;
}

export default function HistoryLogs({ deviceId, baseUrl }: HistoryLogsProps) {
  const [sensorLogs, setSensorLogs] = useState<SensorLog[]>([]);
  const [actionLogs, setActionLogs] = useState<ActionLog[]>([]);
  const [tab, setTab] = useState<'sensor' | 'action'>('sensor');

  const fetchLogs = async () => {
    if (!deviceId) return;
    try {
      const [sensorRes, actionRes] = await Promise.all([
        axios.get<SensorLog[]>(`${baseUrl}/devices/${deviceId}/logs/sensors`),
        axios.get<ActionLog[]>(`${baseUrl}/devices/${deviceId}/logs/actions`)
      ]);
      setSensorLogs(sensorRes.data);
      setActionLogs(actionRes.data);
    } catch (err) {
      console.error('Error fetching logs', err);
    }
  };

  useEffect(() => {
    fetchLogs();
  }, [deviceId]);

  if (!deviceId) return <div className="text-gray-500 text-center py-10">Pilih atau tambah perangkat terlebih dahulu</div>;

  return (
    <div className="space-y-6">
      <h2 className="text-2xl font-bold mb-4">Riwayat Log</h2>

      <div className="flex space-x-4 border-b">
        <button
          className={`pb-2 px-1 font-medium ${tab === 'sensor' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-500'}`}
          onClick={() => setTab('sensor')}
        >
          Log Sensor
        </button>
        <button
          className={`pb-2 px-1 font-medium ${tab === 'action' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-500'}`}
          onClick={() => setTab('action')}
        >
          Log Aksi
        </button>
      </div>

      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-gray-200 border">
          <thead className="bg-gray-50">
            <tr>
              {tab === 'sensor' ? (
                <>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelembapan (%)</th>
                </>
              ) : (
                <>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mode Saat Itu</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi Pompa</th>
                </>
              )}
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {tab === 'sensor' && sensorLogs.length === 0 && (
              <tr><td colSpan={2} className="px-6 py-4 text-center text-gray-500">Belum ada log sensor.</td></tr>
            )}
            {tab === 'sensor' && sensorLogs.map((log) => (
              <tr key={log.Id}>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{new Date(log.RecordedAt).toLocaleString('id-ID')}</td>
                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <span className={`px-2 py-1 rounded-md ${log.MoistureLevel < 30 ? 'bg-red-100 text-red-800' : log.MoistureLevel > 70 ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800'}`}>
                    {log.MoistureLevel}%
                  </span>
                </td>
              </tr>
            ))}
            
            {tab === 'action' && actionLogs.length === 0 && (
              <tr><td colSpan={3} className="px-6 py-4 text-center text-gray-500">Belum ada log aksi.</td></tr>
            )}
            {tab === 'action' && actionLogs.map((log) => (
              <tr key={log.Id}>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{new Date(log.TriggeredAt).toLocaleString('id-ID')}</td>
                <td className="px-6 py-4 whitespace-nowrap text-sm">
                  <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${log.Mode === 'AUTO' ? 'bg-purple-100 text-purple-800' : 'bg-orange-100 text-orange-800'}`}>
                    {log.Mode}
                  </span>
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm">
                  <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${log.Action === 'ON' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                    {log.Action}
                  </span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
