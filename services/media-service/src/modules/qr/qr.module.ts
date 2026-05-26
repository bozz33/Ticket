import { Module } from '@nestjs/common';
import { QrController } from './qr.controller';
import { QrCodeService } from './qr-code.service';

@Module({
  controllers: [QrController],
  providers: [QrCodeService],
  exports: [QrCodeService],
})
export class QrModule {}
