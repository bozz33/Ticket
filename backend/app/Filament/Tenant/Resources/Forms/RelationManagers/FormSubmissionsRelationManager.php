<?php

namespace App\Filament\Tenant\Resources\Forms\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormSubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    protected static ?string $title = 'Soumissions';

    protected static ?string $modelLabel = 'soumission';

    protected static ?string $pluralModelLabel = 'soumissions';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Soumission')->schema([
                Select::make('status')
                    ->label('Statut')
                    ->options([
                        'submitted' => 'Soumise',
                        'reviewed' => 'Examinée',
                    ])
                    ->required(),
                DateTimePicker::make('submitted_at')
                    ->label('Soumis le')
                    ->disabled()
                    ->dehydrated(false),
                DateTimePicker::make('reviewed_at')
                    ->label('Examiné le'),
            ])->columns(2),
            Section::make('Données soumises')->schema([
                Textarea::make('data')
                    ->label('Réponses')
                    ->formatStateUsing(fn ($state): string => is_array($state)
                        ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : (string) ($state ?? ''))
                    ->rows(16)
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Textarea::make('files')
                    ->label('Fichiers joints')
                    ->formatStateUsing(fn ($state): string => is_array($state)
                        ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : (string) ($state ?? ''))
                    ->rows(8)
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('public_id')
                    ->label('ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'reviewed' => 'success',
                        'submitted' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('submitted_at')
                    ->label('Soumis le')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('reviewed_at')
                    ->label('Examiné le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->recordActions([
                ViewAction::make()->label('Voir'),
                Action::make('mark_reviewed')
                    ->label('Marquer examinée')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        $record->update([
                            'status' => 'reviewed',
                            'reviewed_at' => now(),
                        ]);
                    })
                    ->visible(fn ($record): bool => $record->status !== 'reviewed'),
                DeleteAction::make(),
            ]);
    }
}
