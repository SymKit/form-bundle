# Audit complet — Symkit Form Bundle

## 1. Vue d'ensemble du bundle

**Namespace** : `Symkit\FormBundle`
**Type** : Bundle Symfony (PHP 8.2+, Symfony 7/8)
**Licence** : MIT (fichier LICENSE absent)
**Description** : Collection de form types, extensions de formulaire, Live Components Twig et contrôleurs Stimulus avec thème Tailwind CSS.

### Inventaire des composants

| Catégorie | Fichiers | Couverture tests |
|---|---|---|
| Form Types (5) | SlugType, IconPickerType, ActiveInactiveType, FormSectionType, SitemapPriorityType | 2/5 testés |
| Form Extensions (6) | RichSelectExtension, DependencyExtension, PasswordExtension, CheckboxRichSelectExtension, UrlExtension, TranslatableExtension | 2/6 testés |
| Twig Components (4) | Slug, RichSelect, PasswordField, TranslatableField | 4/4 testés |
| Services (1) | HeroiconProvider | 1/1 testé |
| Stimulus Controllers (8) | slug, rich-select, dropdown, password-visibility, section-nav, dependency, url-preview, table-of-contents | 0/8 testés |
| Templates Twig (6) | 4 composants + tailwind_layout + sectioned_form | — |
| Traductions (2) | EN, FR (XLIFF) | — |

---

## 2. Points forts

- **Architecture propre** : Séparation claire Types / Extensions / Components / Services.
- **Configuration granulaire** : Chaque fonctionnalité est activable/désactivable individuellement via `symkit_form.yaml`.
- **PHP moderne** : `declare(strict_types=1)`, classes `final`, readonly properties, promoted constructor properties.
- **Thème Tailwind complet** : Le `tailwind_layout.html.twig` couvre tous les widgets standards (input, textarea, select, checkbox, radio, button, file, date, time, url).
- **Support dark mode** : Toutes les classes Tailwind incluent des variantes `dark:`.
- **Live Components** : Bonne utilisation de Symfony UX Live Components pour les interactions complexes.
- **51 tests passent**, 923 assertions, PHPUnit 11 bien configuré.
- **Documentation** : README clair + 6 pages de documentation dans `docs/`.

---

## 3. Problèmes identifiés

### 3.1 PHPStan — 8 erreurs (level max)

Le badge README affiche "PHPStan level max" mais l'analyse échoue avec **8 erreurs** :

| Fichier | Ligne | Erreur |
|---|---|---|
| `CheckboxRichSelectExtension.php` | 21 | `Cannot access an offset on mixed` — `$view->vars['block_prefixes']` |
| `DependencyExtension.php` | 36 | 3 erreurs sur `$view->vars['attr']['data-controller']` (accès offset + concaténation sur mixed) |
| `PasswordExtension.php` | 22 | `Cannot access an offset on mixed` — `$view->vars['block_prefixes']` |
| `RichSelectExtension.php` | 22 | `Cannot access an offset on mixed` — `$view->vars['block_prefixes']` |
| `TranslatableExtension.php` | 34 | `in_array()` reçoit `mixed` au lieu de `array` pour `$view->vars['block_prefixes']` |
| `Slug.php` | 145 | `ensureUniqueness()` retourne `mixed` au lieu de `string` (appel de méthode dynamique via `$repository->{$this->repositoryMethod}()`) |

**Impact** : La CI qui annonce PHPStan level max ne passe pas en réalité. Fiabilité du pipeline affichée est fausse.

### 3.2 Couverture de tests — 53% des fichiers source

**8 fichiers PHP sans aucun test** :

| Fichier non testé | Complexité |
|---|---|
| `ActiveInactiveType` | Faible — simple ChoiceType configuré |
| `FormSectionType` | Moyenne — options typées + buildView |
| `SitemapPriorityType` | Faible — ChoiceType avec génération de priorités |
| `PasswordExtension` | Moyenne — 5 options typées + buildView |
| `CheckboxRichSelectExtension` | Faible — buildView avec valeurs hardcodées |
| `UrlExtension` | Faible — 1 option + buildView |
| `TranslatableExtension` | Moyenne — logique de merge des locales |
| `FormBundle` | Élevée — configure(), loadExtension(), prependExtension() |

**Autres lacunes** :
- Composant `Slug::ensureUniqueness()` non testé avec Doctrine (uniquement le cas `doctrine === null`)
- Aucun test JavaScript pour les 8 contrôleurs Stimulus
- Pas de configuration de coverage dans `phpunit.xml.dist`

### 3.3 Sécurité

