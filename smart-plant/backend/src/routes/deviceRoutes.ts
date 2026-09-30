import { Router } from 'express';
import {
  getDevices,
  createDevice,
  getDeviceById,
  updateDevice,
  deleteDevice,
  updateDeviceMode,
  updateDevicePump,
  getDeviceDashboard,
  getDeviceSensorLogs,
  getDeviceActionLogs,
} from '../controllers/DeviceController';

const router = Router();

router.get('/', getDevices);
router.post('/', createDevice);
router.get('/:Id', getDeviceById);
router.put('/:Id', updateDevice);
router.delete('/:Id', deleteDevice);

router.put('/:Id/mode', updateDeviceMode);
router.post('/:Id/pump', updateDevicePump);

router.get('/:Id/dashboard', getDeviceDashboard);
router.get('/:Id/logs/sensors', getDeviceSensorLogs);
router.get('/:Id/logs/actions', getDeviceActionLogs);

export default router;
