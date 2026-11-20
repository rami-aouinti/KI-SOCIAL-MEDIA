# KI Social Media

MVP Laravel pour préparer un planning éditorial hebdomadaire, construire des briefings, conserver les versions de contenu, obtenir une validation humaine et simuler une transmission vers une interface de publication.

> **Périmètre de démonstration :** données synthétiques uniquement. Aucune publication réelle sur Instagram, Facebook, TikTok ou LinkedIn.

## 1. Contrainte et principe

Le cadre initial prévoit **4 heures maximum (240 minutes), documentation et tests compris**. Le temps réellement consommé doit être déclaré honnêtement dans la livraison ; il ne faut pas prétendre respecter cette limite sans mesure réelle.

Le projet privilégie un workflow métier complet et testable plutôt qu'une plateforme social media complète.

## 2. Fonctionnalités du MVP

- Génération d'un planning hebdomadaire : exactement 3 priorités et 2 idées de réserve.
- Enregistrement d'au moins 3 références d'inspiration synthétiques.
- Répartition des tâches et calcul de capacité (Personne A : 90 min, Personne B : 150 min).
- Briefing de contenu structuré pour une idée prioritaire.
- Création de versions immuables avec variantes par canal et empreinte SHA-256.
- Workflow de validation humaine : `draft` → `in_review` → `approved`.
- Validation liée au hash de la version exacte ; une nouvelle version doit être validée séparément.
- Transmission simulée et idempotente : une seule transmission par version, avec payload JSON persisté.
- Rapport hebdomadaire texte généré depuis les données enregistrées.

Les transmissions ne contactent aucune API externe. Le statut `handed_off` signifie uniquement que le payload a été enregistré dans la simulation.

## 3. Architecture et choix

- **Laravel monolithique** : adapté à un MVP time-boxed, sans coût de coordination de microservices.
- **PHP 8.4 / Laravel / Eloquent** : conventions, validation, migrations et injection de dépendances.
- **MySQL 8.4** : base relationnelle du projet et environnement Docker/Sail.
- **Services applicatifs ciblés** : `WeeklyPlanGenerator`, `CapacityPlanner`, `ContentVersionCreator`, `ContentApprovalService`, `SimulatedContentHandoffService`, `WeeklyReportGenerator`.
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

## 5. Commandes utiles

```bash
# Lancer tous les tests
./vendor/bin/sail artisan test

# Tester une fonctionnalité précise
./vendor/bin/sail artisan test tests/Feature/ContentApprovalServiceTest.php
./vendor/bin/sail artisan test tests/Feature/SimulatedContentHandoffServiceTest.php
./vendor/bin/sail artisan test tests/Feature/WeeklyReportGeneratorTest.php

# Voir les migrations
./vendor/bin/sail artisan migrate:status

# Arrêter les conteneurs
./vendor/bin/sail down
```

## 6. Règles de sécurité métier démontrées

1. Une version ne peut être approuvée que lorsqu'elle est en revue et complète.
2. La validation enregistre le hash exact de la version approuvée.
3. Les champs constitutifs d'une version sont immuables ; une modification crée une nouvelle version.
4. La transmission exige une version approuvée, une validation correspondante au hash exact, un média, des canaux, des variantes et un responsable.
5. Une contrainte unique en base empêche plusieurs transmissions de la même version.
6. Une répétition retourne le résultat déjà enregistré au lieu de créer un doublon.
7. Le rapport est calculé à partir des enregistrements disponibles ; les métriques absentes restent inconnues.

## 7. Tests et preuve

Les tests couvrent notamment la génération du planning, les inspirations, les affectations, les capacités, les briefings, l'immuabilité des versions, la validation humaine, la transmission idempotente et l'export du rapport.

Les résultats doivent être copiés depuis l'exécution locale réelle de `./vendor/bin/sail artisan test`. Ne pas annoncer un nombre de tests sans avoir exécuté la suite.

## 8. Limites explicites

- Les inspirations sont des exemples synthétiques, pas des tendances mesurées en direct.
- Le média est une référence synthétique ; aucun fichier vidéo n'est téléversé.
- La génération IA et la publication externe ne sont pas présentées comme des intégrations actives.
- La transmission est une simulation locale.
- Les extensions (analytics, attribution, ROI, commentaires, intégrations et disponibilité de rendez-vous) restent hors du MVP.

**Principe directeur :** rendre les décisions, validations, limites et effets du workflow visibles et vérifiables, sans simuler une intégration réelle comme si elle existait.
