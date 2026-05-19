# Politique financière Ticket

## Résumé exécutif

Ticket applique une politique financière globale pilotée par le super-admin.

- Commission organisateur : configurable en pourcentage.
- Frais carte par ticket : configurables en montant fixe.
- Valeur vide ou non renseignée : équivaut à `0`.
- Frais gateway réels : absorbés par la plateforme.
- Frais carte : non remboursables, sauf erreur technique confirmée ou débit en doublon.

## Règles de calcul

### 1. Sous-total

Le sous-total correspond au prix unitaire de l’offre multiplié par la quantité commandée.

### 2. Commission organisateur

La commission organisateur est calculée sur le sous-total.

Formule :

`commission = sous_total × taux_commission / 100`

- Elle réduit le net organisateur.
- Elle n’est pas ajoutée au total payé par le client.
- Si le taux est vide ou nul, aucune commission n’est appliquée.

### 3. Frais carte par ticket

Les frais carte sont calculés uniquement si le moyen de paiement sélectionné est la carte bancaire.

Formule :

`frais_carte_total = frais_carte_par_ticket × quantité`

- Ils sont ajoutés au total client.
- Ils ne s’appliquent pas au mobile money.
- Si le montant est vide ou nul, aucun frais carte n’est appliqué.

### 4. Total payé par le client

Formule :

`total_client = sous_total + frais_carte_total`

### 5. Net organisateur

Formule :

`net_organisateur = sous_total - commission`

### 6. Frais gateway

Les frais réellement facturés par la passerelle de paiement restent des coûts internes à Ticket.

- Ils ne sont pas configurés via des règles gateway métier.
- Ils ne sont pas refacturés à l’organisateur via la politique actuelle.
- Ils sont conservés en audit dans les transactions financières.

## Exemples

### Exemple A — politique activée

Configuration :

- Commission organisateur : `10 %`
- Frais carte par ticket : `500 FCFA`
- Quantité : `2`
- Prix unitaire : `10 000 FCFA`
- Paiement : `Carte bancaire`

Calcul :

- Sous-total : `20 000 FCFA`
- Commission : `2 000 FCFA`
- Frais carte : `1 000 FCFA`
- Total client : `21 000 FCFA`
- Net organisateur : `18 000 FCFA`

### Exemple B — mobile money

Configuration :

- Commission organisateur : `10 %`
- Frais carte par ticket : `500 FCFA`
- Quantité : `2`
- Prix unitaire : `10 000 FCFA`
- Paiement : `Mobile Money`

Calcul :

- Sous-total : `20 000 FCFA`
- Commission : `2 000 FCFA`
- Frais carte : `0 FCFA`
- Total client : `20 000 FCFA`
- Net organisateur : `18 000 FCFA`

### Exemple C — configuration vide

Configuration :

- Commission organisateur : vide
- Frais carte par ticket : vide

Calcul :

- Commission : `0`
- Frais carte : `0`
- Aucun supplément appliqué.

## Politique de remboursement

### Billet / sous-total

Le sous-total peut être remboursé selon le motif accepté :

- annulation organisateur,
- report incompatible,
- demande validée,
- erreur technique confirmée,
- débit en doublon,
- autres cas validés par la plateforme.

### Frais carte

Les frais carte par ticket ne sont **pas** remboursés dans un remboursement standard.

Ils sont remboursés uniquement dans les cas suivants :

- erreur technique confirmée,
- débit en doublon.

### Commission organisateur

En cas de remboursement accepté, la commission organisateur est réversible dans le calcul financier interne afin d’éviter de facturer une vente annulée comme une vente définitivement acquise.

### Frais gateway

Les frais gateway réels restent des coûts internes potentiellement absorbés par Ticket selon le résultat du remboursement passerelle et la politique interne de rapprochement.

## Audit et traçabilité

Chaque transaction conserve un snapshot de pricing incluant :

- sous-total,
- commission appliquée,
- frais carte appliqués,
- net organisateur,
- frais gateway absorbés,
- règles de remboursement utiles à la reconstitution financière.

Cette structure permet de préserver l’historique même si la configuration super-admin évolue ensuite.

## Paramétrage super-admin

Le réglage central est stocké dans `platform_settings` sous la clé :

- `group = finance`
- `key = finance_policy`

Champs actuellement pilotés :

- `commission_rate`
- `card_fee_per_ticket`

## Décision produit actuelle

La politique cible actuellement supportée par l’interface est :

- une commission organisateur globale,
- un frais carte global par ticket,
- aucun frais si les champs sont vides,
- aucune configuration gateway métier séparée à maintenir dans le parcours principal.
