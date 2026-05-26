<?php

namespace App\Filament\Platform\Resources\FrontMenus\Schemas;

use App\Enums\FrontMenuLocation;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FrontMenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Menu')->schema([
                TextInput::make('title')->label('Titre')->required()->maxLength(255)->columnSpan(2),
                TextInput::make('key')->label('Clé')->required()->maxLength(120)->unique(ignoreRecord: true)->columnSpan(2),
                Select::make('location')->label('Emplacement')->options(FrontMenuLocation::options())->required()->columnSpan(1),
                Toggle::make('is_active')->label('Actif')->default(true)->inline(false)->columnSpan(1),
                KeyValue::make('settings')->label('Settings')->columnSpanFull(),
            ])->columns(6),
            Section::make('Éléments fixes du header utilitaire')->schema([
                TextInput::make('settings.support_email')
                    ->label('Email support')
                    ->email()
                    ->maxLength(255)
                    ->placeholder('support@ticket.africa')
                    ->columnSpan(3),
                TextInput::make('settings.support_phone')
                    ->label('Téléphone support')
                    ->maxLength(50)
                    ->placeholder('+225 27 22 40 11 00')
                    ->columnSpan(3),
                TextInput::make('settings.availability_label')
                    ->label('Badge disponibilité')
                    ->maxLength(80)
                    ->placeholder('Disponible 24h/24')
                    ->columnSpan(3),
                TextInput::make('settings.secure_payment_label')
                    ->label('Libellé paiement sécurisé')
                    ->maxLength(80)
                    ->placeholder('Paiement sécurisé')
                    ->columnSpan(3),
                Toggle::make('settings.show_contacts')
                    ->label('Afficher email et téléphone')
                    ->default(true)
                    ->inline(false)
                    ->columnSpan(2),
                Toggle::make('settings.show_availability')
                    ->label('Afficher le badge disponibilité')
                    ->default(true)
                    ->inline(false)
                    ->columnSpan(2),
                Toggle::make('settings.show_secure_payment')
                    ->label('Afficher paiement sécurisé')
                    ->default(true)
                    ->inline(false)
                    ->columnSpan(2),
            ])
                ->columns(6)
                ->visible(fn ($get): bool => $get('location') === FrontMenuLocation::HeaderUtility->value),
            Section::make('Liens')->schema([
                Repeater::make('items')
                    ->relationship()
                    ->orderColumn('sort_order')
                    ->collapsible()
                    ->collapsed()
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                    ->schema([
                        TextInput::make('label')->label('Label')->required()->maxLength(255)->columnSpan(1),
                        TextInput::make('href')->label('URL')->required()->maxLength(255)->columnSpan(1),
                        Select::make('meta.icon')
                            ->label('Icône')
                            ->options([
                                '' => 'Aucune',
                                'mail' => 'Email',
                                'phone' => 'Téléphone',
                                'status' => 'Statut / disponibilité',
                                'lock' => 'Paiement sécurisé',
                                'shield' => 'Sécurité',
                                'globe' => 'Langue / monde',
                                'user' => 'Utilisateur',
                                'ticket' => 'Ticket',
                                'organizer' => 'Organisateur',
                                'link' => 'Lien',
                            ])
                            ->native(false)
                            ->searchable()
                            ->columnSpan(1),
                        Select::make('target')
                            ->label('Target')
                            ->options([
                                '_self' => 'Même onglet',
                                '_blank' => 'Nouvel onglet',
                            ])
                            ->default('_self')
                            ->required()
                            ->columnSpan(1),
                        Toggle::make('is_active')->label('Actif')->default(true)->inline(false)->columnSpan(1),
                        KeyValue::make('meta')->label('Meta lien')->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ])->columnSpanFull(),
        ]);
    }
}
