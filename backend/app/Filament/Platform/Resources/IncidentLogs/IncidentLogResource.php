<?php

namespace App\Filament\Platform\Resources\IncidentLogs;

use App\Filament\Platform\Resources\IncidentLogs\Pages\ManageIncidentLogs;
use App\Models\IncidentLog;
use App\Models\PaymentIncident;
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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class IncidentLogResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = IncidentLog::class;

    protected static ?string $permissionPrefix = 'platform.incidents';

    protected static string|UnitEnum|null $navigationGroup = 'Support & audit';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bug-ant';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Incident')->schema([
                    Select::make('tenant_id')->label('Tenant')->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                    Select::make('payment_incident_id')->label('Incident paiement')->options(fn (): array => PaymentIncident::query()->orderByDesc('id')->pluck('summary', 'id')->all())->searchable()->preload(),
                    Select::make('platform_support_ticket_id')->label('Ticket support')->options(fn (): array => PlatformSupportTicket::query()->orderByDesc('id')->pluck('reference', 'id')->all())->searchable()->preload(),
                    TextInput::make('title')->label('Titre')->required()->maxLength(255),
                    Select::make('severity')->label('Sévérité')->options(['low' => 'Basse', 'medium' => 'Moyenne', 'high' => 'Haute', 'critical' => 'Critique'])->default('medium')->required(),
                    Select::make('status')->label('Statut')->options(['open' => 'Ouvert', 'monitoring' => 'Monitoring', 'resolved' => 'Résolu'])->default('open')->required(),
                    TextInput::make('incident_type')->label('Type')->maxLength(120),
                    DateTimePicker::make('detected_at')->label('Détecté le'),
                    DateTimePicker::make('resolved_at')->label('Résolu le'),
                    Textarea::make('summary')->label('Résumé')->rows(3)->columnSpanFull(),
                    KeyValue::make('meta')->label('Meta')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable(),
                TextColumn::make('tenant.name')->label('Tenant'),
                TextColumn::make('severity')->label('Sévérité')->badge(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('incident_type')->label('Type')->badge(),
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
            'index' => ManageIncidentLogs::route('/'),
        ];
    }
}
