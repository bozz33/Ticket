import { Injectable, PayloadTooLargeException } from '@nestjs/common';
import { serviceConfig } from '../../shared/config/service.config';

@Injectable()
export class TenantQuotaService {
  private readonly config = serviceConfig();

  ensureUploadAllowed(byteSize: number): void {
    if (byteSize > this.config.uploadMaxBytes) {
      throw new PayloadTooLargeException('File exceeds upload limit');
    }
  }
}
