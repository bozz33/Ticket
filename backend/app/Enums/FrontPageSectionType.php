<?php

namespace App\Enums;

enum FrontPageSectionType: string
{
    case Hero = 'hero';
    case SplitOverview = 'split_overview';
    case FeatureGrid = 'feature_grid';
    case Metrics = 'metrics';
    case Faq = 'faq';
    case ContactChannels = 'contact_channels';
    case ContactForm = 'contact_form';
    case OrganizerHighlights = 'organizer_highlights';
    case LegalArticle = 'legal_article';
    case OnboardingForm = 'onboarding_form';

    public static function options(): array
    {
        return [
            self::Hero->value => 'Hero',
            self::SplitOverview->value => 'Vue partagée',
            self::FeatureGrid->value => 'Grille de cartes',
            self::Metrics->value => 'Chiffres clés',
            self::Faq->value => 'FAQ',
            self::ContactChannels->value => 'Canaux de contact',
            self::ContactForm->value => 'Bloc formulaire de contact',
            self::OrganizerHighlights->value => 'Organisateurs mis en avant',
            self::LegalArticle->value => 'Article légal',
            self::OnboardingForm->value => 'Formulaire onboarding organisateur',
        ];
    }
}
