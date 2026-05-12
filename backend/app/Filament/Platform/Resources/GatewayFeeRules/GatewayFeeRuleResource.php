<?php

namespace App\Filament\Platform\Resources\GatewayFeeRules;

use App\Enums\FeeCalculationMode;
use App\Enums\FeeChargeBearer;
use App\Enums\PaymentChannel;
use App\Enums\RefundFeeBehavior;
use App\Filament\Platform\Resources\GatewayFeeRules\Pages\CreateGatewayFeeRule;
use App\Filament\Platform\Resources\GatewayFeeRules\Pages\EditGatewayFeeRule;
use App\Filament\Platform\Resources\GatewayFeeRules\Pages\ListGatewayFeeRules;
use App\Models\Country;
use App\Models\Currency;
use App\Models\GatewayFeeRule;
use App\Models\PaymentGateway;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class GatewayFeeRuleResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = GatewayFeeRule::class;

    protected static ?string $permissionPrefix = 'platform.gateway_fee_rules';

    protected static string|UnitEnum|null $navigationGroup = 'Finance plateforme';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Frais gateway';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Règle de frais gateway';

    protected static ?string $pluralModelLabel = 'Règles de frais gateway';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ciblage')->schema([
                    TextInput::make('public_id')
                        ->default(fn (): string => (string) Str::uuid())
                        ->disabled()
                        ->dehydrated()
                        ->columnSpan(2),
                    Select::make('payment_gateway_id')
                        ->label('Gateway')
                        ->options(static::gatewayOptions())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(2),
                    Toggle::make('is_active')
                        ->label('Actif')
                        ->default(true)
                        ->inline(false)
                        ->columnSpan(1),
                    TextInput::make('priority')
                        ->label('Priorité')
                        ->numeric()
                        ->default(100)
                        ->minValue(0)
                        ->required()
                        ->columnSpan(1),
                    TextInput::make('name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(3),
                    Select::make('country_code')
                        ->label('Pays')
                        ->options(static::countryOptions())
                        ->searchable()
                        ->preload()
                        ->placeholder('Tous les pays')
                        ->columnSpan(1),
                    Select::make('currency_code')
                        ->label('Devise')
                        ->options(static::currencyOptions())
                        ->searchable()
                        ->preload()
                        ->placeholder('Toutes les devises')
                        ->columnSpan(1),
                    Select::make('payment_channel')
                        ->label('Canal')
                        ->options(PaymentChannel::options())
                        ->placeholder('Tous les canaux')
                        ->columnSpan(1),
                    DateTimePicker::make('effective_from')
                        ->label('Actif à partir du')
                        ->seconds(false)
                        ->columnSpan(2),
                    DateTimePicker::make('effective_to')
                        ->label('Actif jusqu’au')
                        ->seconds(false)
                        ->columnSpan(2),
                ])->columns(6),
                Section::make('Calcul')->schema([
                    Select::make('charge_bearer')
                        ->label('Porteur des frais')
                        ->options(FeeChargeBearer::options())
                        ->helperText('Pour ne pas impacter l’organisateur, choisissez Plateforme et gardez le checkout sans frais côté acheteur.')
                        ->default(FeeChargeBearer::Platform->value)
                        ->required()
                        ->columnSpan(2),
                    Select::make('fee_mode')
                        ->label('Mode de calcul')
                        ->options(FeeCalculationMode::options())
                        ->default(FeeCalculationMode::Percentage->value)
                        ->required()
                        ->columnSpan(2),
                    Toggle::make('is_gross_up_enabled')
                        ->label('Gross-up côté acheteur')
                        ->helperText('À activer si le total payé par l’acheteur doit absorber automatiquement les frais du gateway.')
                        ->default(false)
                        ->inline(false)
                        ->columnSpan(2),
                    TextInput::make('percentage_rate')
                        ->label('Taux (%)')
                        ->numeric()
                        ->step('0.0001')
                        ->minValue(0)
                        ->columnSpan(2),
                    TextInput::make('fixed_amount')
                        ->label('Montant fixe')
                        ->numeric()
                        ->minValue(0)
                        ->columnSpan(2),
                    TextInput::make('cap_amount')
                        ->label('Plafond')
                        ->numeric()
                        ->minValue(0)
                        ->columnSpan(1),
                    TextInput::make('vat_rate')
                        ->label('TVA sur frais (%)')
                        ->numeric()
                        ->step('0.0001')
                        ->minValue(0)
                        ->columnSpan(1),
                    Select::make('refund_behavior')
                        ->label('Politique de remboursement')
                        ->options(RefundFeeBehavior::options())
                        ->default(RefundFeeBehavior::NonRefundable->value)
                        ->required()
                        ->columnSpan(1),
                    KeyValue::make('meta')
                        ->label('Meta')
                        ->columnSpanFull(),
                ])->columns(6),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('paymentGateway.name')
                    ->label('Gateway')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('country_code')
                    ->label('Pays')
                    ->formatStateUsing(fn (?string $state): string => $state ?: 'Tous')
                    ->badge(),
                TextColumn::make('currency_code')
                    ->label('Devise')
                    ->formatStateUsing(fn (?string $state): string => $state ?: 'Toutes')
                    ->badge(),
                TextColumn::make('payment_channel')
                    ->label('Canal')
                    ->formatStateUsing(fn ($state): string => ($state?->value ?? $state) ?: 'Tous')
                    ->badge(),
                TextColumn::make('charge_bearer')
                    ->label('Porteur')
                    ->formatStateUsing(function ($state): string {
                        $value = $state?->value ?? $state;

                        return FeeChargeBearer::options()[$value] ?? (string) $value;
                    })
                    ->badge(),
                TextColumn::make('fee_mode')
                    ->label('Calcul')
                    ->formatStateUsing(function ($state): string {
                        $value = $state?->value ?? $state;

                        return FeeCalculationMode::options()[$value] ?? (string) $value;
                    })
                    ->badge(),
                TextColumn::make('refund_behavior')
                    ->label('Remboursement')
                    ->formatStateUsing(function ($state): string {
                        $value = $state?->value ?? $state;

                        return RefundFeeBehavior::options()[$value] ?? (string) $value;
                    })
                    ->badge(),
                TextColumn::make('percentage_rate')
                    ->label('Taux')
                    ->formatStateUsing(fn ($state): string => $state !== null ? number_format((float) $state, 4, ',', ' ') . ' %' : '-'),
                TextColumn::make('fixed_amount')
                    ->label('Fixe')
                    ->numeric(),
                TextColumn::make('cap_amount')
                    ->label('Plafond')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_gross_up_enabled')
                    ->label('Gross-up')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                TextColumn::make('priority')
                    ->label('Priorité')
                    ->numeric(),
            ])
            ->filters([
                SelectFilter::make('payment_gateway_id')
                    ->label('Gateway')
                    ->options(static::gatewayOptions())
                    ->searchable(),
                SelectFilter::make('country_code')
                    ->label('Pays')
                    ->options(static::countryOptions())
                    ->searchable(),
                SelectFilter::make('payment_channel')
                    ->label('Canal')
                    ->options(PaymentChannel::options()),
                SelectFilter::make('charge_bearer')
                    ->label('Porteur')
                    ->options(FeeChargeBearer::options()),
                SelectFilter::make('refund_behavior')
                    ->label('Remboursement')
                    ->options(RefundFeeBehavior::options()),
                SelectFilter::make('is_active')
                    ->label('Actif')
                    ->options([
                        '1' => 'Actif',
                        '0' => 'Inactif',
                    ]),
            ])
            ->defaultSort('priority')
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
            'index' => ListGatewayFeeRules::route('/'),
            'create' => CreateGatewayFeeRule::route('/create'),
            'edit' => EditGatewayFeeRule::route('/{record}/edit'),
        ];
    }

    private static function gatewayOptions(): array
    {
        return PaymentGateway::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function countryOptions(): array
    {
        return Country::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Country $country): array => [$country->iso2 => sprintf('%s (%s)', $country->name, $country->iso2)])
            ->all();
    }

    private static function currencyOptions(): array
    {
        return Currency::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Currency $currency): array => [$currency->code => sprintf('%s (%s)', $currency->code, $currency->name)])
            ->all();
    }
}
