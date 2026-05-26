import { Injectable } from '@nestjs/common';

@Injectable()
export class PreferencesService {
  async isChannelAllowed(_recipient: string, _channel: string, _eventType: string): Promise<boolean> {
    return true;
  }
}
