# Audit complet — symkit/form-bundle

> Date : 2026-02-22
> Branche : `claude/bundle-audit-plan-Im7rq`

---

## 1. Résumé exécutif

Le bundle est **fonctionnel** et bien structuré en termes de features (5 form types, 6 extensions, 4 Live Components, thème Tailwind, 8 contrôleurs Stimulus). Cependant, il manque une partie significative de l'outillage qualité et plusieurs conventions définies dans `.cursor/rules/` ne sont pas respectées.

**Score global : 5/10** — Code fonctionnel mais infrastructure qualité et conformité aux rules incomplètes.

---

## 2. Violations détectées

### 2.1 PHP — Appels natifs sans backslash (`\`)

**Règle** : `native_function_invocation` avec `@compiler_optimized` — tout appel natif doit être préfixé `\`.

| Fichier | Ligne | Appel fautif |
|---------|-------|-------------|
| `src/FormBundle.php` | 152 | `array_merge(...)`, `array_values(...)` |
| `src/Form/Type/SlugType.php` | 52 | `method_exists(...)` |
| `src/Form/Type/IconPickerType.php` | 24-25 | `array_flip(...)`, `array_combine(...)`, `array_keys(...)` |
| `src/Form/Extension/TranslatableExtension.php` | 43 | `array_unique(...)`, `array_merge(...)` |
| `src/Form/Extension/DependencyExtension.php` | 36 | `trim(...)` |
| `src/Twig/Component/PasswordField.php` | 57-59, 62-63, 66 | `preg_match(...)`, `array_filter(...)`, `ceil(...)` |
| `src/Twig/Component/Slug.php` | 141 | `method_exists(...)` |

### 2.2 PHP — Classes non `readonly`

**Règle** : les services, handlers, processors doivent être `final readonly class`.

| Classe | Statut actuel | Attendu |
|--------|--------------|---------|
| `HeroiconProvider` | `final class` | `final readonly class` |
| Toutes les `AbstractType` extensions | `final class` | `final class` (OK — héritage Symfony empêche `readonly`) |
| Twig Components (`Slug`, `RichSelect`, etc.) | `final class` | `final class` (OK — `#[LiveProp]` nécessite mutable) |

> Seul `HeroiconProvider` peut et doit être `readonly`.

### 2.3 PHP — Service IDs non préfixés

**Règle** : tous les service IDs doivent être préfixés avec l'alias du bundle (`form_bundle.`).

Dans `FormBundle::loadExtension()`, les services sont enregistrés avec leur FQCN (ex: `HeroiconProvider::class`) mais sans alias préfixé. Il faudrait au minimum ajouter des alias `form_bundle.*` pour chaque service public.

### 2.4 PHP — Configuration XML manquante

**Règle** : utiliser `config/services.xml` pour la définition des services, pas le PHP fluent dans `loadExtension()`.

Actuellement, toute la DI est en PHP fluent dans `FormBundle::loadExtension()`. La règle exige du XML.

**Impact** : moyen — le PHP fluent fonctionne mais ne respecte pas la convention du bundle.

### 2.5 Composer — Contraintes de versions UX incorrectes

**Règle** : Symfony `^7.0 || ^8.0` (jamais `7.*|8.*`).

```json
"symfony/ux-icons": "2.*",           // ❌ devrait être "^2.0"
"symfony/ux-live-component": "2.*"   // ❌ devrait être "^2.0"
```

### 2.6 Composer — `doctrine/orm` en dépendance dure

`doctrine/orm` est dans `require` mais n'est utilisé que dans `SlugType` et `Slug` (component) pour la vérification d'unicité. Il devrait être dans `suggest` avec un guard `class_exists()`.

### 2.7 Composer — Dev dependencies manquantes

| Package | Utilité |
|---------|---------|
| `friendsofphp/php-cs-fixer` | Code style (cs-fix, cs-check) |
| `infection/infection` | Mutation testing (MSI >= 70%) |
| `qossmic/deptrac` | Validation d'architecture |
| `phpro/grumphp` | Pre-commit hooks |

`allow-plugins` manquant pour `infection/extension-installer` et `phpro/grumphp`.

---

## 3. Fichiers de configuration manquants

| Fichier | Statut | Description |
|---------|--------|-------------|
| `Makefile` | ❌ MANQUANT | Targets : `cs-fix`, `cs-check`, `phpstan`, `test`, `quality`, `ci`, `install-hooks` |
| `.php-cs-fixer.dist.php` | ❌ MANQUANT | Config `@Symfony` + `@PHP82Migration` + `native_function_invocation` |
| `grumphp.yml` | ❌ MANQUANT | Pre-commit hooks (phpstan, cs-fixer, phpunit, deptrac, infection) |
| `deptrac.yaml` | ❌ MANQUANT | Layers : Entity, Service, Contract, Form, Twig |
| `infection.json5` | ❌ MANQUANT | Config mutation testing, MSI >= 70% |
| `config/services.xml` | ❌ MANQUANT | Service definitions en XML |
| `package.json` | ❌ MANQUANT | Metadata `symfony.controllers` pour AssetMapper |
| `assets/dist/` | ❌ MANQUANT | Assets pré-compilés distribués |
| `src/Contract/` | ❌ MANQUANT | Interfaces publiques (BC-safe API) |
| `scripts/git-hooks/commit-msg` | ❌ MANQUANT | Strip AI signatures des commits |

