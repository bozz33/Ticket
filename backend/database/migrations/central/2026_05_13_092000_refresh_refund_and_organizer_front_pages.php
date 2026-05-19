<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('central');

        $this->replaceSections($connection, 'refund_policy', [
            [
                'key' => 'refund_hero',
                'type' => 'hero',
                'eyebrow' => 'Documents légaux',
                'title' => 'Politique de remboursement',
                'body' => 'Cette politique décrit les cas de remboursement, les délais indicatifs, le traitement des frais carte et la procédure à suivre sur Ticket.',
                'image_url' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=1800&q=80',
                'settings' => ['variant' => 'inner', 'updated_at_label' => 'Dernière mise à jour : 13 mai 2026'],
            ],
            [
                'key' => 'refund_conditions',
                'type' => 'legal_article',
                'title' => 'Principes généraux',
                'body' => 'Un remboursement peut être accordé en cas d’annulation organisateur, de report incompatible, de politique organisateur favorable, d’erreur technique confirmée ou de débit en doublon. Les billets non utilisés après un événement tenu ne sont pas remboursables, sauf exception contractuelle ou légale.',
                'items' => [
                    ['label' => 'Annulation organisateur', 'value' => 'Oui — remboursement du billet'],
                    ['label' => 'Report incompatible', 'value' => 'Oui — selon la fenêtre annoncée'],
                    ['label' => 'Débit en doublon', 'value' => 'Oui — intégral'],
                    ['label' => 'Erreur technique confirmée', 'value' => 'Oui — intégral'],
                    ['label' => 'No-show / billet non utilisé', 'value' => 'Non, sauf exception'],
                ],
            ],
            [
                'key' => 'refund_card_fee',
                'type' => 'legal_article',
                'title' => 'Traitement des frais carte',
                'body' => 'Lorsque des frais carte par ticket sont affichés au checkout, ils sont distincts du prix du billet. Par défaut, ces frais carte ne sont pas remboursables. Ils ne sont restitués qu’en cas d’erreur technique confirmée ou de débit en doublon.',
                'items' => [
                    ['title' => 'Paiement carte', 'body' => 'Des frais carte par ticket peuvent être ajoutés avant validation du paiement.'],
                    ['title' => 'Remboursement standard', 'body' => 'Les frais carte restent acquis et ne sont pas remboursés.'],
                    ['title' => 'Erreur technique / doublon', 'body' => 'Les frais carte sont remboursés en même temps que le billet.'],
                ],
            ],
            [
                'key' => 'refund_customer_requests',
                'type' => 'legal_article',
                'title' => 'Demande de remboursement par l’acheteur',
                'body' => 'L’acheteur peut soumettre une demande depuis son compte lorsque l’option est proposée, ou contacter le support avec les éléments nécessaires. Chaque demande est étudiée selon le motif, la politique applicable et l’état de l’événement.',
                'items' => [
                    ['title' => 'Étape 1', 'body' => 'Connectez-vous à votre compte Ticket et ouvrez la commande concernée.'],
                    ['title' => 'Étape 2', 'body' => 'Sélectionnez le motif le plus précis possible et joignez vos informations utiles.'],
                    ['title' => 'Étape 3', 'body' => 'Conservez les preuves éventuelles en cas de doublon ou d’anomalie technique.'],
                    ['title' => 'Étape 4', 'body' => 'Vous êtes notifié du traitement et du statut du remboursement.'],
                ],
            ],
            [
                'key' => 'refund_processing',
                'type' => 'legal_article',
                'title' => 'Délais indicatifs et moyens de remboursement',
                'body' => 'Une fois le remboursement validé, les délais dépendent du moyen de paiement et de la passerelle utilisée. Les montants retournent sur le moyen de paiement initial dans la mesure du possible.',
                'items' => [
                    ['label' => 'Carte bancaire', 'value' => '5 à 10 jours ouvrés'],
                    ['label' => 'Orange Money', 'value' => '24 à 72 heures'],
                    ['label' => 'MTN Mobile Money', 'value' => '24 à 72 heures'],
                    ['label' => 'Wave', 'value' => '24 à 48 heures'],
                    ['label' => 'Moov Money', 'value' => '24 à 72 heures'],
                ],
            ],
            [
                'key' => 'refund_evidence',
                'type' => 'legal_article',
                'title' => 'Cas particuliers, litiges et pièces utiles',
                'body' => 'Pour les doublons ou erreurs techniques, Ticket peut demander des justificatifs complémentaires pour sécuriser le traitement. Les contestations sont examinées à partir des informations de commande, des journaux techniques et des preuves transmises.',
                'items' => [
                    ['title' => 'Pièces utiles', 'body' => 'Référence de commande, moyen de paiement, capture ou relevé de débit si disponible.'],
                    ['title' => 'Support', 'body' => 'Le support Ticket peut intervenir lorsque la demande ne peut pas être finalisée depuis le compte acheteur.'],
                    ['title' => 'Montant applicable', 'body' => 'Le montant remboursable est toujours celui confirmé et affiché au moment de la commande.'],
                ],
            ],
        ]);

        $this->replaceSections($connection, 'become_organizer', [
            [
                'key' => 'organizer_hero',
                'type' => 'hero',
                'eyebrow' => 'Onboarding organisateur',
                'title' => 'Développez vos ventes et votre visibilité avec Ticket',
                'body' => 'Lancez votre espace organisateur, gérez vos contenus, vos offres et vos paiements depuis un backoffice pensé pour la vente et l’exploitation quotidienne.',
                'image_url' => 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?auto=format&fit=crop&w=1800&q=80',
                'settings' => ['variant' => 'page'],
            ],
            [
                'key' => 'organizer_benefits',
                'type' => 'feature_grid',
                'eyebrow' => 'Pourquoi Ticket',
                'title' => 'Un parcours organisateur clair du lancement à l’encaissement',
                'body' => 'Vous gardez la main sur vos offres, vos pages publiques, vos ventes et vos équipes sans multiplier les outils.',
                'items' => [
                    ['title' => 'Billetterie et checkout unifiés', 'body' => 'Créez plusieurs offres, vendez en ligne et suivez vos encaissements depuis un seul espace.'],
                    ['title' => 'Pages publiques prêtes à convertir', 'body' => 'Chaque organisateur dispose d’une présence publique cohérente dans le portail Ticket.'],
                    ['title' => 'Pilotage des accès et des demandes', 'body' => 'Suivez vos commandes, reçus, accès et traitements opérationnels depuis votre panel.'],
                ],
            ],
            [
                'key' => 'organizer_reassurance',
                'type' => 'feature_grid',
                'eyebrow' => 'Accompagnement',
                'title' => 'Ce que vous obtenez dès l’ouverture de votre espace',
                'body' => 'Votre espace est prêt pour configurer votre profil, publier vos premiers contenus et préparer vos ventes.',
                'items' => [
                    ['title' => 'Backoffice dédié', 'body' => 'Un environnement orienté métier pour gérer vos publications, offres et opérations.'],
                    ['title' => 'Configuration financière lisible', 'body' => 'Une politique de commission claire et un checkout transparent pour vos acheteurs.'],
                    ['title' => 'Support de démarrage', 'body' => 'Vous pouvez lancer vos premiers contenus sans attendre un onboarding complexe.'],
                ],
            ],
            [
                'key' => 'organizer_final_cta',
                'type' => 'onboarding_form',
                'eyebrow' => 'Prêt à démarrer',
                'title' => 'Passez à l’étape suivante',
                'body' => 'Ouvrez maintenant votre espace organisateur via la page d’inscription dédiée et commencez à configurer votre activité.',
                'primary_cta_label' => 'Créer mon espace organisateur',
                'primary_cta_url' => '/devenir-organisateur/inscription',
                'settings' => ['mount_form' => false],
            ],
        ]);
    }

    public function down(): void {}

    private function replaceSections($connection, string $pageKey, array $sections): void
    {
        $page = $connection->table('front_pages')->where('key', $pageKey)->first();

        if (! $page) {
            return;
        }

        $connection->table('front_page_sections')
            ->where('front_page_id', $page->id)
            ->delete();

        foreach (array_values($sections) as $index => $section) {
            $connection->table('front_page_sections')->insert([
                'front_page_id' => $page->id,
                'key' => $section['key'],
                'type' => $section['type'],
                'title' => Arr::get($section, 'title'),
                'eyebrow' => Arr::get($section, 'eyebrow'),
                'body' => Arr::get($section, 'body'),
                'image_url' => Arr::get($section, 'image_url'),
                'primary_cta_label' => Arr::get($section, 'primary_cta_label'),
                'primary_cta_url' => Arr::get($section, 'primary_cta_url'),
                'secondary_cta_label' => Arr::get($section, 'secondary_cta_label'),
                'secondary_cta_url' => Arr::get($section, 'secondary_cta_url'),
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
                'items' => json_encode(Arr::get($section, 'items', []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'settings' => json_encode(Arr::get($section, 'settings', []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
