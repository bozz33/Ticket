import { Controller, Get, Inject, NotFoundException, Param, Query, UnauthorizedException } from '@nestjs/common';
import { ASSETS_REPOSITORY, AssetsRepository } from './assets.repository';
import { SignedUrlService } from './signed-url.service';

@Controller('assets')
export class AssetsController {
  constructor(
    @Inject(ASSETS_REPOSITORY) private readonly assets: AssetsRepository,
    private readonly signedUrls: SignedUrlService,
  ) {}

  @Get(':assetId')
  async show(@Param('assetId') assetId: string): Promise<Record<string, unknown>> {
    const asset = await this.assets.findById(assetId);
    if (!asset) {
      throw new NotFoundException('Asset not found');
    }

    return {
      asset,
      links: {
        download: this.signedUrls.buildDownloadUrl(asset),
      },
    };
  }

  @Get(':assetId/download')
  async download(
    @Param('assetId') assetId: string,
    @Query('expires') expires: string,
    @Query('disposition') disposition = 'inline',
    @Query('signature') signature = '',
  ): Promise<Record<string, unknown>> {
    const asset = await this.assets.findById(assetId);
    if (!asset) {
      throw new NotFoundException('Asset not found');
    }

    const allowed = this.signedUrls.verify(asset, Number.parseInt(expires, 10), disposition, signature);
    if (!allowed) {
      throw new UnauthorizedException('Invalid or expired signature');
    }

    return {
      assetId: asset.id,
      storageDisk: asset.storageDisk,
      storageKey: asset.storageKey,
      disposition,
    };
  }
}
