import { Injectable } from '@nestjs/common';
import QRCode from 'qrcode';
import { serviceConfig } from '../../shared/config/service.config';

@Injectable()
export class QrCodeService {
  private readonly config = serviceConfig();

  async renderDataUri(payload: unknown, size?: number): Promise<{ dataUri: string; format: string; size: number }> {
    const width = size && size > 80 ? size : this.config.qrDefaultSize;
    const data = typeof payload === 'string' ? payload : JSON.stringify(payload);
    const dataUri = await QRCode.toDataURL(data, {
      width,
      margin: 1,
      errorCorrectionLevel: 'M',
    });

    return { dataUri, format: 'png-data-uri', size: width };
  }
}
