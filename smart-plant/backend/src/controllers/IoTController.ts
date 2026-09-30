import { Request, Response } from 'express';
import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();

export const receiveTelemetry = async (req: Request, res: Response) => {
  try {
    const { MacAddress, MoistureLevel } = req.body;
    
    if (!MacAddress || MoistureLevel === undefined) {
      return res.status(400).json({ message: 'Invalid telemetry data' });
    }

    const device = await prisma.device.findUnique({ where: { MacAddress } });
    if (!device) return res.status(404).json({ message: 'Device not found' });

    await prisma.sensorLog.create({
      data: {
        DeviceId: device.Id,
        MoistureLevel: Number(MoistureLevel),
      },
    });

    await prisma.device.update({
      where: { Id: device.Id },
      data: { LastSeenAt: new Date() },
    });

    // In REST architecture, ESP32 polls the current mode & pump status here
    res.json({
      OperatingMode: device.OperatingMode,
      PumpStatus: device.PumpStatus,
    });
  } catch (error) {
    res.status(500).json({ message: 'Internal server error' });
  }
};

export const reportAction = async (req: Request, res: Response) => {
  try {
    const { MacAddress, Action, Mode } = req.body;
    
    if (!MacAddress || !Action || !Mode) {
      return res.status(400).json({ message: 'Invalid action report data' });
    }

    const device = await prisma.device.findUnique({ where: { MacAddress } });
    if (!device) return res.status(404).json({ message: 'Device not found' });

    // Ensure the device table's status matches what the ESP32 reports if AUTO mode
    if (Mode === 'AUTO') {
      await prisma.device.update({
        where: { Id: device.Id },
        data: { PumpStatus: Action, LastSeenAt: new Date() },
      });
    }

    await prisma.actionLog.create({
      data: {
        DeviceId: device.Id,
        Mode,
        Action,
      },
    });

    res.json({ message: 'Action recorded' });
  } catch (error) {
    res.status(500).json({ message: 'Internal server error' });
  }
};
