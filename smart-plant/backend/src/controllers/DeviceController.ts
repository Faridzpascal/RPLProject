import { Request, Response } from 'express';
import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();

export const getDevices = async (req: Request, res: Response) => {
  try {
    const devices = await prisma.device.findMany();
    res.json(devices);
  } catch (error) {
    res.status(500).json({ message: 'Internal server error' });
  }
};

export const createDevice = async (req: Request, res: Response) => {
  try {
    const { MacAddress, Name, Location } = req.body;
    const device = await prisma.device.create({
      data: { MacAddress, Name, Location },
    });
    res.status(201).json(device);
  } catch (error) {
    res.status(400).json({ message: 'Bad request' });
  }
};

export const getDeviceById = async (req: Request, res: Response) => {
  try {
    const { Id } = req.params;
    const device = await prisma.device.findUnique({ where: { Id: Number(Id) } });
    if (!device) return res.status(404).json({ message: 'Device not found' });
    res.json(device);
  } catch (error) {
    res.status(500).json({ message: 'Internal server error' });
  }
};

export const updateDevice = async (req: Request, res: Response) => {
  try {
    const { Id } = req.params;
    const { Name, Location } = req.body;
    const device = await prisma.device.update({
      where: { Id: Number(Id) },
      data: { Name, Location },
    });
    res.json(device);
  } catch (error) {
    res.status(400).json({ message: 'Bad request' });
  }
};

export const deleteDevice = async (req: Request, res: Response) => {
  try {
    const { Id } = req.params;
    await prisma.sensorLog.deleteMany({ where: { DeviceId: Number(Id) } });
    await prisma.actionLog.deleteMany({ where: { DeviceId: Number(Id) } });
    await prisma.device.delete({ where: { Id: Number(Id) } });
    res.json({ message: 'Device deleted' });
  } catch (error) {
    res.status(500).json({ message: 'Internal server error' });
  }
};

export const updateDeviceMode = async (req: Request, res: Response) => {
  try {
    const { Id } = req.params;
    const { OperatingMode } = req.body; // "AUTO" or "MANUAL"
    
    if (OperatingMode !== 'AUTO' && OperatingMode !== 'MANUAL') {
      return res.status(400).json({ message: 'Invalid mode' });
    }

    const device = await prisma.device.update({
      where: { Id: Number(Id) },
      data: { OperatingMode },
    });
    res.json(device);
  } catch (error) {
    res.status(400).json({ message: 'Bad request' });
  }
};

export const updateDevicePump = async (req: Request, res: Response) => {
  try {
    const { Id } = req.params;
    const { PumpStatus } = req.body; // "ON" or "OFF"

    if (PumpStatus !== 'ON' && PumpStatus !== 'OFF') {
      return res.status(400).json({ message: 'Invalid status' });
    }

    const device = await prisma.device.findUnique({ where: { Id: Number(Id) } });
    if (!device) return res.status(404).json({ message: 'Device not found' });
    
    if (device.OperatingMode !== 'MANUAL') {
      return res.status(400).json({ message: 'Pump can only be manually controlled in MANUAL mode' });
    }

    const updatedDevice = await prisma.device.update({
      where: { Id: Number(Id) },
      data: { PumpStatus },
    });

    await prisma.actionLog.create({
      data: {
        DeviceId: Number(Id),
        Mode: updatedDevice.OperatingMode,
        Action: PumpStatus,
      },
    });

    res.json(updatedDevice);
  } catch (error) {
    res.status(500).json({ message: 'Internal server error' });
  }
};

export const getDeviceDashboard = async (req: Request, res: Response) => {
  try {
    const { Id } = req.params;
    const device = await prisma.device.findUnique({ where: { Id: Number(Id) } });
    if (!device) return res.status(404).json({ message: 'Device not found' });

    const latestSensor = await prisma.sensorLog.findFirst({
      where: { DeviceId: Number(Id) },
      orderBy: { RecordedAt: 'desc' },
    });

    res.json({
      CurrentMoistureLevel: latestSensor ? latestSensor.MoistureLevel : null,
      CurrentPumpStatus: device.PumpStatus,
      CurrentOperatingMode: device.OperatingMode,
      LastSyncTime: device.LastSeenAt,
    });
  } catch (error) {
    res.status(500).json({ message: 'Internal server error' });
  }
};

export const getDeviceSensorLogs = async (req: Request, res: Response) => {
  try {
    const { Id } = req.params;
    const logs = await prisma.sensorLog.findMany({
      where: { DeviceId: Number(Id) },
      orderBy: { RecordedAt: 'desc' },
      take: 100,
    });
    res.json(logs);
  } catch (error) {
    res.status(500).json({ message: 'Internal server error' });
  }
};

export const getDeviceActionLogs = async (req: Request, res: Response) => {
  try {
    const { Id } = req.params;
    const logs = await prisma.actionLog.findMany({
      where: { DeviceId: Number(Id) },
      orderBy: { TriggeredAt: 'desc' },
      take: 100,
    });
    res.json(logs);
  } catch (error) {
    res.status(500).json({ message: 'Internal server error' });
  }
};
