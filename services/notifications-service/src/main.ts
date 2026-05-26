import 'reflect-metadata';
import { Logger, ValidationPipe } from '@nestjs/common';
import { NestFactory } from '@nestjs/core';
import { AppModule } from './app.module';
import { serviceConfig } from './shared/config/service.config';
import { internalTokenMiddleware } from './shared/security/internal-token.middleware';

async function bootstrap(): Promise<void> {
  const app = await NestFactory.create(AppModule, { bufferLogs: true });
  const config = serviceConfig();

  app.setGlobalPrefix('v1');
  app.use(internalTokenMiddleware);
  app.useGlobalPipes(new ValidationPipe({ whitelist: true, transform: true }));
  app.enableShutdownHooks();

  await app.listen(config.port, '0.0.0.0');

  Logger.log(`notifications-service listening on :${config.port}`, 'Bootstrap');
}

void bootstrap();

