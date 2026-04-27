<?php

namespace App\Filament\Platform\Resources\PlatformAuditLogs;

use App\Filament\Platform\Resources\PlatformAuditLogs\Pages\ManagePlatformAuditLogs;
use App\Models\PlatformAuditLog;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PlatformAuditLogResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = PlatformAuditLog::class;

    protected static ?string $permissionPrefix = 'platform.audit_logs';

    protected static string|UnitEnum|null $navigationGroup = 'Observabilité';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Audit Logs';

    protected static ?string $recordTitleAttribute = 'subject_label';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('subject_label')
                    ->disabled()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject_label')
            ->columns([
                TextColumn::make('logged_at')->label('Date')->dateTime()->sortable(),
                TextColumn::make('event')->label('Événement')->badge(),
                TextColumn::make('subject_label')->label('Cible')->searchable(),
                TextColumn::make('tenant.name')->label('Tenant'),
                TextColumn::make('platformUser.name')->label('Acteur'),
                TextColumn::make('subject_type')->label('Type')->wrap(),
            ])
            ->filters([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePlatformAuditLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
