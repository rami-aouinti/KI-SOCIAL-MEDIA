# KI Social Media

MVP Laravel pour préparer un planning éditorial hebdomadaire, construire des briefings, conserver les versions de contenu, obtenir une validation humaine et simuler une transmission vers une interface de publication.

> **Périmètre de démonstration :** données synthétiques uniquement. Aucune publication réelle sur Instagram, Facebook, TikTok ou LinkedIn.

## 1. Contrainte et principe

Le cadre initial prévoit **4 heures maximum (240 minutes), documentation et tests compris**. Le temps réellement consommé doit être déclaré honnêtement dans la livraison ; il ne faut pas prétendre respecter cette limite sans mesure réelle.

Le projet privilégie un workflow métier complet et testable plutôt qu'une plateforme social media complète.

## 2. Fonctionnalités du MVP

- Génération IA simulée et interchangeable : le fournisseur mock produit les cinq idées (3 priorités + 2 réserves) à partir des entrées structurées.
- Génération IA simulée d'un briefing complet et de variantes distinctes par canal pour une idée prioritaire.
- Validation de la sortie générée avant persistance ; provenance `provider=mock` et `simulated=true` conservée dans les données.
- Enregistrement d'au moins 3 références d'inspiration synthétiques.
- Répartition des tâches et calcul de capacité (Personne A : 90 min, Personne B : 150 min).
- Avertissement en cas de dépassement : le système ne déplace pas automatiquement les tâches et ne modifie jamais les capacités.
- Briefing complet pour une idée prioritaire avec variantes Instagram et TikTok.
- Création de versions immuables avec variantes par canal et empreinte SHA-256.
- Workflow de validation humaine : `draft` → `in_review` → `approved`.
- Validation liée au hash de la version exacte ; une nouvelle version doit être validée séparément.
- Transmission simulée et idempotente : une seule transmission par version, avec payload JSON persisté.
- Rapport hebdomadaire TXT généré depuis les données enregistrées.
- Événements métier `ContentApproved`, `ContentRejected` et `ContentHandedOff`, dispatchés uniquement après le commit de la transaction.
- Listener qui planifie un job Laravel pour actualiser le rapport hebdomadaire après chaque décision ou transmission.
- Job `RefreshWeeklyReportForContentVersion` avec trois tentatives, exécuté par la queue `database`.
- Page d'accueil de démonstration : planning, inspirations, capacités, briefing, validation et handoff.

Les transmissions ne contactent aucune API externe. Le statut `handed_off` signifie uniquement que le payload a été enregistré dans la simulation. Le mock IA utilise des réponses de test déterministes, validées avant stockage ; il ne contacte aucun modèle externe.

## 3. Architecture et choix

- **Laravel monolithique** : adapté à un MVP time-boxed, sans coût de coordination de microservices.
- **PHP 8.4 / Laravel / Eloquent** : conventions, validation, migrations et injection de dépendances.
- **MySQL 8.4** : base relationnelle du projet et environnement Docker/Sail.
- **Contrat IA interchangeable** : `AiContentGeneratorInterface`, implémentation `MockAiContentGenerator` et orchestration par `AiAssistedContentWorkflow`. Le binding est déclaré dans `AppServiceProvider`.
- **Services applicatifs ciblés** : `AiAssistedContentWorkflow`, `WeeklyPlanGenerator`, `CapacityPlanner`, `ContentVersionCreator`, `ContentApprovalService`, `SimulatedContentHandoffService`, `WeeklyReportGenerator`.
- **Seeder idempotent** : charge un jeu de données fictif complet sans créer de doublons lorsqu'il est exécuté plusieurs fois.
- **Commande Artisan** : exporte le rapport TXT depuis le plan enregistré.
- **Enums** pour les statuts, priorités, décisions et types d'affectation.
- **JSON** pour les snapshots, variantes de canal, entrées et payloads cohérents.
- **Tests unitaires et Feature** pour protéger les règles métier.

