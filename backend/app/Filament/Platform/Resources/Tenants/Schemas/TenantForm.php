<?php

namespace App\Filament\Platform\Resources\Tenants\Schemas;

use App\Models\Plan;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Organisation')->schema([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                        if (blank($get('slug'))) {
                            $set('slug', Str::slug((string) $state));
                        }
                    })
                    ->columnSpan(4),
                TextInput::make('slug')
                    ->label('Slug')
                    ->maxLength(120)
                    ->helperText('Utilisé pour générer le lien d\'accès tenant et le lien public organisateur.')
                    ->columnSpan(2),
                TextInput::make('display_name')->label('Nom affiché')->maxLength(255)->columnSpan(3),
                TextInput::make('email')->label('Email public')->email()->maxLength(255)->columnSpan(3),
                TextInput::make('phone')->label('Téléphone')->maxLength(50)->columnSpan(2),
                TextInput::make('website_url')->label('Site web')->url()->maxLength(255)->columnSpan(4),
                Placeholder::make('public_organizer_link')
                    ->label('Lien public organisateur')
                    ->content(function (callable $get): string {
                        if (blank($get('name')) && blank($get('slug'))) {
                            return 'Le lien public organisateur sera généré automatiquement après création du tenant.';
                        }

                        return 'Le lien public organisateur utilisera le public_id du tenant après création, par exemple : /fr/organisateurs/{public_id}?tab=events';
                    })
                    ->columnSpan(3),
                Placeholder::make('tenant_panel_link')
                    ->label('Lien panel tenant')
                    ->content(function (callable $get): string {
                        $slug = Str::slug((string) ($get('slug') ?: $get('name')));

                        if ($slug === '') {
                            return 'Le lien de connexion tenant sera généré automatiquement à partir du slug.';
                        }

                        return url(sprintf('/tenants/%s/admin/login', $slug));
                    })
                    ->columnSpan(3),
                TextInput::make('country_code')->label('Pays')->maxLength(2)->columnSpan(1),
                TextInput::make('currency_code')->label('Devise')->maxLength(3)->columnSpan(1),
                TextInput::make('locale')->label('Locale')->maxLength(10)->columnSpan(2),
                TextInput::make('timezone')->label('Timezone')->maxLength(100)->columnSpan(2),
                Textarea::make('description')->label('Description')->rows(4)->columnSpanFull(),
            ])->columns(6),
            Section::make('Admin du panel tenant')->schema([
                TextInput::make('admin.name')->label('Nom complet')->maxLength(255)->columnSpan(3),
                TextInput::make('admin.username')->label('Username')->maxLength(120)->columnSpan(3),
                TextInput::make('admin.email')->label('Email')->email()->required()->maxLength(255)->columnSpan(3),
                TextInput::make('admin.phone')->label('Téléphone')->maxLength(50)->columnSpan(3),
                TextInput::make('admin.password')->label('Mot de passe')->password()->revealable()->required(fn (?string $operation): bool => $operation === 'create')->dehydrated(fn (?string $state): bool => filled($state))->minLength(8)->maxLength(255)->columnSpan(3),
                TextInput::make('admin.locale')->label('Locale')->maxLength(10)->columnSpan(1),
                TextInput::make('admin.timezone')->label('Timezone')->maxLength(100)->columnSpan(2),
            ])->columns(6),
            Section::make('Activation & souscription')->schema([
                Toggle::make('activate')->label('Activer immédiatement')->default(true)->inline(false)->columnSpan(2),
                Select::make('plan_id')->label('Plan initial')->options(fn (): array => Plan::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload()->columnSpan(2),
                KeyValue::make('meta')->label('Métadonnées')->columnSpanFull(),
                Placeholder::make('provisioning_note')
                    ->label('Provisioning automatique')
                    ->content('La base de données et le dossier de stockage du tenant sont créés automatiquement à la création, puis nettoyés à la suppression. Aucun sous-domaine n\'est créé automatiquement.')
                    ->columnSpan(4),
            ])->columns(6),
        ]);
    }
}
