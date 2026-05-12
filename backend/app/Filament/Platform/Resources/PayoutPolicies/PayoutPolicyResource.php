<?php

namespace App\Filament\Platform\Resources\PayoutPolicies;

use App\Enums\FeeCalculationMode;
use App\Enums\FeeChargeBearer;
use App\Filament\Platform\Resources\PayoutPolicies\Pages\CreatePayoutPolicy;
use App\Filament\Platform\Resources\PayoutPolicies\Pages\EditPayoutPolicy;
use App\Filament\Platform\Resources\PayoutPolicies\Pages\ListPayoutPolicies;
use App\Models\Country;
use App\Models\Currency;
use App\Models\PayoutPolicy;
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

class PayoutPolicyResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = PayoutPolicy::class;

    protected static ?string $permissionPrefix = 'platform.payout_policies';

    protected static string|UnitEnum|null $navigationGroup = 'Finance plateforme';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Politiques de reversement';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Politique de reversement';

    protected static ?string $pluralModelLabel = 'Politiques de reversement';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Périmètre')->schema([
                    TextInput::make('public_id')
                        ->default(fn (): string => (string) Str::uuid())
                        ->disabled()
                        ->dehydrated()
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
                    Select::make('tenant_id')
                        ->label('Tenant')
                        ->options(static::tenantOptions())
                        ->searchable()
                        ->preload()
                        ->placeholder('Tous les tenants')
                        ->columnSpan(2),
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
                        ->columnSpan(2),
                    Select::make('currency_code')
                        ->label('Devise')
                        ->options(static::currencyOptions())
                        ->searchable()
                        ->preload()
                        ->placeholder('Toutes les devises')
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
                Section::make('Règles de reversement')->schema([
                    TextInput::make('minimum_payout_amount')
                        ->label('Montant minimum')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required()
                        ->columnSpan(2),
                    TextInput::make('reserve_rate')
                        ->label('Réserve (%)')
                        ->numeric()
                        ->step('0.0001')
                        ->minValue(0)
                        ->default(0)
                        ->columnSpan(1),
                    TextInput::make('reserve_days')
                        ->label('Jours de réserve')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->columnSpan(1),
                    TextInput::make('payout_delay_days')
                        ->label('Délai avant reversement (jours)')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->columnSpan(2),
                    Select::make('charge_bearer')
                        ->label('Porteur des frais')
                        ->options(FeeChargeBearer::options())
                        ->helperText('Avec votre règle actuelle, aucun frais additionnel de reversement n’est appliqué. Le net organisateur est déjà calculé avant cette étape.')
                        ->default(FeeChargeBearer::Organizer->value)
                        ->required()
                        ->columnSpan(2),
                    Select::make('payout_fee_mode')
                        ->label('Mode de calcul des frais')
                        ->options(FeeCalculationMode::options())
                        ->placeholder('Aucun frais de reversement')
                        ->helperText('Laissez vide si seule la commission plateforme de 10% doit être retenue.')
                        ->columnSpan(2),
                    TextInput::make('payout_fee_percentage')
                        ->label('Taux reversement (%)')
                        ->numeric()
                        ->step('0.0001')
                        ->minValue(0)
                        ->columnSpan(1),
                    TextInput::make('payout_fee_fixed')
                        ->label('Montant fixe reversement')
                        ->numeric()
                        ->minValue(0)
                        ->columnSpan(1),
                    TextInput::make('payout_fee_cap')
                        ->label('Plafond reversement')
                        ->numeric()
                        ->minValue(0)
                        ->columnSpan(2),
                    Toggle::make('auto_payout_enabled')
                        ->label('Reversement automatique')
                        ->default(false)
                        ->inline(false)
                        ->columnSpan(1),
                    Toggle::make('requires_manual_review')
                        ->label('Validation manuelle requise')
                        ->default(true)
                        ->inline(false)
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
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->formatStateUsing(fn (?string $state): string => $state ?: 'Global')
                    ->badge(),
                TextColumn::make('country_code')
                    ->label('Pays')
                    ->formatStateUsing(fn (?string $state): string => $state ?: 'Tous')
                    ->badge(),
                TextColumn::make('currency_code')
                    ->label('Devise')
                    ->formatStateUsing(fn (?string $state): string => $state ?: 'Toutes')
                    ->badge(),
                TextColumn::make('minimum_payout_amount')
                    ->label('Minimum')
                    ->numeric(),
                TextColumn::make('reserve_rate')
                    ->label('Réserve')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 4, ',', ' ') . ' %'),
                TextColumn::make('payout_delay_days')
                    ->label('Délai')
                    ->formatStateUsing(fn ($state): string => number_format((int) $state, 0, ',', ' ') . ' j'),
                TextColumn::make('payout_fee_mode')
                    ->label('Mode frais')
                    ->formatStateUsing(function ($state): string {
                        $value = $state?->value ?? $state;

                        return FeeCalculationMode::options()[$value] ?? ($value ?: 'Aucun');
                    })
                    ->badge(),
                TextColumn::make('payout_fee_percentage')
                    ->label('Taux frais')
                    ->formatStateUsing(fn ($state): string => $state !== null ? number_format((float) $state, 4, ',', ' ') . ' %' : '-'),
                IconColumn::make('auto_payout_enabled')
                    ->label('Auto')
                    ->boolean(),
                IconColumn::make('requires_manual_review')
                    ->label('Validation')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->options(static::tenantOptions())
                    ->searchable(),
                SelectFilter::make('country_code')
                    ->label('Pays')
                    ->options(static::countryOptions())
                    ->searchable(),
                SelectFilter::make('currency_code')
                    ->label('Devise')
                    ->options(static::currencyOptions())
                    ->searchable(),
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
            'index' => ListPayoutPolicies::route('/'),
            'create' => CreatePayoutPolicy::route('/create'),
            'edit' => EditPayoutPolicy::route('/{record}/edit'),
        ];
    }

    private static function tenantOptions(): array
    {
        return Tenant::query()
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
