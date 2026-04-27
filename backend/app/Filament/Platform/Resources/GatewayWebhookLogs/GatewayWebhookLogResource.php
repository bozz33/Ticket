<?php

namespace App\Filament\Platform\Resources\GatewayWebhookLogs;

use App\Filament\Platform\Resources\GatewayWebhookLogs\Pages\ManageGatewayWebhookLogs;
use App\Models\GatewayWebhookLog;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class GatewayWebhookLogResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = GatewayWebhookLog::class;

    protected static ?string $permissionPrefix = 'platform.webhook_logs';

    protected static string|UnitEnum|null $navigationGroup = 'Observabilité';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static ?string $recordTitleAttribute = 'external_id';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Webhook')->schema([
                    TextInput::make('event_name')->label('Événement')->disabled(),
                    TextInput::make('external_id')->label('ID externe')->disabled(),
                    TextInput::make('status')->label('Statut')->disabled(),
                    TextInput::make('response_code')->label('Code réponse')->disabled(),
                    KeyValue::make('headers')->label('Headers')->disabled()->columnSpanFull(),
                    KeyValue::make('payload')->label('Payload')->disabled()->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('external_id')
            ->columns([
                TextColumn::make('event_name')->label('Événement')->searchable(),
                TextColumn::make('paymentGateway.name')->label('Gateway'),
                TextColumn::make('external_id')->label('ID externe')->searchable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('response_code')->label('Code'),
                TextColumn::make('processed_at')->label('Traité')->dateTime(),
            ])
            ->filters([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageGatewayWebhookLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
