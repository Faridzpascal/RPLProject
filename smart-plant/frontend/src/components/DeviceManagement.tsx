import { useState } from 'react';
import axios from 'axios';
import { Device } from '../types';
import { Trash2 } from 'lucide-react';

interface DeviceManagementProps {
  baseUrl: string;
  devices: Device[];
  onDevicesChanged: () => void;
}

export default function DeviceManagement({ baseUrl, devices, onDevicesChanged }: DeviceManagementProps) {
  const [macAddress, setMacAddress] = useState('');
  const [name, setName] = useState('');
  const [location, setLocation] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    setSuccess('');
    try {
      await axios.post(`${baseUrl}/devices`, {
        MacAddress: macAddress,
        Name: name,
        Location: location,
      });
      setSuccess('Perangkat berhasil ditambahkan');
      setMacAddress('');
      setName('');
      setLocation('');
      onDevicesChanged();
    } catch (err) {
      setError('Gagal menambahkan perangkat');
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Apakah Anda yakin ingin menghapus perangkat ini?')) return;
    try {
      await axios.delete(`${baseUrl}/devices/${id}`);
      onDevicesChanged();
    } catch (err) {
      alert('Gagal menghapus perangkat');
    }
  };

  return (
    <div className="space-y-6">
      <h2 className="text-2xl font-bold mb-4">Kelola Perangkat</h2>

      <div className="bg-gray-50 p-4 rounded-lg border">
        <h3 className="font-semibold mb-3">Tambah Perangkat Baru</h3>
        {error && <div className="text-red-500 text-sm mb-2">{error}</div>}
        {success && <div className="text-green-500 text-sm mb-2">{success}</div>}
        <form onSubmit={handleCreate} className="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Mac Address</label>
            <input 
              required
              type="text" 
              placeholder="AA:BB:CC:DD:EE:FF"
              className="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:ring-green-500 focus:border-green-500"
              value={macAddress}
              onChange={(e) => setMacAddress(e.target.value)}
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Nama Perangkat</label>
            <input 
              required
              type="text" 
              placeholder="Tanaman Tomat"
              className="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:ring-green-500 focus:border-green-500"
              value={name}
              onChange={(e) => setName(e.target.value)}
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
            <div className="flex gap-2">
              <input 
                required
                type="text" 
                placeholder="Balkon"
                className="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:ring-green-500 focus:border-green-500"
                value={location}
                onChange={(e) => setLocation(e.target.value)}
              />
              <button 
                type="submit" 
                disabled={loading}
                className="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 disabled:opacity-50"
              >
                Tambah
              </button>
            </div>
          </div>
        </form>
      </div>

      <div>
        <h3 className="font-semibold mb-3">Daftar Perangkat</h3>
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200 border">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mac Address</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mode</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {devices.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-6 py-4 text-center text-gray-500">Tidak ada perangkat.</td>
                </tr>
              ) : (
                devices.map((d) => (
                  <tr key={d.Id}>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{d.Id}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{d.MacAddress}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{d.Name}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{d.Location}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                      <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${d.OperatingMode === 'AUTO' ? 'bg-purple-100 text-purple-800' : 'bg-orange-100 text-orange-800'}`}>
                        {d.OperatingMode}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <button onClick={() => handleDelete(d.Id)} className="text-red-600 hover:text-red-900">
                        <Trash2 size={18} />
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