---

## 4. Tests — Couverture insuffisante

### 4.1 Tests unitaires manquants

| Classe | Fichier test | Statut |
|--------|-------------|--------|
| `ActiveInactiveType` | `tests/Unit/Form/Type/ActiveInactiveTypeTest.php` | ❌ MANQUANT |
| `SitemapPriorityType` | `tests/Unit/Form/Type/SitemapPriorityTypeTest.php` | ❌ MANQUANT |
| `FormSectionType` | `tests/Unit/Form/Type/FormSectionTypeTest.php` | ❌ MANQUANT |
| `PasswordExtension` | `tests/Unit/Form/Extension/PasswordExtensionTest.php` | ❌ MANQUANT |
| `TranslatableExtension` | `tests/Unit/Form/Extension/TranslatableExtensionTest.php` | ❌ MANQUANT |
| `UrlExtension` | `tests/Unit/Form/Extension/UrlExtensionTest.php` | ❌ MANQUANT |
| `CheckboxRichSelectExtension` | `tests/Unit/Form/Extension/CheckboxRichSelectExtensionTest.php` | ❌ MANQUANT |

**7 classes sur 16 ne sont pas testées unitairement** (44% de couverture en nombre de classes).

### 4.2 Tests d'intégration

- `BundleBootTest` ✅ présent avec `restore_exception_handler()` dans `tearDown()`
- Manque : test de validation de la configuration (clés requises, defaults)
- Manque : test du Twig prepend (paths, globals)

---

## 5. JavaScript — Violations Stimulus

### 5.1 `stimulusFetch: 'lazy'` manquant

| Fichier | Statut |
|---------|--------|
| `dropdown_controller.js` | ❌ MANQUANT |
| `password-visibility_controller.js` | ❌ MANQUANT |
| `rich-select_controller.js` | ❌ MANQUANT |
| `section-nav_controller.js` | ❌ MANQUANT |
| `slug_controller.js` | ❌ MANQUANT |
| `url-preview_controller.js` | ❌ MANQUANT |

Seuls `dependency_controller.js` et `table-of-contents_controller.js` ont le commentaire.

### 5.2 `disconnect()` manquant

| Fichier | Statut |
|---------|--------|
| `dependency_controller.js` | ❌ Pas de cleanup |
| `dropdown_controller.js` | ❌ Pas de cleanup |
| `password-visibility_controller.js` | ❌ Pas de cleanup |
| `url-preview_controller.js` | ❌ Pas de cleanup |

### 5.3 API interdite

- `rich-select_controller.js:14` — utilise `document.addEventListener('click', ...)` au lieu du binding Stimulus. Le listener n'est jamais nettoyé (fuite mémoire).

---

## 6. Templates Twig — Violations

### 6.1 Attributs `data-*` manuels

Les règles interdisent les `data-*` manuels dans les components Twig (utiliser les helpers Stimulus/Live).

| Template | Attributs manuels |
|----------|------------------|
| `PasswordField.html.twig` | `data-model` (lignes 3, 8) |
| `Slug.html.twig` | `data-model`, `data-action` (lignes 9, 17, 31) |
| `TranslatableField.html.twig` | `data-model`, `data-value`, `data-action` (lignes 6, 7, 15, 17, 21) |
| `tailwind_layout.html.twig` | `data-model` (lignes 8, 15, 21, 44) |

> Note : `data-model` et `data-action` sont des attributs Live Component / Stimulus qui peuvent nécessiter une écriture manuelle. L'usage est acceptable ici mais devrait être validé.

### 6.2 Préfixe CSS bundle manquant

**Règle** : les classes CSS doivent être préfixées avec le nom du bundle (`form-bundle-`).

Aucun des templates n'utilise de classes CSS préfixées. Tout utilise des classes Tailwind utilitaires directement. Si des classes custom sont ajoutées, elles devront être préfixées.

---

## 7. Architecture — `src/Contract/` manquant

**Règle** : les interfaces publiques doivent être dans `src/Contract/` pour former la BC-safe API.

Actuellement, aucune interface n'existe. Les classes concrètes sont directement utilisées comme API publique. Il faudrait extraire au minimum :

- `IconProviderInterface` (pour `HeroiconProvider`)
- Potentiellement des interfaces pour les types et extensions configurables

---

## 8. Distribution — Flex recipe manquant

Pas de recipe Symfony Flex. Pour un bundle distribué, il faudrait :

- `config/routes.yaml` (si des routes sont nécessaires — pas le cas actuellement)
- `package.json` avec metadata `symfony.controllers`
- `assets/dist/` avec les assets pré-compilés
- Contribution au repo `symfony/recipes-contrib` (ou recipe privé)

