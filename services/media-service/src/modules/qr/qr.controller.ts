import { Body, Controller, Post } from '@nestjs/common';
import { QrCodeService } from './qr-code.service';

type CreateQrBody = {
  payload: unknown;
  size?: number;
};

@Controller('qr-codes')
export class QrController {
  constructor(private readonly qrCodes: QrCodeService) {}

  @Post()
  async create(@Body() body: CreateQrBody): Promise<Record<string, unknown>> {
    return this.qrCodes.renderDataUri(body.payload, body.size);
  }
}
