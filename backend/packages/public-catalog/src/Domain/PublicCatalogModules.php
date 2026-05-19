<?php

namespace Ticket\PublicCatalog\Domain;

use App\Enums\CategoryScope;
use App\Models\CallForProject;
use App\Models\CrowdfundingCampaign;
use App\Models\Event;
use App\Models\Stand;
use App\Models\Training;

class PublicCatalogModules
{
    private const MODULE_MAP = [
        'evenements' => Event::class,
        'formations' => Training::class,
        'stands' => Stand::class,
        'appels-a-projets' => CallForProject::class,
        'crowdfunding' => CrowdfundingCampaign::class,
    ];

    private const MODULE_PRESENTATION = [
        'evenements' => [
            'title' => 'Evenements',
            'singular' => 'evenement',
            'cta' => 'Acheter',
            'description' => 'Concerts, conferences et experiences live publies par les organisations de la plateforme.',
            'href' => '/evenements',
            'heroImageUrl' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1800&q=80',
        ],
        'formations' => [
            'title' => 'Formations',
            'singular' => 'formation',
            'cta' => 'S\'inscrire',
            'description' => 'Sessions, masterclass et ateliers avec des parcours clairs jusqu\'a l\'inscription.',
            'href' => '/formations',
            'heroImageUrl' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1800&q=80',
        ],
        'stands' => [
            'title' => 'Stands',
            'singular' => 'stand',
            'cta' => 'Reserver',
            'description' => 'Catalogues d\'exposition et offres de reservation pour salons, foires et showcases.',
            'href' => '/stands',
            'heroImageUrl' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=1800&q=80',
        ],
        'appels-a-projets' => [
            'title' => 'Appels a projets',
            'singular' => 'appel',
            'cta' => 'Candidater',
            'description' => 'Programmes, concours et appels a candidatures avec conditions, calendrier et pieces requises.',
            'href' => '/appels-a-projets',
            'heroImageUrl' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?auto=format&fit=crop&w=1800&q=80',
        ],
        'crowdfunding' => [
            'title' => 'Crowdfunding',
            'singular' => 'campagne',
            'cta' => 'Contribuer',
            'description' => 'Campagnes financieres publiees par les organisateurs avec progression, paliers et impact attendu.',
            'href' => '/crowdfunding',
            'heroImageUrl' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=1800&q=80',
        ],
    ];

    /**
     * @return array<string, class-string>
     */
    public function modelMap(): array
    {
        return self::MODULE_MAP;
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys(self::MODULE_MAP);
    }

    public function has(string $module): bool
    {
        return isset(self::MODULE_MAP[$module]);
    }

    /**
     * @return class-string|null
     */
    public function modelClass(string $module): ?string
    {
        return self::MODULE_MAP[$module] ?? null;
    }

    public function scope(?string $module): ?string
    {
        return match ($module) {
            'evenements' => CategoryScope::Event->value,
            'formations' => CategoryScope::Training->value,
            'stands' => CategoryScope::Stand->value,
            'appels-a-projets' => CategoryScope::Call->value,
            'crowdfunding' => CategoryScope::Campaign->value,
            default => null,
        };
    }

    public function presentation(string $module): array
    {
        return self::MODULE_PRESENTATION[$module] ?? [
            'title' => ucfirst($module),
            'singular' => $module,
            'cta' => 'Explorer',
            'description' => '',
            'href' => sprintf('/%s', $module),
            'heroImageUrl' => '',
        ];
    }

    public function listingPresentation(?string $module = null, ?string $fallback = 'evenements'): array
    {
        $resolvedModule = is_string($module) && $this->has($module)
            ? $module
            : ((is_string($fallback) && $this->has($fallback)) ? $fallback : 'evenements');
        $presentation = $this->presentation($resolvedModule);

        return [
            'module' => $resolvedModule,
            'title' => $presentation['title'],
            'singular' => $presentation['singular'],
            'cta' => $presentation['cta'],
            'description' => $presentation['description'],
            'href' => $presentation['href'],
            'heroImageUrl' => $presentation['heroImageUrl'],
        ];
    }
}
