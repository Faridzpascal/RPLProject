import { Router } from 'express';
import { receiveTelemetry, reportAction } from '../controllers/IoTController';

const router = Router();

router.post('/telemetry', receiveTelemetry);
router.post('/action', reportAction);

export default router;
