import { useState, useEffect } from 'react';
import axios from 'axios';
import { Device } from './types';
import Dashboard from './components/Dashboard';
import DeviceManagement from './components/DeviceManagement';
import HistoryLogs from './components/HistoryLogs';
import { Leaf, Server, History } from 'lucide-react';

const API_BASE_URL = 'http://localhost:3001/api';

function App() {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [devices, setDevices] = useState<Device[]>([]);
  const [selectedDeviceId, setSelectedDeviceId] = useState<number | null>(null);

  const fetchDevices = async () => {
    try {
      const response = await axios.get<Device[]>(`${API_BASE_URL}/devices`);
      setDevices(response.data);
      if (response.data.length > 0 && !selectedDeviceId) {
        setSelectedDeviceId(response.data[0].Id);
      }
    } catch (error) {
      console.error('Error fetching devices', error);
    }
  };

  useEffect(() => {
    fetchDevices();
  }, []);

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">
      <header className="bg-green-600 text-white p-4 shadow-md">
        <div className="container mx-auto flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <Leaf size={24} />
            <h1 className="text-xl font-bold">Smart Plant Dashboard</h1>
          </div>
          {devices.length > 0 && (
            <select 
              className="bg-green-700 text-white border-none p-2 rounded outline-none"
              value={selectedDeviceId || ''}
              onChange={(e) => setSelectedDeviceId(Number(e.target.value))}
            >
              {devices.map(d => (
                <option key={d.Id} value={d.Id}>{d.Name} ({d.Location})</option>
              ))}
            </select>
          )}
        </div>
      </header>

      <main className="flex-1 container mx-auto p-4 flex flex-col md:flex-row gap-6">
        <aside className="w-full md:w-64 bg-white rounded-lg shadow p-4 h-fit">
          <nav className="flex flex-col space-y-2">
            <button
              onClick={() => setActiveTab('dashboard')}
              className={`flex items-center space-x-3 p-3 rounded-md transition-colors ${activeTab === 'dashboard' ? 'bg-green-100 text-green-700' : 'hover:bg-gray-100'}`}
            >
              <Leaf size={20} />
              <span>Dashboard</span>
            </button>
            <button
              onClick={() => setActiveTab('devices')}
              className={`flex items-center space-x-3 p-3 rounded-md transition-colors ${activeTab === 'devices' ? 'bg-green-100 text-green-700' : 'hover:bg-gray-100'}`}
            >
              <Server size={20} />
              <span>Kelola Perangkat</span>
            </button>
            <button
              onClick={() => setActiveTab('logs')}
              className={`flex items-center space-x-3 p-3 rounded-md transition-colors ${activeTab === 'logs' ? 'bg-green-100 text-green-700' : 'hover:bg-gray-100'}`}
            >
              <History size={20} />
              <span>Riwayat Log</span>
            </button>
          </nav>
        </aside>

        <section className="flex-1 bg-white rounded-lg shadow p-6">
          {activeTab === 'dashboard' && <Dashboard deviceId={selectedDeviceId} baseUrl={API_BASE_URL} />}
          {activeTab === 'devices' && <DeviceManagement baseUrl={API_BASE_URL} onDevicesChanged={fetchDevices} devices={devices} />}
          {activeTab === 'logs' && <HistoryLogs deviceId={selectedDeviceId} baseUrl={API_BASE_URL} />}
        </section>
      </main>
    </div>
  );
}

export default App;
