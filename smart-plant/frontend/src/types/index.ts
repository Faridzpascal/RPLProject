export interface Device {
  Id: number;
  MacAddress: string;
  Name: string;
  Location: string;
  OperatingMode: string;
  PumpStatus: string;
  LastSeenAt: string | null;
  CreatedAt: string;
}

export interface DeviceDashboard {
  CurrentMoistureLevel: number | null;
  CurrentPumpStatus: string;
  CurrentOperatingMode: string;
  LastSyncTime: string | null;
}

export interface SensorLog {
  Id: number;
  DeviceId: number;
  MoistureLevel: number;
  RecordedAt: string;
}

export interface ActionLog {
  Id: number;
  DeviceId: number;
  Mode: string;
  Action: string;
  TriggeredAt: string;
}
