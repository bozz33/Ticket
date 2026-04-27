<?php

namespace App\Filament\Platform\Resources\Settlements;

use App\Filament\Platform\Resources\Settlements\Pages\ManageSettlements;
use App\Models\PayoutBatch;
use App\Models\Settlement;
use App\Models\Tenant;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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

class SettlementResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = Settlement::class;

    protected static ?string $permissionPrefix = 'platform.settlements';

    protected static string|UnitEnum|null $navigationGroup = 'Finance plateforme';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Demandes de reversement';

    protected static ?string $modelLabel = 'Demande de reversement';

    protected static ?string $pluralModelLabel = 'Demandes de reversement';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Reversement')->schema([
                    Select::make('tenant_id')->label('Tenant')->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())->required()->searchable()->preload(),
                    Select::make('payout_batch_id')->label('Batch')->options(fn (): array => PayoutBatch::query()->orderByDesc('id')->pluck('reference', 'id')->all())->searchable()->preload(),
                    TextInput::make('reference')->label('Référence')->required()->maxLength(255),
                    Select::make('status')->label('Statut')->options(['draft' => 'Brouillon', 'scheduled' => 'Planifié', 'paid' => 'Payé', 'failed' => 'Échec'])->default('draft')->required(),
                    DatePicker::make('period_start')->label('Période début'),
                    DatePicker::make('period_end')->label('Période fin'),
                    TextInput::make('gross_amount')->label('Brut')->numeric()->default(0)->required(),
                    TextInput::make('fee_amount')->label('Frais')->numeric()->default(0)->required(),
                    TextInput::make('net_amount')->label('Net')->numeric()->default(0)->required(),
                    TextInput::make('currency_code')->label('Devise')->default('XOF')->maxLength(3)->required(),
                    DateTimePicker::make('scheduled_at')->label('Planifié le'),
                    DateTimePicker::make('paid_at')->label('Payé le'),
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
                TextColumn::make('tenant.name')->label('Tenant'),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('net_amount')->label('Net')->numeric(),
                TextColumn::make('currency_code')->label('Devise'),
                TextColumn::make('period_end')->label('Période fin')->date(),
                TextColumn::make('paid_at')->label('Payé le')->dateTime(),
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
            'index' => ManageSettlements::route('/'),
        ];
    }
}
