<?php

namespace App\Filament\Platform\Resources\TenantSubscriptions;

use App\Enums\SubscriptionStatus;
use App\Filament\Platform\Resources\TenantSubscriptions\Pages\CreateTenantSubscription;
use App\Filament\Platform\Resources\TenantSubscriptions\Pages\EditTenantSubscription;
use App\Filament\Platform\Resources\TenantSubscriptions\Pages\ListTenantSubscriptions;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class TenantSubscriptionResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = TenantSubscription::class;

    protected static ?string $permissionPrefix = 'platform.subscriptions';

    protected static string|UnitEnum|null $navigationGroup = 'Finance & Comptabilité';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Souscriptions';

    protected static ?string $modelLabel = 'Souscription';

    protected static ?string $pluralModelLabel = 'Souscriptions';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Souscription')->schema([
                    Select::make('tenant_id')
                        ->label('Tenant')
                        ->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->columnSpan(2),
                    Select::make('plan_id')
                        ->label('Plan')
                        ->options(fn (): array => Plan::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->columnSpan(2),
                    Select::make('status')
                        ->label('Statut')
                        ->options(SubscriptionStatus::options())
                        ->default(SubscriptionStatus::Active->value)
                        ->required()
                        ->columnSpan(2),
                    DateTimePicker::make('started_at')->label('Début')->columnSpan(2),
                    DateTimePicker::make('trial_ends_at')->label('Fin essai')->columnSpan(2),
                    DateTimePicker::make('ends_at')->label('Fin')->columnSpan(1),
                    DateTimePicker::make('cancelled_at')->label('Annulée le')->columnSpan(1),
                    KeyValue::make('meta')->label('Meta')->columnSpanFull(),
                ])->columns(6),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('started_at')
                    ->label('Début')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('trial_ends_at')
                    ->label('Fin essai')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Fin')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('cancelled_at')
                    ->label('Annulée le')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(SubscriptionStatus::options()),
                SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(fn (): array => Plan::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
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
            'index' => ListTenantSubscriptions::route('/'),
            'create' => CreateTenantSubscription::route('/create'),
            'edit' => EditTenantSubscription::route('/{record}/edit'),
        ];
    }
}
