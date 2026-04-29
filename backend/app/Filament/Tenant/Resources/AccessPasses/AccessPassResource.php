<?php

namespace App\Filament\Tenant\Resources\AccessPasses;

use App\Enums\AccessPassStatus;
use App\Enums\AccessPassType;
use App\Filament\Tenant\Resources\AccessPasses\Pages\ListAccessPasses;
use App\Models\AccessPass;
use App\Services\Tenancy\AccessPassCheckinService;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use UnitEnum;

class AccessPassResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = AccessPass::class;

    protected static ?string $permissionPrefix = 'tenant.sales';

    protected static string|UnitEnum|null $navigationGroup = 'Ventes';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'Pass d\'accès';

    protected static ?string $modelLabel = 'Pass d\'accès';

    protected static ?string $pluralModelLabel = 'Pass d\'accès';

    protected static ?string $recordTitleAttribute = 'access_code';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('access_code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->limit(16)
                    ->tooltip(fn (AccessPass $record): string => $record->access_code),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (AccessPassType $state): string => $state->label()),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (AccessPassStatus $state): string => $state->color()),
                TextColumn::make('order.reference')
                    ->label('Commande')
                    ->searchable(),
                TextColumn::make('holder_name')
                    ->label('Titulaire')
                    ->default('—')
                    ->searchable(),
                TextColumn::make('holder_email')
                    ->label('Email')
                    ->default('—')
                    ->searchable(),
                TextColumn::make('used_at')
                    ->label('Utilisé le')
                    ->dateTime('d/m/Y H:i')
                    ->default('—'),
                TextColumn::make('scans_count')
                    ->label('Scans')
                    ->counts('scans')
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(AccessPassStatus::options()),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(AccessPassType::options()),
            ])
            ->recordActions([
                TableAction::make('consume')
                    ->label('Valider entrée')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (AccessPass $record): bool => $record->status === AccessPassStatus::Active)
                    ->requiresConfirmation()
                    ->action(function (AccessPass $record): void {
                        $checkin = app(AccessPassCheckinService::class);
                        $result = $checkin->consume($record, request());

                        if ($result['access_granted']) {
                            Notification::make()->success()->title('Entrée validée')->body($result['message'])->send();
                        } else {
                            Notification::make()->warning()->title('Non accordé')->body($result['message'])->send();
                        }
                    }),

                TableAction::make('reset')
                    ->label('Réinitialiser')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (AccessPass $record): bool => $record->status === AccessPassStatus::Used)
                    ->requiresConfirmation()
                    ->action(function (AccessPass $record): void {
                        $checkin = app(AccessPassCheckinService::class);
                        $checkin->reset($record, request());
                        Notification::make()->success()->title('Pass réinitialisé')->send();
                    }),

                TableAction::make('revoke')
                    ->label('Révoquer')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (AccessPass $record): bool => ! in_array($record->status, [AccessPassStatus::Revoked], true))
                    ->form([
                        TextInput::make('reason')
                            ->label('Motif de révocation')
                            ->placeholder('Fraude, remboursement...')
                            ->maxLength(255),
                    ])
                    ->action(function (AccessPass $record, array $data): void {
                        $checkin = app(AccessPassCheckinService::class);
                        $checkin->revoke($record, request(), $data['reason'] ?? '');
                        Notification::make()->success()->title('Pass révoqué')->send();
                    }),

                TableAction::make('reactivate')
                    ->label('Réactiver')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('info')
                    ->visible(fn (AccessPass $record): bool => $record->status === AccessPassStatus::Revoked)
                    ->requiresConfirmation()
                    ->action(function (AccessPass $record): void {
                        $checkin = app(AccessPassCheckinService::class);
                        $checkin->reactivate($record, request());
                        Notification::make()->success()->title('Pass réactivé')->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccessPasses::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return static::allows('delete');
    }
}
