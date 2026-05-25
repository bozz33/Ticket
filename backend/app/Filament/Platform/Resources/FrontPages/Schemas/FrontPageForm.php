<?php

namespace App\Filament\Platform\Resources\FrontPages\Schemas;

use App\Enums\FrontPageSectionType;
use App\Enums\FrontPageStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                            $set('route_path', $slug === '' ? '/' : '/'.$slug);
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
                    TagsInput::make('meta.seo.keywords')
                        ->label('Mots-clés SEO')
                        ->columnSpan(3),
                    TextInput::make('meta.seo.canonical_url')
                        ->label('URL canonique')
                        ->url()
                        ->maxLength(255)
                        ->columnSpan(3),
                    Select::make('meta.seo.robots_index')
                        ->label('Indexation')
                        ->options(['index' => 'Index', 'noindex' => 'No index'])
                        ->native(false)
                        ->columnSpan(2),
                    Select::make('meta.seo.robots_follow')
                        ->label('Liens')
                        ->options(['follow' => 'Follow', 'nofollow' => 'No follow'])
                        ->native(false)
                        ->columnSpan(2),
                    Select::make('meta.seo.max_image_preview')
                        ->label('Aperçu image')
                        ->options(['large' => 'Large', 'standard' => 'Standard', 'none' => 'Aucun'])
                        ->native(false)
                        ->columnSpan(2),
                    TextInput::make('meta.seo.og_title')->label('OG title')->maxLength(255)->columnSpan(3),
                    Textarea::make('meta.seo.og_description')->label('OG description')->rows(3)->columnSpan(3),
                    FileUpload::make('meta.seo.og_image')
                        ->label('Image Open Graph')
                        ->image()
                        ->disk('public')
                        ->directory('front/seo')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->columnSpan(3),
                    TextInput::make('meta.seo.og_image_alt')->label('Texte alternatif OG')->maxLength(255)->columnSpan(3),
                    TextInput::make('meta.seo.twitter_title')->label('Twitter title')->maxLength(255)->columnSpan(3),
                    Textarea::make('meta.seo.twitter_description')->label('Twitter description')->rows(3)->columnSpan(3),
                    FileUpload::make('meta.seo.twitter_image')
                        ->label('Image Twitter')
                        ->image()
                        ->disk('public')
                        ->directory('front/seo')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->columnSpan(3),
                    Select::make('meta.seo.twitter_card')
                        ->label('Twitter card')
                        ->options(['summary' => 'Summary', 'summary_large_image' => 'Summary large image'])
                        ->native(false)
                        ->columnSpan(3),
                    Textarea::make('meta.seo.structured_data_json')
                        ->label('JSON-LD page')
                        ->rows(6)
                        ->columnSpanFull(),
                    Textarea::make('meta.summary')->label('Résumé interne')->rows(2)->columnSpan(3),
                    KeyValue::make('meta.extra')->label('Meta extra')->columnSpan(3),
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
