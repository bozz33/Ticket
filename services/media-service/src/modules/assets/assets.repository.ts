import { CreateMediaAssetInput, MediaAsset } from './asset.types';

export interface AssetsRepository {
  create(input: CreateMediaAssetInput): Promise<MediaAsset>;
  findById(id: string): Promise<MediaAsset | null>;
  markUploaded(id: string, checksumSha256?: string | null): Promise<void>;
}

export const ASSETS_REPOSITORY = Symbol('ASSETS_REPOSITORY');
