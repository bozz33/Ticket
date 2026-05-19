<?php

namespace App\Filament\Platform\Resources\PaymentGateways;

use App\Enums\PaymentChannel;
use App\Filament\Platform\Resources\PaymentGateways\Pages\CreatePaymentGateway;
use App\Filament\Platform\Resources\PaymentGateways\Pages\EditPaymentGateway;
use App\Filament\Platform\Resources\PaymentGateways\Pages\ListPaymentGateways;
use App\Models\Country;
use App\Models\Currency;
use App\Models\PaymentGateway;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

class PaymentGatewayResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = PaymentGateway::class;

    protected static ?string $permissionPrefix = 'platform.payment_gateways';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Payment Gateways';

    protected static ?string $modelLabel = 'Gateway de paiement';

    protected static ?string $pluralModelLabel = 'Gateways de paiement';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gateway')->schema([
                    TextInput::make('public_id')->default(fn () => (string) Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                    TextInput::make('code')->label('Code')->required()->maxLength(80)->columnSpan(1),
                    TextInput::make('name')->label('Nom')->required()->maxLength(255)->columnSpan(2),
                    TextInput::make('provider')->label('Provider')->required()->maxLength(120)->columnSpan(1),
                    Select::make('mode')->label('Mode')->options(['test' => 'Test', 'live' => 'Live'])->default('live')->required()->columnSpan(1),
                    Toggle::make('is_active')->label('Actif')->default(false)->inline(false)->columnSpan(1),
                    TextInput::make('public_key')
                        ->label('Clé publique')
                        ->maxLength(255)
                        ->columnSpan(2),
                    TextInput::make('secret_key')
                        ->label('Clé secrète')
                        ->password()
                        ->autocomplete('new-password')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->afterStateHydrated(fn (TextInput $component): mixed => $component->state(null))
                        ->helperText('Stockage chiffré. En production, privilégiez PAYSTACK_SECRET_KEY dans .env et laissez ce champ vide.')
                        ->maxLength(255)
                        ->columnSpan(2),
                    TextInput::make('webhook_secret')
                        ->label('Secret webhook')
                        ->password()
                        ->autocomplete('new-password')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->afterStateHydrated(fn (TextInput $component): mixed => $component->state(null))
                        ->helperText('Stockage chiffré. Laisser vide conserve la valeur existante.')
                        ->maxLength(255)
                        ->columnSpan(2),
                    Select::make('supported_countries')
                        ->label('Pays supportés')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn (): array => Country::query()
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (Country $country): array => [$country->iso2 => sprintf('%s (%s)', $country->name, $country->iso2)])
                            ->all())
                        ->helperText('Ces pays limitent les règles de frais et le checkout proposé par ce gateway.')
                        ->columnSpan(3),
                    Select::make('supported_currencies')
                        ->label('Devises supportées')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn (): array => Currency::query()
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn (Currency $currency): array => [$currency->code => sprintf('%s (%s)', $currency->code, $currency->name)])
                            ->all())
                        ->columnSpan(3),
                    Select::make('supported_channels')
                        ->label('Canaux supportés')
                        ->multiple()
                        ->options(PaymentChannel::options())
                        ->helperText('Le moteur de frais peut différencier carte, mobile money, virement, wallet, etc.')
                        ->columnSpanFull(),
                    KeyValue::make('meta')->label('Meta')->columnSpanFull(),
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
                TextColumn::make('code')->label('Code')->searchable(),
                TextColumn::make('provider')->label('Provider')->badge(),
                TextColumn::make('mode')->label('Mode')->badge(),
                TextColumn::make('supported_countries')
                    ->label('Pays')
                    ->formatStateUsing(fn ($state): string => collect((array) $state)->implode(', ') ?: '-')
                    ->toggleable(),
                TextColumn::make('supported_channels')
                    ->label('Canaux')
                    ->formatStateUsing(fn ($state): string => collect((array) $state)
                        ->map(fn ($channel): string => PaymentChannel::options()[$channel] ?? (string) $channel)
                        ->implode(', ') ?: '-')
                    ->toggleable(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('mode')
                    ->label('Mode')
                    ->options([
                        'test' => 'Test',
                        'live' => 'Live',
                    ]),
                SelectFilter::make('is_active')
                    ->label('Actif')
                    ->options([
                        '1' => 'Actif',
                        '0' => 'Inactif',
                    ]),
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
            'index' => ListPaymentGateways::route('/'),
            'create' => CreatePaymentGateway::route('/create'),
            'edit' => EditPaymentGateway::route('/{record}/edit'),
        ];
    }
}
