<laravel-boost-guidelines>
=== .ai/ARCHITECTURE rules ===

# ARCHITECTURE

Pragmatic DDD + SOLID for Laravel 12. Normative for new code and opportunistic refactors. Do not rewrite existing code for aesthetic reasons.

## Principles

- **Pragmatic DDD**: business rules as first-class concepts; use cases as named classes; business language in class names and methods; context consistency before global reorganization.
- **SOLID**: SRP (single reason to change), OCP (extend over modify), LSP (honor contracts), ISP (small interfaces), DIP (abstractions only when valuable).
- **Explicitness**: business logic easy to locate; visible dependencies; testable without UI coupling; contained side effects.
- **Low coupling**: UI/HTTP/Livewire must not capture core business rules.

## Layers

### `App\Domain\{Context}\...`

Entities, value objects, enums, pure rules, domain services, repository contracts.

**Must not** depend on Livewire, `Request`, `Response`, `Session`, views, or presentation facades.

### `App\Application\{Context}\...` or `App\Actions\{Context}\...`

Use cases and orchestration. Coordinates flow; may use Eloquent pragmatically; may depend on domain services. **Must not** render views or contain HTTP/Livewire logic.

### `App\Infrastructure\{Context}\...`

External integrations, concrete contract implementations, gateways, API adapters. Connects through providers and the container.

### Framework adapters

`App\Livewire`, `App\Http\Controllers`, `App\Http\Requests`, `App\Policies`, `App\Jobs`, `App\Providers`, `App\Models` — delegate meaningful logic to Application/Domain when behavior stops being trivial.

## Dependency rules

| Allowed | Forbidden |
|---|---|
| `Livewire` -> `Action`/`UseCase` | `Domain` -> `Livewire` |
| `Controller` -> `FormRequest` + `Action` | `Domain` -> `Request` |
| `Job` -> `Action`/service | `Action` -> Blade view |
| `Action` -> `Domain Service`, `Model`, contract | `Model` -> multi-step orchestration |
| `Infrastructure` -> SDK, HTTP client, external | `Provider` -> business rules |

Organize by functional context (`App\Domain\Auth\...`), not generic folders (`App\Services\...`, `App\Helpers\...`).

## Rules by artifact

- **Livewire**: state, UI interaction, simple validation, events, redirects. Move logic to Action/UseCase when non-trivial.
- **Fortify Actions**: treat as application use cases; no presentation; extract domain services if rules accumulate.
- **Actions/UseCases**: one responsibility, explicit `handle()`/`execute()`, no UI/transport details.
- **Domain Services**: business semantics, no presentation dependencies, prefer purity.
- **Repository Contracts**: only when needed (multiple implementations, test isolation, real decoupling). No reflexive interfaces.
- **Infrastructure**: lives outside UI, registered through providers, no SDK pollution in domain.
- **FormRequests**: authorization + validation + lightweight normalization. Not for business rules or persistence.
- **Policies**: authorization only. No business logic mix.
- **Jobs**: async/retries/decoupling. Must delegate business behavior to application/domain classes.
- **Models**: relationships, casts, scopes, simple accessors/mutators, small invariants. Not workflow orchestrators.

## Placement quick reference

| Case | Location |
|---|---|
| Use case with business rules | `App\Actions\...` or `App\Application\...` |
| Pure business rule/policy | `App\Domain\{Context}\...` |
| External gateway implementation | `App\Infrastructure\{Context}\...` |
| Reactive UI state/events | `App\Livewire\...` |
| Authorization | `App\Policies\...` |
| HTTP validation | `App\Http\Requests\...` |
| Persistence (relationships, casts, scopes) | `App\Models\...` |
| Bindings and bootstrap | `App\Providers\...` |

## Forbidden anti-patterns

- Controllers/Livewire with extensive business logic
- Generic "service classes" without clear intent
- Interfaces without real variation/decoupling need
- Providers with business rules
- Jobs hiding core logic
- Models as orchestration containers
- Global helpers for business rules
- Moving files just to "look enterprise"

## Adoption

1. Apply to new features.
2. Refactor when existing code shows pain (too many responsibilities, duplication, hidden side effects, poor testability, tight UI coupling).
3. Do not move code for aesthetic reasons.
4. Prioritize context consistency before massive migration.

## Final rule

Adopt only if it improves clarity, testability, and domain evolution without fighting Laravel 12. Reject layers, contracts, or folders without real need.

=== .ai/FORMS rules ===

# FORMS

Normative for new forms, updates to existing forms, and refactors involving Livewire/Blade/Flux UI validation feedback.

## Main rule

