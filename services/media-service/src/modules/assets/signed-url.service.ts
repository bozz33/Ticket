import { Injectable } from '@nestjs/common';
import { serviceConfig } from '../../shared/config/service.config';
import { signValue, verifySignature } from '../../shared/crypto/signing';
import { MediaAsset } from './asset.types';

@Injectable()
export class SignedUrlService {
  private readonly config = serviceConfig();

  buildDownloadUrl(asset: MediaAsset, disposition = 'inline'): string {
    const expires = Math.floor(Date.now() / 1000) + this.config.signedUrlTtlSeconds;
    const payload = this.signaturePayload(asset.id, asset.storageKey, expires, disposition);
    const signature = signValue(payload, this.config.signingSecret);
    const params = new URLSearchParams({ expires: String(expires), disposition, signature });

    return `${this.config.publicBaseUrl.replace(/\/$/, '')}/v1/assets/${asset.id}/download?${params.toString()}`;
  }

  verify(asset: MediaAsset, expires: number, disposition: string, signature: string): boolean {
    if (!Number.isFinite(expires) || expires < Math.floor(Date.now() / 1000)) {
      return false;
    }

    return verifySignature(
      this.signaturePayload(asset.id, asset.storageKey, expires, disposition),
      signature,
      this.config.signingSecret,
    );
  }

  private signaturePayload(assetId: string, storageKey: string, expires: number, disposition: string): string {
    return [assetId, storageKey, expires, disposition].join('|');
  }
}
