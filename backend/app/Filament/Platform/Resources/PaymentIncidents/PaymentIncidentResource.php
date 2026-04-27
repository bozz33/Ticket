<?php

namespace App\Filament\Platform\Resources\PaymentIncidents;

use App\Filament\Platform\Resources\PaymentIncidents\Pages\ManagePaymentIncidents;
use App\Models\PaymentGateway;
use App\Models\PaymentIncident;
use App\Models\PlatformTransaction;
use App\Models\Tenant;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PaymentIncidentResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = PaymentIncident::class;

    protected static ?string $permissionPrefix = 'platform.payment_incidents';

    protected static string|UnitEnum|null $navigationGroup = 'Finance plateforme';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $recordTitleAttribute = 'summary';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Incident')->schema([
                    Select::make('tenant_id')->label('Tenant')->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                    Select::make('platform_transaction_id')->label('Transaction')->options(fn (): array => PlatformTransaction::query()->orderByDesc('id')->pluck('transaction_reference', 'id')->all())->searchable()->preload(),
                    Select::make('payment_gateway_id')->label('Gateway')->options(fn (): array => PaymentGateway::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                    Select::make('severity')->label('Sévérité')->options(['low' => 'Basse', 'medium' => 'Moyenne', 'high' => 'Haute', 'critical' => 'Critique'])->default('medium')->required(),
                    Select::make('status')->label('Statut')->options(['open' => 'Ouvert', 'investigating' => 'Investigation', 'resolved' => 'Résolu'])->default('open')->required(),
                    TextInput::make('incident_code')->label('Code incident')->maxLength(120),
                    DateTimePicker::make('detected_at')->label('Détecté le'),
                    DateTimePicker::make('resolved_at')->label('Résolu le'),
                    Textarea::make('summary')->label('Résumé')->required()->rows(3)->columnSpanFull(),
                    KeyValue::make('meta')->label('Meta')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('summary')
            ->columns([
                TextColumn::make('summary')
                    ->label('Résumé')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('severity')->label('Sévérité')->badge(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('tenant.name')->label('Tenant'),
                TextColumn::make('transaction.transaction_reference')->label('Transaction'),
                TextColumn::make('detected_at')->label('Détecté')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePaymentIncidents::route('/'),
        ];
    }
}
