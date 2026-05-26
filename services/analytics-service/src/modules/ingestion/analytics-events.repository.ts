import { AnalyticsEvent } from '../events/analytics-event';

export interface AnalyticsEventsRepository {
  ingest(event: AnalyticsEvent): Promise<void>;
}

export const ANALYTICS_EVENTS_REPOSITORY = Symbol('ANALYTICS_EVENTS_REPOSITORY');
