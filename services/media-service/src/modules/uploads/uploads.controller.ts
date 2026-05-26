import { Body, Controller, Inject, Post } from '@nestjs/common';
import { ASSETS_REPOSITORY, AssetsRepository } from '../assets/assets.repository';
import { SignedUrlService } from '../assets/signed-url.service';
import { TenantQuotaService } from '../quotas/tenant-quota.service';

type CreateUploadIntentBody = {
  tenantId?: string;
  ownerType?: string;
  ownerId?: string;
  purpose: string;
  originalName: string;
  mimeType: string;
  byteSize: number;
  checksumSha256?: string;
  metadata?: Record<string, unknown>;
};

@Controller('uploads')
export class UploadsController {
  constructor(
    @Inject(ASSETS_REPOSITORY) private readonly assets: AssetsRepository,
    private readonly signedUrls: SignedUrlService,
    private readonly quotas: TenantQuotaService,
  ) {}

  @Post('intent')
  async createIntent(@Body() body: CreateUploadIntentBody): Promise<Record<string, unknown>> {
    this.quotas.ensureUploadAllowed(Number(body.byteSize));

    const asset = await this.assets.create({
      tenantId: body.tenantId ?? null,
      ownerType: body.ownerType ?? null,
      ownerId: body.ownerId ?? null,
      purpose: body.purpose,
      originalName: body.originalName,
      mimeType: body.mimeType,
      byteSize: Number(body.byteSize),
      checksumSha256: body.checksumSha256 ?? null,
      storageDisk: 'service-managed',
      storageKey: this.storageKey(body),
      metadata: body.metadata ?? {},
    });

    return {
      asset,
      upload: {
        method: 'PUT',
        url: this.signedUrls.buildDownloadUrl(asset, 'upload'),
      },
    };
  }

  private storageKey(body: CreateUploadIntentBody): string {
    const tenant = body.tenantId ?? 'platform';
    const purpose = body.purpose.replace(/[^a-zA-Z0-9_-]/g, '-').toLowerCase();
    const name = body.originalName.replace(/[^a-zA-Z0-9_.-]/g, '-').toLowerCase();

    return [tenant, purpose, Date.now(), name].join('/');
  }
}