| Problème | Fichier | Sévérité |
|---|---|---|
| **Injection SQL potentielle** | `Slug.php:163` — `sprintf('e.%s = :slug', $this->slugField)` utilise une `LiveProp` writable dans une requête DQL. Un attaquant pourrait manipuler `slugField` via la requête LiveComponent. | **Haute** |
| **`help_html\|raw`** sans échappement | `tailwind_layout.html.twig:226` — Si `help_html` est activé, le contenu est rendu en `raw`. C'est le comportement standard de Symfony mais le bundle ne documente pas le risque XSS. | Faible (conforme Symfony) |
| **`console.log` en production** | `dependency_controller.js:53` — Un `console.log('Clearing field value:', ...)` est laissé dans le code. | Faible |

### 3.4 Qualité du code

#### PHP

- **`ActiveInactiveType`** : Les labels "Active"/"Inactive" sont hardcodés en anglais au lieu d'utiliser des clés de traduction.
- **`SitemapPriorityType`** : Le label "Sitemap Priority" et le placeholder sont hardcodés en anglais.
- **`CheckboxRichSelectExtension`** : Les labels "Yes"/"No" sont hardcodés en anglais.
- **`FormBundle::loadExtension()`** : Les services `ActiveInactiveType`, `SitemapPriorityType`, `FormSectionType`, `SlugType` ne sont pas `autowire()` ni `autoconfigure()`, contrairement à `IconPickerType` et les composants. Incohérence de style.
- **`DependencyExtension::buildView()`** : La logique d'activation par défaut (lignes 62-82) est complexe et pourrait être extraite dans une méthode privée.
- **`Slug::ensureUniqueness()`** : Le `slugField` vient d'une `LiveProp` publique et est injecté dans du DQL via `sprintf` — vecteur d'injection.

#### JavaScript

- **`dropdown_controller.js`** : Dépend de `stimulus-use` (`useTransition`) qui n'est pas dans `composer.json` ni dans un `package.json`. Dépendance non documentée.
- **`rich-select_controller.js`** : Dépend de `@symfony/ux-live-component` (`getComponent`). OK car requis dans composer.json.
- **Aucun `package.json`** : Les dépendances JavaScript ne sont pas formellement gérées (stimulus-use, @hotwired/stimulus).
- **`table-of-contents_controller.js`** : Utilisation de `innerHTML = ''` — préférer `replaceChildren()` pour la sécurité.

#### Templates Twig

- **Classes CSS dupliquées** : Les classes d'input (`block w-full rounded-md bg-white px-3 py-1.5...`) sont copiées-collées dans `form_widget_simple`, `textarea_widget`, `choice_widget_collapsed` et les composants. Devrait être factorisé.
- **`tailwind_layout.html.twig:14`** : `form.parent is empty` est probablement un bug — devrait être `form.parent is null`. `is empty` retourne `true` pour un parent sans enfants, ce qui n'est pas la sémantique voulue.
- **Chaînes non traduites** : "No results found" dans `RichSelect.html.twig:101`, "Unlock to edit" / "Lock to sync with name" dans `Slug.html.twig:20`, "Open link in new window" dans `tailwind_layout.html.twig:266`.

#### Traductions

- **Incohérence des `<source>`** : Dans `SymkitForm.fr.xlf`, les `<source>` contiennent les clés de traduction (`form.action.save`) au lieu du texte anglais. Le fichier EN utilise correctement le texte anglais. Les sources devraient être identiques dans les deux fichiers.

### 3.5 Configuration & CI

- **Pas de CI GitHub Actions** : Le dossier `.github/` est absent malgré le badge CI dans le README.
- **Pas de fichier LICENSE** : Le `composer.json` déclare "MIT" mais aucun fichier LICENSE n'est présent.
- **Pas de `.php-cs-fixer.dist.php`** : Aucun outil de formatage configuré.
- **Pas de `package.json`** : Les dépendances JS ne sont pas tracées.
- **Pas de `CHANGELOG.md`**.
- **PHPUnit sans coverage** : `phpunit.xml.dist` ne configure pas de reporting de couverture.

### 3.6 Dépendances

- **`doctrine/orm` en `require`** : Le bundle fonctionne sans Doctrine (le composant Slug gère `doctrine === null`). Doctrine devrait être en `suggest` ou `require` conditionnel, pas en dépendance dure.
- **`symfony/ux-icons` et `symfony/ux-live-component`** : Versions contraintes à `2.*` — pas de compatibilité avec d'éventuelles futures v3.
- **`stimulus-use`** : Dépendance JS utilisée dans `dropdown_controller.js` mais non déclarée nulle part.

### 3.7 Accessibilité

- Les contrôleurs Stimulus `rich-select` et `dropdown` gèrent partiellement le clavier (ArrowUp/Down/Enter/Escape) mais :
  - Pas d'attribut `aria-activedescendant` pour le suivi de la sélection clavier
  - Pas de gestion du focus trap dans le dropdown
  - Le bouton clear (`unselect`) n'est pas navigable au clavier quand le menu est fermé

---

## 4. Plan d'amélioration

