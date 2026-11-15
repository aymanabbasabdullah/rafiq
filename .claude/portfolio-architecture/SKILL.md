---
name: portfolio-architecture
description: >-
  Use this skill when designing, implementing, or reviewing system architecture,
  module boundaries, Inertia.js v2 data contracts, database models, or cross-cutting
  patterns for the Assad Creative Designer Portfolio & CMS (Laravel 13 + React 19 +
  Inertia.js + Tailwind CSS 4 + MySQL 8).
---

# Portfolio Architecture Skill

## 1. Skill Name
`portfolio-architecture`

## 2. Purpose
Enforce the modular monolith architecture, strict layer boundaries, Inertia.js v2 shared/page prop contracts, and database-driven configuration model of the Assad Creative Designer Portfolio & CMS. Ensures that backend (Laravel 13) and frontend (React 19 + TypeScript) modules remain decoupled, type-safe, and free of hardcoded content, themes, or animations.

## 3. When to Use It
- Designing new features, modules, or database tables.
- Defining Inertia.js page props, shared data (`HandleInertiaRequests`), or JSON API endpoints.
- Reviewing code for architectural consistency across Controllers, FormRequests, Actions/Services, Policies, and React feature modules.
- Resolving boundaries between SSR/Inertia payloads and asynchronous client fetches.

## 4. When Not to Use It
- Writing isolated CSS styles or tweaking single animation curves (use `theme-system-development` or `animation-engine-development`).
- Performing standalone server administration unrelated to application architecture.

## 5. Required Knowledge
- Laravel 13 directory structure, Service Container, Eloquent ORM, FormRequests, Policies, Events, Jobs, and Cache tags/keys.
- Inertia.js v2 protocol (partial reloads, deferred props, shared props, SSR hydration, form helpers).
- React 19 + TypeScript strict mode, feature-based folder organization, and Zustand state slices for complex builders.
- MySQL 8 normalized relational schema with targeted JSON configuration columns validated by schemas.

## 6. Workflow
1. **Read Architecture Specs**: Inspect `docs/SYSTEM_ARCHITECTURE.md`, `docs/DATABASE_SCHEMA.md`, `docs/BACKEND_PLAN.md`, and `docs/FRONTEND_PLAN.md`.
2. **Verify Domain Placement**:
   - Determine whether the feature belongs to `PublicWebsite`, `AdminCMS`, `PageBuilder`, `ThemeEngine`, `AnimationEngine`, `ThreeDEngine`, `MediaLibrary`, or `Localization`.
3. **Define Data Contract**:
   - Specify the Eloquent Model(s), Resource/DTO structure, and corresponding TypeScript interfaces in `resources/js/types/`.
4. **Enforce Layering**:
   - HTTP Layer: Thin Controller (`app/Http/Controllers/Public/` or `app/Http/Controllers/Admin/`) -> delegates validation to `FormRequest` and authorization to `Policy`.
   - Domain Layer: `app/Services/` or single-purpose `app/Actions/` handles business logic, transactions, media binding, cache invalidation, and activity logging.
   - Data Layer: Eloquent Models with explicit `$fillable`, `casts()`, query scopes, and eager-loaded relationships.
5. **Validate Caching & Invalidation**:
   - Public read queries (active theme tokens, navigation, published home sections, localization dictionaries) must be cached and invalidated automatically when modified in the Admin Dashboard.

## 7. Coding Standards
- **Strict Typing**: Use `declare(strict_types=1);` in all PHP files, explicit parameter and return types on all methods, and strict TypeScript interfaces (no `any`) on the frontend.
- **Zero Hardcoding Rule**: Never hardcode portfolio text, social links, colors, fonts, animation timings, or 3D asset URLs in React components or Blade views.
- **Thin Controllers**: Controllers must contain at most 15–25 lines per action: authorize, validate via FormRequest, invoke Service/Action, and return `Inertia::render()` or `redirect()->back()->with('success', ...)`.
- **Idempotent Migrations**: Every migration must define foreign key constraints, composite indexes for localized/filtered queries, and a working `down()` method.

## 8. Quality Standards
- Zero N+1 queries on any public or admin route (verified via `DB::preventLazyLoading(! app()->isProduction())`).
- Inertia shared payload kept lean (< 15 KB compressed) by passing only active locale translations, active theme CSS token map, site identity summary, and navigation items.
- Heavy builder registries (full animation schema catalog, 3D preset catalog) loaded only on Admin routes or via deferred/lazy props.

## 9. Validation Requirements
- Check that every new table matches `docs/DATABASE_SCHEMA.md`.
- Check that every new route matches `docs/API_AND_ROUTES.md`.
- Run `php artisan test` and TypeScript typechecking (`npx tsc --noEmit`) after architectural changes.

## 10. Related Project Documents
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/DATABASE_SCHEMA.md`
- `docs/BACKEND_PLAN.md`
- `docs/FRONTEND_PLAN.md`
- `docs/API_AND_ROUTES.md`
- `docs/DECISIONS_LOG.md`

## 11. Dependencies on Other Skills
- `laravel-best-practices`
- `software-architecture`
- `database-design`
- `react-best-practices`

