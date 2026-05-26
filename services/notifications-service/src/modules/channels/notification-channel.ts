export type NotificationChannelName = 'email' | 'sms' | 'in-app';

export type NotificationSendInput = {
  recipient: string;
  subject: string | null;
  body: string;
  tenantId: string | null;
  metadata: Record<string, unknown>;
};

export type NotificationSendResult = {
  providerReference: string | null;
};

export interface NotificationChannel {
  readonly name: NotificationChannelName;
  send(input: NotificationSendInput): Promise<NotificationSendResult>;
}