### Phase 1 — Corrections critiques (bloquant)

| # | Tâche | Fichier(s) | Priorité |
|---|---|---|---|
| 1.1 | **Corriger les 8 erreurs PHPStan** : ajouter des assertions de type (`assert(is_array(...))`) ou des annotations `@var` pour les accès à `$view->vars`. Caster le retour de `repositoryMethod` en `string`. | 6 fichiers PHP | Critique |
| 1.2 | **Corriger la faille d'injection DQL** dans `Slug::ensureUniqueness()` : valider `$this->slugField` contre une whitelist ou utiliser les metadata Doctrine pour vérifier que le champ existe. | `Slug.php` | Critique |
| 1.3 | **Corriger le bug `is empty`** dans `tailwind_layout.html.twig:14` : remplacer `form.parent is empty` par `form.parent is null`. | `tailwind_layout.html.twig` | Haute |
| 1.4 | **Supprimer le `console.log`** du dependency_controller.js. | `dependency_controller.js` | Haute |

### Phase 2 — Tests manquants

| # | Tâche | Priorité |
|---|---|---|
| 2.1 | Ajouter les tests unitaires pour les 5 classes non testées : `ActiveInactiveType`, `FormSectionType`, `SitemapPriorityType`, `PasswordExtension`, `UrlExtension`. | Haute |
| 2.2 | Ajouter les tests pour `CheckboxRichSelectExtension` et `TranslatableExtension`. | Haute |
| 2.3 | Tester `FormBundle::loadExtension()` et `prependExtension()` avec différentes configurations (features on/off). | Haute |
| 2.4 | Tester `Slug::ensureUniqueness()` avec un EntityRepository mocké (cas nominal, collision, max attempts). | Haute |
| 2.5 | Configurer le reporting de coverage dans `phpunit.xml.dist` avec seuil minimum (80%). | Moyenne |

### Phase 3 — Qualité & cohérence

| # | Tâche | Priorité |
|---|---|---|
| 3.1 | **Internationaliser les labels hardcodés** : "Active"/"Inactive", "Yes"/"No", "Sitemap Priority", "No results found", "Unlock to edit", etc. via des clés de traduction dans les fichiers XLIFF. | Haute |
| 3.2 | **Corriger les `<source>` dans `SymkitForm.fr.xlf`** : les sources doivent contenir le texte anglais, pas les clés de traduction. | Moyenne |
| 3.3 | **Homogénéiser l'enregistrement des services** dans `FormBundle::loadExtension()` : ajouter `->autowire()->autoconfigure()` aux types simples ou le retirer de `IconPickerType` pour être cohérent. | Moyenne |
| 3.4 | **Extraire les classes CSS répétées** dans les templates Twig en macros ou variables. | Faible |
| 3.5 | **Rendre `doctrine/orm` optionnel** : le déplacer de `require` à `suggest` dans composer.json, adapter le wiring du composant Slug. | Moyenne |

### Phase 4 — Infrastructure & CI

| # | Tâche | Priorité |
|---|---|---|
| 4.1 | **Créer `.github/workflows/ci.yml`** : PHPUnit + PHPStan + (optionnel) PHP-CS-Fixer. | Haute |
| 4.2 | **Ajouter le fichier `LICENSE`** (MIT). | Haute |
| 4.3 | **Ajouter `.php-cs-fixer.dist.php`** avec les règles Symfony. | Moyenne |
| 4.4 | **Créer un `CHANGELOG.md`**. | Faible |

### Phase 5 — Améliorations optionnelles

| # | Tâche | Priorité |
|---|---|---|
| 5.1 | Améliorer l'accessibilité du RichSelect : `aria-activedescendant`, focus trap, navigation complète au clavier. | Moyenne |
| 5.2 | Ajouter un `package.json` pour déclarer les dépendances JS (`stimulus-use`, `@hotwired/stimulus`). | Faible |
| 5.3 | Remplacer `innerHTML = ''` par `replaceChildren()` dans `table-of-contents_controller.js`. | Faible |
| 5.4 | Ajouter des tests JS (Vitest ou Jest) pour les contrôleurs Stimulus les plus complexes (rich-select, dependency, slug). | Faible |

---

## 5. Résumé chiffré

| Métrique | Valeur |
|---|---|
| Fichiers PHP source | 17 |
| Fichiers PHP testés | 9/17 (53%) |
| Tests / Assertions | 51 / 923 |
| Erreurs PHPStan (level max) | 8 |
| Contrôleurs Stimulus | 8 |
| Templates Twig | 6 |
| Langues de traduction | 2 (EN, FR) |
| Vulnérabilités potentielles | 1 critique (DQL injection), 1 mineure (console.log) |
| Fichiers d'infrastructure manquants | CI, LICENSE, .php-cs-fixer, package.json, CHANGELOG |