---

## 9. Plan d'action (priorisé)

### Phase 1 — Outillage qualité (priorité haute)

| # | Tâche | Fichier(s) |
|---|-------|-----------|
| 1.1 | Créer le `Makefile` avec tous les targets requis | `Makefile` |
| 1.2 | Créer `.php-cs-fixer.dist.php` (`@Symfony` + `@PHP82Migration` + `native_function_invocation`) | `.php-cs-fixer.dist.php` |
| 1.3 | Ajouter les dev-deps manquantes dans `composer.json` | `composer.json` |
| 1.4 | Créer `grumphp.yml` | `grumphp.yml` |
| 1.5 | Créer `deptrac.yaml` avec les layers du bundle | `deptrac.yaml` |
| 1.6 | Créer `infection.json5` | `infection.json5` |
| 1.7 | Créer `scripts/git-hooks/commit-msg` | `scripts/git-hooks/commit-msg` |

### Phase 2 — Conformité PHP (priorité haute)

| # | Tâche | Fichier(s) |
|---|-------|-----------|
| 2.1 | Exécuter `make cs-fix` pour corriger tous les appels natifs sans `\` | Tous les fichiers `src/` |
| 2.2 | Rendre `HeroiconProvider` `final readonly class` | `src/Service/HeroiconProvider.php` |
| 2.3 | Corriger les contraintes de version UX dans `composer.json` (`2.*` → `^2.0`) | `composer.json` |
| 2.4 | Déplacer `doctrine/orm` dans `suggest` + ajouter guards `class_exists()` | `composer.json`, `src/Form/Type/SlugType.php`, `src/Twig/Component/Slug.php` |

### Phase 3 — Architecture (priorité moyenne)

| # | Tâche | Fichier(s) |
|---|-------|-----------|
| 3.1 | Créer `src/Contract/IconProviderInterface.php` | `src/Contract/` |
| 3.2 | Faire implémenter l'interface par `HeroiconProvider` | `src/Service/HeroiconProvider.php` |
| 3.3 | Migrer la DI de PHP fluent vers `config/services.xml` | `config/services.xml`, `src/FormBundle.php` |
| 3.4 | Ajouter des alias préfixés `form_bundle.*` pour les services publics | `config/services.xml` |

### Phase 4 — Tests (priorité moyenne)

| # | Tâche | Fichier(s) |
|---|-------|-----------|
| 4.1 | Créer `ActiveInactiveTypeTest` | `tests/Unit/Form/Type/` |
| 4.2 | Créer `SitemapPriorityTypeTest` | `tests/Unit/Form/Type/` |
| 4.3 | Créer `FormSectionTypeTest` | `tests/Unit/Form/Type/` |
| 4.4 | Créer `PasswordExtensionTest` | `tests/Unit/Form/Extension/` |
| 4.5 | Créer `TranslatableExtensionTest` | `tests/Unit/Form/Extension/` |
| 4.6 | Créer `UrlExtensionTest` | `tests/Unit/Form/Extension/` |
| 4.7 | Créer `CheckboxRichSelectExtensionTest` | `tests/Unit/Form/Extension/` |
| 4.8 | Ajouter test de validation de config dans `BundleBootTest` | `tests/Integration/` |
| 4.9 | Ajouter test du Twig prepend | `tests/Integration/` |

### Phase 5 — JavaScript Stimulus (priorité moyenne)

| # | Tâche | Fichier(s) |
|---|-------|-----------|
| 5.1 | Ajouter `/* stimulusFetch: 'lazy' */` aux 6 contrôleurs manquants | `assets/controllers/` |
| 5.2 | Ajouter `disconnect()` aux 4 contrôleurs manquants | `assets/controllers/` |
| 5.3 | Remplacer `document.addEventListener` par binding Stimulus dans `rich-select_controller.js` | `assets/controllers/rich-select_controller.js` |

### Phase 6 — Distribution (priorité basse)

| # | Tâche | Fichier(s) |
|---|-------|-----------|
| 6.1 | Créer `package.json` avec metadata `symfony.controllers` | `package.json` |
| 6.2 | Créer `assets/dist/` avec assets pré-compilés | `assets/dist/` |
| 6.3 | Préparer la Flex recipe (optionnel) | `config/` |

---

## 10. Récapitulatif des violations

| Catégorie | Nombre de violations |
|-----------|---------------------|
| PHP — Appels natifs sans `\` | ~15 occurrences dans 7 fichiers |
| PHP — Classes non `readonly` | 1 (`HeroiconProvider`) |
| PHP — Service IDs non préfixés | Tous les services |
| Composer — Contraintes versions | 2 packages UX |
| Composer — Deps manquantes | 4 dev-deps |
| Fichiers config manquants | 10 fichiers |
| Tests manquants | 7 unit + 2 intégration |
| JS — `stimulusFetch` manquant | 6 contrôleurs |
| JS — `disconnect()` manquant | 4 contrôleurs |
| JS — API interdite | 1 occurrence |
| **Total** | **~50 violations** |
