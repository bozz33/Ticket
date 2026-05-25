<?php

namespace App\Filament\Platform\Resources\SeoSettings;

use App\Filament\Platform\Resources\SeoSettings\Pages\CreateSeoSetting;
use App\Filament\Platform\Resources\SeoSettings\Pages\EditSeoSetting;
use App\Filament\Platform\Resources\SeoSettings\Pages\ListSeoSettings;
use App\Models\PlatformSetting;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SeoSettingResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = PlatformSetting::class;

    protected static ?string $permissionPrefix = 'platform.platform_settings';

    protected static string|UnitEnum|null $navigationGroup = 'CMS front public';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'SEO & métadonnées';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Réglage SEO';

    protected static ?string $pluralModelLabel = 'SEO & métadonnées';

    protected static ?string $recordTitleAttribute = 'key';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('SEO plateforme')->schema([
                    Hidden::make('group')->default('seo')->dehydrated(),
                    TextInput::make('key')
                        ->label('Clé technique')
                        ->required()
                        ->maxLength(150)
                        ->unique(ignoreRecord: true)
                        ->helperText('Exemples : seo.defaults, seo.home, seo.about. La clé sert au CMS et aux audits.')
                        ->columnSpan(3),
                    TextInput::make('value.route_path')
                        ->label('Chemin public')
                        ->placeholder('/a-propos')
                        ->helperText('Laisser vide pour des valeurs globales.')
                        ->maxLength(255)
                        ->columnSpan(2),
                    Toggle::make('is_public')
                        ->label('Public')
                        ->default(true)
                        ->inline(false)
                        ->columnSpan(1),
                    Hidden::make('type')->default('json')->dehydrated(),
                ])->columns(6),
                Section::make('Balises de recherche')->schema([
                    TextInput::make('value.title')
                        ->label('Title SEO')
                        ->maxLength(255)
                        ->helperText('Titre court, distinctif et cohérent avec le H1 de la page.')
                        ->columnSpan(3),
                    Textarea::make('value.description')
                        ->label('Meta description')
                        ->rows(3)
                        ->maxLength(320)
                        ->helperText('Résumé clair en une ou deux phrases. Google peut l’utiliser comme snippet.')
                        ->columnSpan(3),
                    TagsInput::make('value.keywords')
                        ->label('Mots-clés internes')
                        ->helperText('Sert au pilotage éditorial interne, sans bourrage de mots-clés.')
                        ->columnSpan(3),
                    TextInput::make('value.canonical_url')
                        ->label('URL canonique')
                        ->url()
                        ->maxLength(255)
                        ->helperText('URL absolue préférée quand une page peut être consultée par plusieurs chemins.')
                        ->columnSpan(3),
                ])->columns(6),
                Section::make('Robots & sitemap')->schema([
                    Select::make('value.robots_index')
                        ->label('Indexation')
                        ->options([
                            'index' => 'Index',
                            'noindex' => 'No index',
                        ])
                        ->default('index')
                        ->native(false)
                        ->columnSpan(2),
                    Select::make('value.robots_follow')
                        ->label('Liens')
                        ->options([
                            'follow' => 'Follow',
                            'nofollow' => 'No follow',
                        ])
                        ->default('follow')
                        ->native(false)
                        ->columnSpan(2),
                    Select::make('value.max_image_preview')
                        ->label('Aperçu image')
                        ->options([
                            'large' => 'Large',
                            'standard' => 'Standard',
                            'none' => 'Aucun',
                        ])
                        ->default('large')
                        ->native(false)
                        ->columnSpan(2),
                    Toggle::make('value.include_in_sitemap')
                        ->label('Inclure au sitemap')
                        ->default(true)
                        ->inline(false)
                        ->columnSpan(2),
                    DateTimePicker::make('value.lastmod')
                        ->label('Dernière modification significative')
                        ->helperText('À renseigner seulement quand le contenu principal, les liens ou les données structurées changent.')
                        ->columnSpan(2),
                    TextInput::make('value.sitemap_priority')
                        ->label('Priorité interne')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(1)
                        ->step(0.1)
                        ->helperText('Indicateur interne uniquement. Google ignore priority/changefreq XML.')
                        ->columnSpan(2),
                ])->columns(6),
                Section::make('Open Graph')->schema([
                    TextInput::make('value.og_title')
                        ->label('OG title')
                        ->maxLength(255)
                        ->columnSpan(3),
                    TextInput::make('value.og_type')
                        ->label('OG type')
                        ->default('website')
                        ->maxLength(80)
                        ->columnSpan(1),
                    Textarea::make('value.og_description')
                        ->label('OG description')
                        ->rows(3)
                        ->maxLength(320)
                        ->columnSpan(4),
                    FileUpload::make('value.og_image_url')
                        ->label('Image Open Graph')
                        ->helperText('Recommandé : 1200 x 630 px, lisible sur fond clair et sombre.')
                        ->image()
                        ->disk('public')
                        ->directory('platform/seo')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->columnSpan(3),
                    TextInput::make('value.og_image_alt')
                        ->label('Texte alternatif image')
                        ->maxLength(255)
                        ->columnSpan(3),
                ])->columns(6),
                Section::make('Twitter / X Card')->schema([
                    Select::make('value.twitter_card')
                        ->label('Type de carte')
                        ->options([
                            'summary' => 'Summary',
                            'summary_large_image' => 'Summary large image',
                        ])
                        ->default('summary_large_image')
                        ->native(false)
                        ->columnSpan(2),
                    TextInput::make('value.twitter_title')
                        ->label('Twitter title')
                        ->maxLength(255)
                        ->columnSpan(2),
                    Textarea::make('value.twitter_description')
                        ->label('Twitter description')
                        ->rows(3)
                        ->maxLength(320)
                        ->columnSpan(4),
                    FileUpload::make('value.twitter_image_url')
                        ->label('Image Twitter')
                        ->image()
                        ->disk('public')
                        ->directory('platform/seo')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->columnSpan(3),
                    TextInput::make('value.twitter_site')
                        ->label('Compte Twitter/X')
                        ->placeholder('@ticket')
                        ->maxLength(80)
                        ->columnSpan(3),
                ])->columns(6),
                Section::make('Réseaux sociaux')->schema([
                    Repeater::make('value.social_links')
                        ->label('Liens sociaux')
                        ->helperText('Ajoutez les liens publics utilisés dans le footer, les métadonnées Organization et les partages.')
                        ->schema([
                            Select::make('platform')
                                ->label('Réseau')
                                ->options([
                                    'facebook' => 'Facebook',
                                    'instagram' => 'Instagram',
                                    'linkedin' => 'LinkedIn',
                                    'x' => 'X / Twitter',
                                    'youtube' => 'YouTube',
                                    'tiktok' => 'TikTok',
                                    'website' => 'Site web',
                                    'other' => 'Autre',
                                ])
                                ->native(false)
                                ->columnSpan(1),
                            TextInput::make('label')
                                ->label('Libellé')
                                ->maxLength(80)
                                ->columnSpan(1),
                            TextInput::make('url')
                                ->label('URL')
                                ->url()
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2),
                        ])
                        ->columns(4)
                        ->columnSpanFull(),
                ])->columns(6),
                Section::make('Données structurées')->schema([
                    Select::make('value.schema_type')
                        ->label('Type JSON-LD')
                        ->options([
                            'Organization' => 'Organization',
                            'WebSite' => 'WebSite',
                            'Event' => 'Event',
                            'FAQPage' => 'FAQPage',
                            'BreadcrumbList' => 'BreadcrumbList',
                        ])
                        ->native(false)
                        ->columnSpan(2),
                    Textarea::make('value.structured_data_json')
                        ->label('JSON-LD avancé')
                        ->rows(8)
                        ->helperText('JSON-LD optionnel injecté dans le head si la page en a besoin.')
                        ->columnSpanFull(),
                ])->columns(6),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->label('Clé')->searchable(),
                TextColumn::make('value.route_path')->label('Chemin')->placeholder('Global')->searchable(),
                TextColumn::make('value.title')->label('Title')->limit(45)->placeholder('—')->searchable(),
                TextColumn::make('value.robots_index')->label('Index')->badge()->placeholder('index'),
                IconColumn::make('is_public')->label('Public')->boolean(),
                TextColumn::make('updated_at')->label('Mis à jour')->dateTime()->sortable(),
            ])
            ->defaultSort('key')
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
            'index' => ListSeoSettings::route('/'),
            'create' => CreateSeoSetting::route('/create'),
            'edit' => EditSeoSetting::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('group', 'seo');
    }
}
