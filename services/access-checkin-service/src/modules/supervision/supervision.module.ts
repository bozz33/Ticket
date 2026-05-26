import { Module } from '@nestjs/common';
import { DatabaseModule } from '../../shared/database/database.module';
import { SupervisionController } from './supervision.controller';

@Module({
  imports: [DatabaseModule],
  controllers: [SupervisionController],
})
export class SupervisionModule {}
