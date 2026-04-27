<?php

namespace App\Filament\Platform\Resources\PlatformSupportTickets;

use App\Filament\Platform\Resources\PlatformSupportTickets\Pages\ManagePlatformSupportTickets;
use App\Models\PlatformUser;
use App\Models\PlatformSupportTicket;
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
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PlatformSupportTicketResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = PlatformSupportTicket::class;

    protected static ?string $permissionPrefix = 'platform.support_tickets';

    protected static string|UnitEnum|null $navigationGroup = 'Support & audit';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket support')->schema([
                    Select::make('tenant_id')->label('Tenant')->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                    Select::make('platform_user_id')->label('Assigné à')->options(fn (): array => PlatformUser::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                    TextInput::make('reference')->label('Référence')->required()->maxLength(255),
                    TextInput::make('subject')->label('Sujet')->required()->maxLength(255),
                    TextInput::make('requester_name')->label('Demandeur')->maxLength(255),
                    TextInput::make('requester_email')->label('Email demandeur')->email()->maxLength(255),
                    Select::make('status')->label('Statut')->options(['open' => 'Ouvert', 'pending' => 'En attente', 'resolved' => 'Résolu'])->default('open')->required(),
                    Select::make('priority')->label('Priorité')->options(['low' => 'Basse', 'normal' => 'Normale', 'high' => 'Haute', 'critical' => 'Critique'])->default('normal')->required(),
                    TextInput::make('category')->label('Catégorie')->maxLength(120),
                    DateTimePicker::make('opened_at')->label('Ouvert le'),
                    DateTimePicker::make('last_activity_at')->label('Dernière activité'),
                    DateTimePicker::make('resolved_at')->label('Résolu le'),
                    KeyValue::make('meta')->label('Meta')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable(),
                TextColumn::make('subject')->label('Sujet')->searchable(),
                TextColumn::make('tenant.name')->label('Tenant'),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('priority')->label('Priorité')->badge(),
                TextColumn::make('assignee.name')->label('Assigné à'),
                TextColumn::make('last_activity_at')->label('Activité')->dateTime(),
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
            'index' => ManagePlatformSupportTickets::route('/'),
        ];
    }
}