Single source of truth for validation feedback per field. If a Flux component renders its own error, do not add a manual `@error` block for the same field.

## Flux field components

Flux fields (`flux:input`, `flux:textarea`, `flux:select`, `flux:otp`, etc.) own their validation error when bound to a validated property. Do not duplicate with `@error(...)`, helper text, or custom error rows.

**Wrong:**
```blade
<flux:input wire:model.live="current_password" :label="__('Current password')" />
@error('current_password')
    <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
@enderror
```

**Right:**
```blade
<flux:input wire:model.live="current_password" :label="__('Current password')" type="password" autocomplete="current-password" viewable />
```

## When manual error output is allowed

- The UI element doesn't render errors itself
- The error belongs to the form/section as a whole, not a single input
- The message is a different concern from field validation
- The component library lacks built-in error for that control

## Validation ownership hierarchy

1. **Field-level** -> field component
2. **Grouped input** -> fieldset/group wrapper
3. **Process/domain** -> callout, alert, modal body, section message

Never show the same message in multiple places simultaneously.

## Multi-column alignment

Multi-column rows must use `items-start` (e.g., `grid items-start gap-4 md:grid-cols-2`) so validation errors don't push sibling fields out of alignment.

## Grouped choices and live error recovery

- Use `flux:field` + `flux:checkbox.group` + `flux:error` as group owner
- Validation is group-level, not per-checkbox
- Clear stale errors immediately when user corrects input:

```php
public function updatedRoles(): void
{
    $this->roles = $this->normalizeRoles($this->roles);
    $this->resetValidation('roles');
}

public function updated(string $property): void
{
    if (in_array($property, ['password', 'password_confirmation'], true)) {
        $this->resetValidation(['password', 'password_confirmation']);
    }
}
```

## Autofill boundaries

Browsers ignore visual boundaries (modals, cards). They use `<form>`, `name`, `id`, and `autocomplete` attributes.

**Credential inputs** (password, username, email, re-auth):
- Place in their own `<form>`
- Use correct `autocomplete` tokens (`current-password`, `username`)
- If autofill needs a username companion, provide it (even hidden) with `autocomplete="username"`

**Search/filter inputs**:
- Use specific names (`users_search`, not generic)
- Wrap in `<form autocomplete="off">` when coexisting with credential fields
- Don't use `type="search"` on Flux `clearable` inputs (duplicates clear button)

### Flux + Livewire naming rule

When HTML `name` differs from the Livewire property, use `error:name` to preserve error binding:

```blade
<flux:input wire:model.live="confirmPassword" name="current_password" autocomplete="current-password" error:name="confirmPassword" />
```

## Review checklist

- [ ] Each input has a single validation message source
- [ ] No duplicate errors per field
- [ ] Credential and search fields separated by real `<form>` boundaries
- [ ] `error:name` preserved when `name` differs from Livewire property
- [ ] Grouped controls use single group-level error
- [ ] Errors clear on correction after failed submit
- [ ] Multi-column rows stay top-aligned with errors
- [ ] Sensitive inputs reset when appropriate
- [ ] All labels and messages translated

=== .ai/LIVEWIRE rules ===

# LIVEWIRE

Normative for new components and large refactors. Does not require migrating existing legacy class-based components.

## Main rule

New components use **Multi-file Components (MFC)**. No SFC or class-based for new code.

## Creation commands

```bash

# Reusable component

php artisan make:livewire foo.bar --mfc --test

# Page component

php artisan make:livewire pages::foo.bar --mfc --test

# With JS/CSS

php artisan make:livewire foo.bar --mfc --test --js --css

# Convert legacy

php artisan livewire:convert foo.bar --mfc
```

## Organization

- `pages::` — full-page routable screens with their own route
- `components` — reusable pieces, embedded forms, panels, modals, filters

## MFC structure

Colocated in own directory:

```text
resources/views/pages/users/create/
├── create.php          # state, actions, computed properties

├── create.blade.php    # markup and bindings

└── create.test.php     # tests

```

Optional: `create.js`, `create.css`, `create.global.css` — only when genuinely needed.

## Component responsibilities

**Valid:** state, UI interaction, loading states, screen validation, event dispatching, redirects, user feedback.

**Invalid:** complex business logic, long workflow orchestration, hiding queries/side effects. Delegate to `Action`/`UseCase` when non-trivial.

## Livewire 4 rules

- Close all tags: `<livewire:components.users.filters />`
- Routes: `Route::livewire('/users/create', 'pages::users.create')`
- Use `wire:model.live` for immediate updates; `wire:model.deep` only when explicitly needed
- Every `@foreach` needs `wire:key`
- Use `wire:loading` / `data-loading:*` for loading states
- Avoid JS when Livewire solves it through PHP/Blade

