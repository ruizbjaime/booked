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
