<?php

namespace App\Filament\Platform\Resources\CompliancePolicies;

use App\Filament\Platform\Resources\CompliancePolicies\Pages\ManageCompliancePolicies;
use App\Models\CompliancePolicy;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class CompliancePolicyResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = CompliancePolicy::class;

    protected static ?string $permissionPrefix = 'platform.compliance_policies';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration globale';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $modelLabel = 'Politique de conformité';

    protected static ?string $pluralModelLabel = 'Politiques de conformité';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Politique')->schema([
                    TextInput::make('public_id')->default(fn () => (string) Str::uuid())->disabled()->dehydrated(),
                    TextInput::make('code')->label('Code')->required()->maxLength(120),
                    TextInput::make('name')->label('Nom')->required()->maxLength(255),
                    TextInput::make('policy_type')->label('Type')->required()->maxLength(120),
                    Select::make('status')->label('Statut')->options([
                        'draft' => 'Brouillon',
                        'active' => 'Active',
                        'retired' => 'Retirée',
                    ])->default('draft')->required(),
                    DateTimePicker::make('effective_from')->label('Début'),
                    DateTimePicker::make('effective_to')->label('Fin'),
                    Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                    KeyValue::make('requirements')->label('Exigences')->columnSpanFull(),
                    KeyValue::make('meta')->label('Meta')->columnSpanFull(),
                ])->columns(2),
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
                TextColumn::make('policy_type')->label('Type')->badge(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('effective_from')->label('Début')->dateTime(),
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
            'index' => ManageCompliancePolicies::route('/'),
        ];
    }
}
