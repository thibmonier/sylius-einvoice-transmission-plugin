# Plugin Sylius de transmission des factures électroniques

[English version](README.md)

Transmission des factures Factur-X générées par [`tmonier/sylius-facturx-plugin`](https://github.com/thibmonier/sylius-facturx-plugin) pour Sylius 2 : un **port** remplaçable vers une plateforme, un **journal** des envois avec leurs statuts et un **écouteur idempotent** de l'événement `tmonier_facturx.invoice_generated`.

C'est la première tranche du plugin (version 0.x). Elle fournit le port, le journal, l'écouteur et un adaptateur nul. **Elle ne se connecte à aucune plateforme réelle** : les adaptateurs sont à écrire par vous, ou seront ceux d'une version ultérieure.

## Ce que fait le plugin

- Quand le plugin Factur-X génère une facture, l'écouteur remet le XML et le PDF à l'adaptateur configuré.
- Chaque facture a **une seule ligne** dans le journal (`tmonier_einvoice_transmission`) : identifiant et numéro de la facture, statut, message, dates de création et de mise à jour.
- L'écouteur est **idempotent** : une facture qui a déjà une ligne n'est jamais envoyée une seconde fois, même si l'événement est rejoué ou traité par deux processus en même temps (l'identifiant de la facture est la clé primaire).
- Un adaptateur en échec **n'empêche jamais la génération de la facture** : l'exception est attrapée, la ligne prend le statut `error` et le message de l'exception.
- Un adaptateur qui renvoie un statut hors de l'ensemble connu est **refusé** : la ligne prend le statut `error` avec un message qui nomme le statut inconnu, et la valeur inconnue n'est jamais enregistrée.
- Par défaut, l'adaptateur nul n'envoie rien et journalise la facture au statut `not_transmitted`.

## Prérequis

- PHP 8.2 ou supérieur
- Sylius 2.2 ou 2.3 (Symfony 6.4, 7.4 ou 8.0)
- `tmonier/sylius-facturx-plugin` ^1.0, installé et configuré

## Installation

```bash
composer require tmonier/sylius-einvoice-transmission-plugin
```

Déclarer le bundle dans `config/bundles.php` :

```php
return [
    // ...
    Tmonier\SyliusEinvoiceTransmissionPlugin\TmonierSyliusEinvoiceTransmissionPlugin::class => ['all' => true],
];
```

Lancer la migration (le plugin enregistre lui-même ses migrations) :

```bash
bin/console doctrine:migrations:migrate
```

## Configuration

```yaml
# config/packages/tmonier_sylius_einvoice_transmission.yaml
tmonier_sylius_einvoice_transmission:
    # Identifiant de votre service qui implémente InvoiceTransmitterInterface (défaut : l'adaptateur nul)
    transmitter: App\Einvoice\MyPlatformTransmitter
    # Identifiant de votre service qui implémente InvoiceDocumentProviderInterface (facultatif, active la relance)
    document_provider: App\Einvoice\MyInvoiceDocuments
    # Statuts que votre adaptateur peut renvoyer en plus des quatre ci-dessous (snake_case en minuscules)
    additional_statuses: []
```

## Écrire un adaptateur

```php
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatus;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceDocument;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceTransmitterInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\TransmissionResult;

final class MyPlatformTransmitter implements InvoiceTransmitterInterface
{
    public function transmit(InvoiceDocument $document): TransmissionResult
    {
        // $document->invoiceId, ->invoiceNumber, ->xml, ->pdfContent, ->pdfFilename
        // ...envoi à votre plateforme ; lever une exception en cas d'échec...

        return new TransmissionResult(TransmissionStatus::DEPOSITED, 'Référence 123');
    }
}
```

`RecordingInvoiceTransmitter` (espace de noms `Testing`) est un double de test qui garde ce qu'il reçoit et renvoie un résultat fixe.

## Relancer un envoi en erreur

