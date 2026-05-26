export type MediaAsset = {
  id: string;
  tenantId: string | null;
  ownerType: string | null;
  ownerId: string | null;
  purpose: string;
  originalName: string;
  mimeType: string;
  byteSize: number;
  checksumSha256: string | null;
  storageDisk: string;
  storageKey: string;
  status: string;
  metadata: Record<string, unknown>;
};

export type CreateMediaAssetInput = {
  tenantId: string | null;
  ownerType?: string | null;
  ownerId?: string | null;
  purpose: string;
  originalName: string;
  mimeType: string;
  byteSize: number;
  checksumSha256?: string | null;
  storageDisk: string;
  storageKey: string;
  metadata?: Record<string, unknown>;
};
