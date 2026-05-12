<?php

namespace App\Filament\Platform\Resources\FrontPages\Schemas;

use App\Enums\FrontPageSectionType;
use App\Enums\FrontPageStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FrontPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Page')->schema([
                    TextInput::make('title')
                        ->label('Titre')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                            if (blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }

                            if (blank($get('key'))) {
                                $set('key', Str::snake((string) $state));
                            }
                        })
                        ->columnSpan(3),
                    TextInput::make('key')
                        ->label('Clé technique')
                        ->required()
                        ->maxLength(120)
                        ->unique(ignoreRecord: true)
                        ->helperText('Exemple : about, faq, refund_policy.')
                        ->columnSpan(2),
                    Select::make('status')
                        ->label('Statut')
                        ->options(FrontPageStatus::options())
                        ->required()
                        ->default(FrontPageStatus::Draft->value)
                        ->columnSpan(1),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(160)
                        ->unique(ignoreRecord: true)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set): void {
                            $slug = trim((string) $state, '/');
                            $set('route_path', $slug === '' ? '/' : '/' . $slug);
                        })
                        ->columnSpan(2),
                    TextInput::make('route_path')
                        ->label('Chemin public')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Exemple : /a-propos ou /mentions-legales')
                        ->columnSpan(2),
                    Select::make('template')
                        ->label('Template')
                        ->options([
                            'marketing_page' => 'Marketing',
                            'contact_page' => 'Contact',
                            'faq_page' => 'FAQ',
                            'legal_page' => 'Légal',
                            'onboarding_page' => 'Onboarding',
                            'content_page' => 'Contenu générique',
                        ])
                        ->required()
                        ->default('content_page')
                        ->columnSpan(2),
                    Toggle::make('is_active')->label('Active')->default(true)->inline(false)->columnSpan(1),
                    Toggle::make('show_in_sitemap')->label('Dans sitemap')->default(true)->inline(false)->columnSpan(1),
                    DateTimePicker::make('published_at')->label('Publiée le')->columnSpan(2),
                    TextInput::make('seo_title')->label('SEO title')->maxLength(255)->columnSpan(3),
                    Textarea::make('seo_description')->label('SEO description')->rows(3)->columnSpan(3),
                    TextInput::make('seo_image_url')->label('SEO image')->url()->maxLength(255)->columnSpanFull(),
                    KeyValue::make('meta')->label('Meta page')->columnSpanFull(),
                ])->columns(6),
                Section::make('Sections')->schema([
                    Repeater::make('sections')
                        ->relationship()
                        ->orderColumn('sort_order')
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?: ($state['key'] ?? null))
                        ->schema([
                            TextInput::make('key')
                                ->label('Clé section')
                                ->required()
                                ->maxLength(120)
                                ->columnSpan(1),
                            Select::make('type')
                                ->label('Type')
                                ->options(FrontPageSectionType::options())
                                ->required()
                                ->columnSpan(1),
                            Toggle::make('is_active')->label('Active')->default(true)->inline(false)->columnSpan(1),
                            TextInput::make('eyebrow')->label('Eyebrow')->maxLength(120)->columnSpan(1),
                            TextInput::make('title')->label('Titre')->maxLength(255)->columnSpan(2),
                            Textarea::make('body')->label('Contenu')->rows(4)->columnSpanFull(),
                            TextInput::make('image_url')->label('Image')->url()->maxLength(255)->columnSpanFull(),
                            TextInput::make('primary_cta_label')->label('CTA principal label')->maxLength(120)->columnSpan(1),
                            TextInput::make('primary_cta_url')->label('CTA principal URL')->maxLength(255)->columnSpan(1),
                            TextInput::make('secondary_cta_label')->label('CTA secondaire label')->maxLength(120)->columnSpan(1),
                            TextInput::make('secondary_cta_url')->label('CTA secondaire URL')->maxLength(255)->columnSpan(1),
                            Repeater::make('items')
                                ->label('Items')
                                ->schema([
                                    TextInput::make('title')->label('Titre')->maxLength(255)->columnSpan(1),
                                    TextInput::make('label')->label('Label')->maxLength(255)->columnSpan(1),
                                    TextInput::make('value')->label('Valeur')->maxLength(255)->columnSpan(1),
                                    TextInput::make('href')->label('Lien')->maxLength(255)->columnSpan(1),
                                    TextInput::make('icon')->label('Icône')->maxLength(100)->columnSpan(1),
                                    TextInput::make('image_url')->label('Image')->maxLength(255)->columnSpan(1),
                                    Textarea::make('body')->label('Texte')->rows(3)->columnSpanFull(),
                                    KeyValue::make('meta')->label('Meta item')->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                            KeyValue::make('settings')->label('Settings section')->columnSpanFull(),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ])->columnSpanFull(),
            ]);
    }
}