Les choix et leurs explications sont détaillés en français et en allemand dans [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## 4. Démarrage local

Prérequis : Docker Desktop / Docker Engine et Docker Compose.

```bash
git clone -b master https://github.com/rami-aouinti/KI-SOCIAL-MEDIA.git
cd KI-SOCIAL-MEDIA

cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

Si le fichier `.env` existe déjà, ne l'écrase pas avec `cp`. Vérifie que la configuration utilise le service MySQL de Compose, notamment `DB_CONNECTION=mysql` et `DB_HOST=mysql`.

## 5. Démonstration en 3 commandes

```bash
# Charger le planning, les inspirations, les cinq idées,
# le briefing, les variantes, l'approbation et le handoff simulé.
./vendor/bin/sail artisan db:seed --class="Database\Seeders\DemoContentSeeder"

# Ouvrir le tableau de démonstration
# http://localhost

# Générer le rapport hebdomadaire TXT
./vendor/bin/sail artisan weekly-report:generate
```

Le seeder passe par le générateur IA simulé pour créer le planning et le briefing avant la validation humaine et le handoff. Après une exécution complète, le relancer ne crée pas de doublons de planning, de version, d'approbation ou de transmission. Le seeder n'est toutefois pas un mécanisme de reprise transactionnelle si l'exécution est interrompue au milieu du workflow. Si une ancienne démo avait déjà créé la semaine du 23 novembre 2026 avant l'ajout du générateur IA, elle reste inchangée pour préserver l'idempotence ; pour voir le nouveau scénario sur une base locale jetable, réinitialiser d'abord la base avec `./vendor/bin/sail artisan migrate:fresh` (cette commande supprime toutes les données), puis relancer le seeder. Les données de démonstration utilisent la semaine du 23 novembre 2026 et des identifiants/médias fictifs.

Le rapport est écrit dans `storage/app/private/weekly-reports/`. La page d'accueil propose également un téléchargement direct du rapport.

## 6. Commandes utiles

```bash
# Lancer tous les tests
./vendor/bin/sail artisan test

# Tester une fonctionnalité précise
./vendor/bin/sail artisan test tests/Feature/AiAssistedContentWorkflowTest.php
./vendor/bin/sail artisan test tests/Feature/ContentApprovalServiceTest.php
./vendor/bin/sail artisan test tests/Feature/SimulatedContentHandoffServiceTest.php
./vendor/bin/sail artisan test tests/Feature/WeeklyReportGeneratorTest.php
./vendor/bin/sail artisan test tests/Feature/DemoContentSeederTest.php

# Démarrer le worker asynchrone (garder ce terminal ouvert)
./vendor/bin/sail artisan queue:work --tries=3

# Voir les migrations
./vendor/bin/sail artisan migrate:status

# Arrêter les conteneurs
./vendor/bin/sail down
```

## 7. Règles de sécurité métier démontrées

1. Une version ne peut être approuvée que lorsqu'elle est en revue et complète.
2. La validation enregistre le hash exact de la version approuvée.
3. Les champs constitutifs d'une version sont immuables ; une modification crée une nouvelle version.
4. La transmission exige une version approuvée, une validation correspondante au hash exact, un média, des canaux, des variantes et un responsable.
5. Une contrainte unique en base empêche plusieurs transmissions de la même version.
6. Une répétition retourne le résultat déjà enregistré au lieu de créer un doublon.
7. Le rapport est calculé à partir des enregistrements disponibles ; les métriques absentes restent inconnues.
8. Le contrôle de capacité signale les dépassements sans réaffectation automatique ni modification des limites.

## 8. Tests et preuve

Les tests couvrent notamment la génération du planning, les inspirations, les affectations, les capacités, les briefings, l'immuabilité des versions, la validation humaine, la transmission idempotente, l'export du rapport, le dispatch du job de rapport par les événements et le scénario de démonstration.

Pour que les rapports soient actualisés automatiquement après une approbation, un rejet ou un handoff, le worker doit être actif (`queue:work`). Sans worker, les jobs restent en attente dans la table `jobs` ; le workflow métier reste enregistré normalement.

Les résultats doivent être copiés depuis l'exécution locale réelle de `./vendor/bin/sail artisan test`. Ne pas annoncer un nombre de tests sans avoir exécuté la suite après le dernier changement.

## 9. Limites explicites

- Les inspirations sont des exemples synthétiques, pas des tendances mesurées en direct.
- Le média est une référence synthétique ; aucun fichier vidéo n'est téléversé.
- La génération IA est une simulation déterministe avec réponses de test fixes et substitutions contextuelles ; aucun LLM, appel réseau ou clé API n'est utilisé. Un fournisseur réel n'est pas intégré.
- La transmission est une simulation locale.
- Le contrôle de capacité avertit ; il ne replanifie pas automatiquement.
- Les extensions (analytics, attribution, ROI, commentaires, intégrations et disponibilité de rendez-vous) restent hors du MVP.

**Principe directeur :** rendre les décisions, validations, limites et effets du workflow visibles et vérifiables, sans simuler une intégration réelle comme si elle existait.
