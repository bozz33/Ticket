import 'reflect-metadata';
import { NestFactory } from '@nestjs/core';
import { AppModule } from './app.module';
import { serviceConfig } from './shared/config/service.config';
import { internalTokenMiddleware } from './shared/security/internal-token.middleware';

async function bootstrap(): Promise<void> {
  const app = await NestFactory.create(AppModule);
  app.setGlobalPrefix('v1');
  app.use(internalTokenMiddleware);
  await app.listen(serviceConfig().port);
}

void bootstrap();

