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
