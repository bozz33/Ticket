import { Body, Controller, Post } from '@nestjs/common';
import { DocumentJobsService } from './document-jobs.service';

type CreateDocumentJobBody = {
  tenantId?: string;
  templateKey: string;
  outputPurpose?: string;
  payload?: Record<string, unknown>;
};

@Controller('documents')
export class DocumentsController {
  constructor(private readonly jobs: DocumentJobsService) {}

  @Post('render-jobs')
  async createRenderJob(@Body() body: CreateDocumentJobBody): Promise<Record<string, unknown>> {
    return this.jobs.create({
      tenantId: body.tenantId ?? null,
      templateKey: body.templateKey,
      outputPurpose: body.outputPurpose ?? 'pdf',
      payload: body.payload ?? {},
    });
  }
}
