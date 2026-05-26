import { Body, Controller, Inject, Post } from '@nestjs/common';
import { normalizeAnalyticsEvent } from '../events/analytics-event';
import { ANALYTICS_EVENTS_REPOSITORY, AnalyticsEventsRepository } from './analytics-events.repository';

@Controller('events')
export class IngestionController {
  constructor(@Inject(ANALYTICS_EVENTS_REPOSITORY) private readonly events: AnalyticsEventsRepository) {}

  @Post()
  async ingest(@Body() body: Record<string, unknown>): Promise<Record<string, unknown>> {
    const event = normalizeAnalyticsEvent(body);
    await this.events.ingest(event);

    return { status: 'accepted', event_id: event.eventId };
  }
}
