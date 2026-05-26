import { Injectable } from '@nestjs/common';

export type RenderedTemplate = {
  subject: string | null;
  body: string;
};

@Injectable()
export class TemplateRendererService {
  render(templateKey: string, context: Record<string, unknown>): RenderedTemplate {
    const title = stringValue(context.title) || stringValue(context.event_type) || templateKey;
    const reference = stringValue(context.reference) || stringValue(context.order_reference) || stringValue(context.payment_reference);

    return {
      subject: title,
      body: reference ? `${title}\nReference: ${reference}` : title,
    };
  }
}

function stringValue(value: unknown): string | null {
  return typeof value === 'string' && value.trim() !== '' ? value : null;
}
