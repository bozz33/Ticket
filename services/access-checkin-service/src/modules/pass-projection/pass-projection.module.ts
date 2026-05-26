import { Module } from '@nestjs/common';
import { DatabaseModule } from '../../shared/database/database.module';
import { PassProjectionController } from './pass-projection.controller';

@Module({
  imports: [DatabaseModule],
  controllers: [PassProjectionController],
})
export class PassProjectionModule {}
