<?php

namespace App\Filament\Tenant\Resources\CrowdfundingCampaigns;

use App\Filament\Tenant\Resources\CrowdfundingCampaigns\Pages\CreateCrowdfundingCampaign;
use App\Filament\Tenant\Resources\CrowdfundingCampaigns\Pages\EditCrowdfundingCampaign;
use App\Filament\Tenant\Resources\CrowdfundingCampaigns\Pages\ListCrowdfundingCampaigns;
use App\Filament\Tenant\Resources\CrowdfundingCampaigns\RelationManagers\ContributionOffersRelationManager;
use App\Filament\Tenant\Resources\CrowdfundingCampaigns\RelationManagers\ContributionsRelationManager;
use App\Filament\Tenant\Resources\CrowdfundingCampaigns\Schemas\CrowdfundingCampaignForm;
use App\Filament\Tenant\Resources\CrowdfundingCampaigns\Tables\CrowdfundingCampaignsTable;
use App\Models\CrowdfundingCampaign;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CrowdfundingCampaignResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = CrowdfundingCampaign::class;

    protected static ?string $permissionPrefix = 'tenant.catalog';

    protected static string|UnitEnum|null $navigationGroup = 'Création & modules';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Crowdfunding';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Campagne';

    protected static ?string $pluralModelLabel = 'Campagnes crowdfunding';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return CrowdfundingCampaignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrowdfundingCampaignsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrowdfundingCampaigns::route('/'),
            'create' => CreateCrowdfundingCampaign::route('/create'),
            'edit' => EditCrowdfundingCampaign::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ContributionOffersRelationManager::class,
            ContributionsRelationManager::class,
        ];
    }

    public static function canCreate(): bool
    {
        return static::allows('update');
    }

    public static function canEdit(Model $record): bool
    {
        return static::allows('update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::allows('update');
    }

    public static function canDeleteAny(): bool
    {
        return static::allows('update');
    }
}
