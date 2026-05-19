<?php

namespace App\Filament\Platform\Resources\Settlements;

use App\Filament\Platform\Resources\Settlements\Pages\ManageSettlements;
use App\Models\PayoutBatch;
use App\Models\PayoutPolicy;
use App\Models\PlatformUser;
use App\Models\Settlement;
use App\Models\Tenant;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Ticket\Payments\Contracts\SettlementWorkflow;
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

    public static function getNavigationBadge(): ?string
    {
        $count = app(SettlementWorkflow::class)->pendingCount();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Reversement')->schema([
                    Select::make('tenant_id')->label('Tenant')->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())->required()->searchable()->preload(),
                    Select::make('payout_batch_id')->label('Batch')->options(fn (): array => PayoutBatch::query()->orderByDesc('id')->pluck('reference', 'id')->all())->searchable()->preload(),
                    Select::make('payout_policy_id')->label('Politique de reversement')->options(fn (): array => PayoutPolicy::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                    TextInput::make('reference')->label('Référence')->required()->maxLength(255),
                    Select::make('status')->label('Statut')->options([
                        'draft' => 'Brouillon',
                        'pending' => 'En attente organisateur',
                        'approved' => 'Approuvé',
                        'scheduled' => 'Planifié',
                        'paid' => 'Payé',
                        'rejected' => 'Rejeté',
                        'failed' => 'Échec',
                    ])->default('pending')->required(),
                    DatePicker::make('period_start')->label('Période début'),
                    DatePicker::make('period_end')->label('Période fin'),
                    TextInput::make('gross_amount')->label('Brut')->numeric()->default(0)->required(),
                    TextInput::make('fee_amount')->label('Frais totaux')->numeric()->default(0)->required(),
                    TextInput::make('reserve_amount')->label('Montant en réserve')->numeric()->default(0)->required(),
                    TextInput::make('payout_fee_amount')->label('Frais de reversement')->numeric()->default(0)->required(),
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
            ->recordUrl(null)
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('tenant.name')->label('Tenant')->searchable(),
                TextColumn::make('payoutPolicy.name')->label('Politique')->toggleable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('gross_amount')->label('Brut')->numeric(),
                TextColumn::make('payout_fee_amount')->label('Frais reversement')->numeric(),
                TextColumn::make('reserve_amount')->label('Réserve')->numeric()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('net_amount')->label('Net')->numeric(),
                TextColumn::make('currency_code')->label('Devise'),
                TextColumn::make('meta.request_note')->label('Note')->wrap()->toggleable(),
                TextColumn::make('meta.review.rejection_reason')->label('Motif rejet')->wrap()->toggleable(),
                TextColumn::make('created_at')->label('Demandé le')->dateTime()->sortable(),
                TextColumn::make('period_end')->label('Période fin')->date(),
                TextColumn::make('meta.review.reviewed_by.name')->label('Revu par')->toggleable(),
                TextColumn::make('meta.review.reviewed_at')->label('Revu le')->dateTime()->toggleable(),
                TextColumn::make('paid_at')->label('Payé le')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Settlement $record): bool => in_array((string) $record->status, ['pending', 'under_review'], true))
                    ->action(function (Settlement $record): void {
                        /** @var PlatformUser|null $actor */
                        $actor = Filament::auth()->user();

                        if (! $actor instanceof PlatformUser) {
                            return;
                        }

                        $settlement = app(SettlementWorkflow::class)->approve($record, $actor);

                        Notification::make()
                            ->success()
                            ->title('Demande approuvée')
                            ->body(sprintf('La demande %s a été approuvée.', $settlement->reference))
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Settlement $record): bool => in_array((string) $record->status, ['pending', 'under_review'], true))
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Motif du rejet')
                            ->rows(4)
                            ->required()
                            ->maxLength(2000),
                    ])
                    ->action(function (Settlement $record, array $data): void {
                        /** @var PlatformUser|null $actor */
                        $actor = Filament::auth()->user();

                        if (! $actor instanceof PlatformUser) {
                            return;
                        }

                        $settlement = app(SettlementWorkflow::class)->reject(
                            $record,
                            $actor,
                            trim((string) ($data['rejection_reason'] ?? '')),
                        );

                        Notification::make()
                            ->success()
                            ->title('Demande rejetée')
                            ->body(sprintf('La demande %s a été rejetée.', $settlement->reference))
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSettlements::route('/'),
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
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
