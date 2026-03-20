# TRANSLATIONS

Normative for new code and updates to existing UI flows.

## Main rule

Every user-facing text must be translated in both `en` and `es`. Both locales must be added in the same change. No hardcoded text in Blade, Livewire, PHP, or JS-generated UI.

Use `__()`, `trans()`, or `@lang()` for all UI strings: titles, labels, placeholders, buttons, table headers, validation messages, modal copy, toasts, empty states, etc.

## Domain translation structure

Group translations by functional domain:
- `lang/{locale}/{domain}.php` (e.g., `auth.php`, `actions.php`)
- Same key structure in `en` and `es`
- Related strings together, not scattered across files

## Reuse before creating

Before creating a new key, check existing shared translations:
- `lang/{locale}/actions.php` — common actions (save, cancel, delete, edit)
- `lang/{locale}.json` — shared UI labels (name, email, search)

Reuse existing keys. Do not duplicate generic strings in domain files.

## When to create new keys

- Text is domain-specific
- No equivalent shared translation exists
- Reusing a generic key would be misleading

Domain-specific text -> domain file. Generic text with no existing key -> shared file.

## Copy quality

Verify spelling, grammar, punctuation, and accentuation **before** creating the key. Both languages.

## Review checklist

- [ ] Every new UI string translated in both `en` and `es`
- [ ] Placed in correct domain file
- [ ] No duplicate of existing shared translation
- [ ] Keys clear and consistent with surrounding module