## Testing

Every new component must have a test. Minimum coverage: rendering, initial state, main action.

Additional coverage based on complexity: validation (errors + valid), events, authorization, delegated flows.

## Legacy coexistence

- Do not migrate for aesthetic reasons
- Evaluate `livewire:convert` when a legacy component receives a large change
- Do not mix styles within the same component
- New components follow MFC even if surrounding module is legacy

## Final rule

MFC, small, clear, tested, UI-focused = correct. Business logic in Livewire or unnecessary files = drifting from convention.

=== .ai/TRANSLATIONS rules ===

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

=== .ai/UI_FEEDBACK rules ===

# UI FEEDBACK

Normative for new code and updates to existing UI flows.

## Main rule

Use shared feedback services. No `alert()`, `confirm()`, `prompt()`, or one-off patterns.

- **Confirmations/informational dialogs** -> modal service
- **System messages** (success, error, warning, info) -> toast service

## Modal service

### Confirmation dialogs

Required for any action with side effects needing user confirmation (delete, archive, enable/disable, etc.).

**Copy pattern:**
- **Title**: short, interrogative question (e.g., "Delete user?")
- **Body**: action description + affected record identifier + consequences

```text
Title: Delete user?
Body: You are about to delete "Jane Doe" (#15). This action permanently removes the account from the system.
```

Do not repeat the question in the body. Do not use generic body ("This action cannot be undone") without identifying the record.

### Informational dialogs

For focused non-blocking information: explanations before sensitive actions, contextual warnings, details too important for a toast.

## Toast service

Default for all state-change feedback after operations.

**Copy pattern:** state the result + identify the affected record.

```text
The user "Jane Doe" (#15) was deleted successfully.
```

## Model identification

Always display `name` + `id` in modals and toasts referencing a model.

If the model has no `name` attribute, **ask which attribute to use** before implementing. Minimum fallback: model `id`.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/flux (FLUXUI_FREE) - v2
- livewire/flux-pro (FLUXUI_PRO) - v2
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `fluxui-development` — Use this skill for Flux UI development in Livewire applications only. Trigger when working with <flux:*> components, building or customizing Livewire component UIs, creating forms, modals, tables, or other interactive elements. Covers: flux: components (buttons, inputs, modals, forms, tables, date-pickers, kanban, badges, tooltips, etc.), component composition, Tailwind CSS styling, Heroicons/Lucide icon integration, validation patterns, responsive design, and theming. Do not use for non-Livewire frameworks or non-component styling.
- `livewire-development` — Use for any task or question involving Livewire. Activate if user mentions Livewire, wire: directives, or Livewire-specific concepts like wire:model, wire:click, wire:sort, or islands, invoke this skill. Covers building new components, debugging reactivity issues, real-time form validation, drag-and-drop, loading states, migrating from Livewire 3 to 4, converting component formats (SFC/MFC/class-based), and performance optimization. Do not use for non-Livewire reactive UI (React, Vue, Alpine-only, Inertia.js) or standard Laravel forms without Livewire.
- `pest-testing` — Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, or architecture tests. Covers: it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), smoke testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code.
- `tailwindcss-development` — Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS.
- `fortify-development` — ACTIVATE when the user works on authentication in Laravel. This includes login, registration, password reset, email verification, two-factor authentication (2FA/TOTP/QR codes/recovery codes), profile updates, password confirmation, or any auth-related routes and controllers. Activate when the user mentions Fortify, auth, authentication, login, register, signup, forgot password, verify email, 2FA, or references app/Actions/Fortify/, CreateNewUser, UpdateUserProfileInformation, FortifyServiceProvider, config/fortify.php, or auth guards. Fortify is the frontend-agnostic authentication backend for Laravel that registers all auth routes and controllers. Also activate when building SPA or headless authentication, customizing login redirects, overriding response contracts like LoginResponse, or configuring login throttling. Do NOT activate for Laravel Passport (OAuth2 API tokens), Socialite (OAuth social login), or non-auth Laravel features.
- `blaze-optimize` — Set up and optimize Blade component rendering with Blaze. Use when installing Blaze, optimizing components, or configuring @blaze directives and strategies.
- `medialibrary-development` — Build and work with spatie/laravel-medialibrary features including associating files with Eloquent models, defining media collections and conversions, generating responsive images, and retrieving media URLs and paths.
- `laravel-permission-development` — Build and work with Spatie Laravel Permission features, including roles, permissions, middleware, policies, teams, and Blade directives.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>
