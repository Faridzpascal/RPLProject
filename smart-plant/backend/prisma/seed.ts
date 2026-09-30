import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();

async function main() {
  const device = await prisma.device.upsert({
    where: { MacAddress: 'AA:BB:CC:DD:EE:FF' },
    update: {},
    create: {
      MacAddress: 'AA:BB:CC:DD:EE:FF',
      Name: 'Tanaman Tomat Balkon',
      Location: 'Balkon Depan',
      OperatingMode: 'AUTO',
      PumpStatus: 'OFF',
    },
  });

  await prisma.sensorLog.create({
    data: {
      DeviceId: device.Id,
      MoistureLevel: 45.5,
    },
  });

  await prisma.actionLog.create({
    data: {
      DeviceId: device.Id,
      Mode: 'MANUAL',
      Action: 'ON',
    },
  });

  console.log('Seed completed successfully.');
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