Un envoi au statut `error` peut être renvoyé. Fournissez un service qui rend la facture générée à partir de son identifiant (`InvoiceDocumentProviderInterface::provide()`), déclarez-le dans `document_provider`, puis appelez `RelaunchTransmissionHandler::relaunch($invoiceId)` :

- la ligne du journal est rouverte (même ligne, statut `not_transmitted`) par une seule mise à jour conditionnelle : deux relances simultanées n'envoient qu'une fois ;
- l'adaptateur est appelé une fois et sa réponse est journalisée comme pour le premier envoi ;
- tout autre statut (`deposited`, `rejected`, `not_transmitted`) ou une facture inconnue est refusé par `TransmissionNotRelaunchable`, sans aucun envoi ;
- si le document ne peut pas être fourni, la ligne repasse en `error` avec le message de l'échec et peut être relancée de nouveau.

`TransmissionJournalInterface` a gagné `reopen()` en 0.2.0 : un journal personnalisé doit l'implémenter.

## Statuts

| Statut | Signification |
|--------|---------------|
| `deposited` | La plateforme a accepté le dépôt de la facture |
| `rejected` | La plateforme a refusé la facture |
| `error` | L'envoi a échoué (exception de l'adaptateur, ou statut inconnu renvoyé) |
| `not_transmitted` | La facture est journalisée mais n'a pas été envoyée (adaptateur nul, ou envoi sans réponse encore) |

**Ces statuts sont un ensemble minimal à vérifier.** Les statuts officiels du cycle de vie des plateformes françaises de facturation électronique n'ont pas été confirmés par une source officielle pour ce plugin, et la norme définit ses propres formats et statuts. L'ensemble est donc volontairement réduit et s'étend avec `additional_statuses` ; aucun statut n'est inventé au nom d'une plateforme. Consultez les documents officiels en vigueur avant de vous appuyer sur un statut à des fins juridiques ou fiscales.

## Limites de cette tranche

- Aucune plateforme réelle : seuls le port, le journal et l'adaptateur nul sont fournis.
- Pas de reprise automatique : une ligne en `error` ou `not_transmitted` n'est pas renvoyée par un événement rejoué ; un envoi en erreur se relance explicitement (voir plus haut). Un processus qui s'arrête entre la prise en charge de la facture et la réponse de l'adaptateur laisse la ligne en `not_transmitted`.
- Pas d'écran d'administration, pas de consultation d'annuaire, pas d'e-reporting.
- Le plugin est une brique technique. Il ne constitue ni un conseil juridique ni un conseil fiscal et n'a aucune valeur réglementaire par lui-même.

## Développement

L'application de test est configurée par `phpunit.xml.dist` et par l'environnement, pas par des fichiers `tests/TestApplication/.env*`. `phpunit` fonctionne sans variable dès que MySQL tourne sur le port 3351 ; pour les commandes console, exporter :

```bash
composer install
docker compose up -d --wait mysql   # port 3351 comes from compose.override.yml (see compose.override.dist.yml), or use your own DATABASE_URL
export APP_ENV=test \
  DATABASE_URL='mysql://root@127.0.0.1:3351/tmonier_sylius_einvoice_transmission_test' \
  SYLIUS_TEST_APP_BUNDLES_PATH=tests/TestApplication/config/bundles.php \
  SYLIUS_TEST_APP_CONFIGS_TO_IMPORT='@TmonierSyliusEinvoiceTransmissionPlugin/tests/TestApplication/config/config.yaml'
php -d memory_limit=2G vendor/bin/console doctrine:database:create --if-not-exists
php -d memory_limit=2G vendor/bin/console doctrine:migrations:migrate -n
vendor/bin/phpunit
vendor/bin/ecs check
vendor/bin/phpstan analyse
vendor/bin/rector process --dry-run
```

Les variables d'environnement réelles l'emportent sur les valeurs par défaut de `phpunit.xml.dist` (la CI fixe son propre `DATABASE_URL`).

## Licence

MIT, voir [LICENSE](LICENSE).
