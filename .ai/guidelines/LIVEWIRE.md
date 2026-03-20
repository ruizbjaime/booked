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
