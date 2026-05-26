import { Body, Controller, Post } from '@nestjs/common';
import { ScanService } from './scan.service';

@Controller('scan')
export class ScanController {
  constructor(private readonly scans: ScanService) {}

  @Post()
  async scan(@Body() body: Record<string, unknown>): Promise<Record<string, unknown>> {
    return this.scans.scan({
      tenantId: String(body.tenant_id ?? body.tenantId ?? ''),
      reference: String(body.reference ?? body.code ?? ''),
      agentId: typeof body.agent_id === 'string' ? body.agent_id : null,
      entryPoint: typeof body.entry_point === 'string' ? body.entry_point : null,
      deviceId: typeof body.device_id === 'string' ? body.device_id : null,
      offlineBatchId: typeof body.offline_batch_id === 'string' ? body.offline_batch_id : null,
      metadata: isRecord(body.metadata) ? body.metadata : {},
    });
  }
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}
