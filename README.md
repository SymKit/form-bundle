# Symkit Form Bundle

Symfony bundle for premium form types, extensions, Twig live components (RichSelect, Slug, Password, Translatable), Tailwind CSS theme and Stimulus controllers.

Part of [SymKit](https://github.com/symkit).

## Features

- **Form types**: Slug, SitemapPriority, IconPicker, ActiveInactive, FormSection.
- **Form extensions**: RichSelect, Password, Translatable, Url, Dependency, CheckboxRichSelect.
- **Twig components**: RichSelect, PasswordField, TranslatableField, Slug (all Live Components).
- **Sectioned forms**: card-based layout with sticky navigation.
- **Tailwind theme**: full dark mode support.
- **Stimulus controllers**: dependency, dropdown, password-visibility, rich-select, section-nav, slug, table-of-contents, url-preview.

## Installation

```bash
composer require symkit/form-bundle
```

## Configuration

All features are enabled by default. You can disable specific features:

```php
// config/packages/symkit_form.php
return static function (SymfonyComponentDependencyInjectionLoaderConfiguratorContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('symkit_form', [
        'form_types' => [
            'slug' => true,
            'sitemap_priority' => true,
            'icon_picker' => true,
            'active_inactive' => true,
            'form_section' => true,
        ],
        'form_extensions' => [
            'rich_select' => true,
            'password' => true,
            'translatable' => true,
            'url' => true,
            'dependency' => true,
            'checkbox_rich_select' => true,
        ],
        'components' => [
            'slug' => true,
            'rich_select' => true,
            'password_field' => true,
            'translatable_field' => true,
        ],
    ]);
};
```

## Tailwind Theme

To use the Tailwind form theme, add to your Twig config:

```yaml
twig:
    form_themes: ['@SymkitForm/form/tailwind_layout.html.twig']
```

## Contributing

- Quality: `make quality` (cs-check, phpstan, tests)
- Tests: `vendor/bin/phpunit`
