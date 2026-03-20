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
