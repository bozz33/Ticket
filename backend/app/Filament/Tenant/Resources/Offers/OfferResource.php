<?php

namespace App\Filament\Tenant\Resources\Offers;

use App\Filament\Tenant\Resources\Offers\Pages\ManageOffers;
use App\Models\CallForProject;
use App\Models\CrowdfundingCampaign;
use App\Models\Event;
use App\Models\Offer;
use App\Models\Stand;
use App\Models\Training;
use App\Services\SubscriptionGateService;
use App\Support\Filament\Concerns\HasPanelPermission;
use App\Support\Tenancy\TenantContext;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class OfferResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = Offer::class;

    protected static ?string $permissionPrefix = 'tenant.catalog';

    protected static string|UnitEnum|null $navigationGroup = 'Ventes';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Tickets & offres';

    protected static ?string $modelLabel = 'Offre';

    protected static ?string $pluralModelLabel = 'Tickets & offres';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cible')->schema([
                    Select::make('offerable_type')
                        ->label('Module')
                        ->options(fn (): array => static::offerableTypeOptions())
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn ($state, $set) => $set('offerable_id', null)),
                    Select::make('offerable_id')
                        ->label('Contenu lié')
                        ->options(fn ($get): array => static::offerableRecordOptions((string) $get('offerable_type')))
                        ->searchable()
                        ->preload()
                        ->disabled(fn ($get): bool => blank($get('offerable_type')))
                        ->required(),
                ])->columns(2),
                Section::make('Offre')->schema([
                    TextInput::make('offer_type')
                        ->label('Type d’offre')
                        ->required()
                        ->default('standard')
                        ->maxLength(100),
                    TextInput::make('name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('code')
                        ->label('Code')
                        ->maxLength(100)
                        ->unique(ignoreRecord: true),
                    TextInput::make('currency_code')
                        ->label('Devise')
                        ->default('XOF')
                        ->maxLength(3),
                    TextInput::make('price_amount')
                        ->label('Prix')
                        ->numeric()
                        ->default(0),
                    TextInput::make('quantity_total')
                        ->label('Stock total')
                        ->numeric(),
                    TextInput::make('min_per_order')
                        ->label('Minimum par commande')
                        ->numeric()
                        ->default(1),
                    TextInput::make('max_per_order')
                        ->label('Maximum par commande')
                        ->numeric(),
                    DateTimePicker::make('sales_start_at')->label('Début de vente'),
                    DateTimePicker::make('sales_end_at')->label('Fin de vente'),
                    Textarea::make('description')->label('Description')->rows(4)->columnSpanFull(),
                    Toggle::make('is_active')->label('Actif')->default(true),
                    TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
                    KeyValue::make('meta')->label('Métadonnées')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable(),
                TextColumn::make('offerable_label')->label('Module')->badge(),
                TextColumn::make('offerable_title')->label('Contenu lié'),
                TextColumn::make('offer_type')->label('Type')->badge(),
                TextColumn::make('price_amount')->label('Prix')->numeric(),
                TextColumn::make('quantity_total')->label('Stock')->numeric(),
                TextColumn::make('quantity_sold')->label('Vendus')->numeric(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
                TextColumn::make('updated_at')->label('Mis à jour')->since(),
            ])
            ->defaultSort('sort_order')
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
            'index' => ManageOffers::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::hasAtLeastOneSellableFeature() && static::canAccess();
    }

    public static function canAccess(): bool
    {
        return static::hasAtLeastOneSellableFeature() && static::allows('view');
    }

    public static function canViewAny(): bool
    {
        return static::hasAtLeastOneSellableFeature() && static::allows('view');
    }

    public static function canCreate(): bool
    {
        return static::hasAtLeastOneSellableFeature() && static::allows('update');
    }

    public static function canEdit(Model $record): bool
    {
        return static::hasAtLeastOneSellableFeature() && static::allows('update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::hasAtLeastOneSellableFeature() && static::allows('update');
    }

    public static function canDeleteAny(): bool
    {
        return static::hasAtLeastOneSellableFeature() && static::allows('update');
    }

    protected static function hasAtLeastOneSellableFeature(): bool
    {
        $tenant = app(TenantContext::class)->get();

        if ($tenant === null) {
            return false;
        }

        $gate = app(SubscriptionGateService::class);

        foreach (array_keys(static::offerableFeatureMap()) as $feature) {
            if ($gate->allowsModule($tenant, $feature)) {
                return true;
            }
        }

        return false;
    }

    protected static function offerableTypeOptions(): array
    {
        $tenant = app(TenantContext::class)->get();

        if ($tenant === null) {
            return [];
        }

        $gate = app(SubscriptionGateService::class);
        $options = [];

        foreach (static::offerableFeatureMap() as $feature => $definition) {
            if ($gate->allowsModule($tenant, $feature)) {
                $options[$definition['class']] = $definition['label'];
            }
        }

        return $options;
    }

    protected static function offerableRecordOptions(string $type): array
    {
        return match ($type) {
            Event::class => Event::query()->orderBy('title')->pluck('title', 'id')->all(),
            Stand::class => Stand::query()->orderBy('name')->pluck('name', 'id')->all(),
            Training::class => Training::query()->orderBy('title')->pluck('title', 'id')->all(),
            CallForProject::class => CallForProject::query()->orderBy('title')->pluck('title', 'id')->all(),
            CrowdfundingCampaign::class => CrowdfundingCampaign::query()->orderBy('title')->pluck('title', 'id')->all(),
            default => [],
        };
    }

    protected static function offerableFeatureMap(): array
    {
        return [
            'tenant.ticketing' => ['class' => Event::class, 'label' => 'Événement'],
            'tenant.stands' => ['class' => Stand::class, 'label' => 'Stand'],
            'tenant.training' => ['class' => Training::class, 'label' => 'Formation'],
            'tenant.calls_for_projects' => ['class' => CallForProject::class, 'label' => 'Appel à projets'],
            'tenant.crowdfunding' => ['class' => CrowdfundingCampaign::class, 'label' => 'Crowdfunding'],
        ];
    }
}
