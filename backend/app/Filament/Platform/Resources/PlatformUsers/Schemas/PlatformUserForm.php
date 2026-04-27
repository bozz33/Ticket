<?php

namespace App\Filament\Platform\Resources\PlatformUsers\Schemas;

use App\Models\Tenant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlatformUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Compte')->schema([
                Select::make('tenant_id')->label('Tenant lié')->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload()->columnSpan(2),
                TextInput::make('name')->label('Nom')->required()->maxLength(255)->columnSpan(2),
                TextInput::make('email')->label('Email')->email()->required()->maxLength(255)->unique(ignoreRecord: true)->columnSpan(2),
                TextInput::make('password')->label('Mot de passe')->password()->revealable()->required(fn (?string $operation): bool => $operation === 'create')->dehydrated(fn (?string $state): bool => filled($state))->minLength(8)->maxLength(255)->columnSpan(2),
                Toggle::make('is_super_admin')->label('Super admin')->default(false)->inline(false)->columnSpan(2),
                Select::make('roles')
                    ->label('Rôles')
                    ->relationship('roles', 'name', fn ($query) => $query->where('guard_name', 'platform'))
                    ->multiple()
                    ->preload()
                    ->columnSpanFull(),
                Select::make('permissions')
                    ->label('Permissions directes')
                    ->relationship('permissions', 'name', fn ($query) => $query->where('guard_name', 'platform'))
                    ->multiple()
                    ->preload()
                    ->columnSpanFull(),
            ])->columns(6),
        ]);
    }
}
