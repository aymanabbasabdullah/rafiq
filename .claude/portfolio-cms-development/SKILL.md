---
name: portfolio-cms-development
description: >-
  Use this skill when building or modifying any of the 26 Admin Dashboard CMS modules,
  CRUD workflows, bilingual content forms, publishing states, contact inquiry handlers,
  SEO metadata editors, or activity logs.
---

# Portfolio CMS Development Skill

## 1. Skill Name
`portfolio-cms-development`

## 2. Purpose
Standardize the implementation of all 26 Admin Dashboard modules for managing projects, case studies, services, software/tools, skills, experience, education, certificates, testimonials, blog posts, inquiries, SEO settings, and site identity.

## 3. When to Use It
- Implementing backend CRUD controllers, FormRequests, Services, and Policies under `Admin\`.
- Building Admin React pages (`resources/js/Pages/Admin/*`) using `shadcn/ui`, `TanStack Table`, `React Hook Form`, `Zod`, and `Tiptap`.
- Implementing bilingual (`ar` / `en`) content editing tabs and localized slug generation.
- Building draft/published/archived state transitions, drag-and-drop ordering (`sort_order`), and audit logging (`activity_logs`).

## 4. When Not to Use It
- Building public-facing WebGL/3D canvases or public scroll choreographies (use `react-three-fiber-development` or `animation-engine-development`).

## 5. Required Knowledge
- Laravel 13 FormRequests, Eloquent polymorphic relations (`seo_settings`, `translations`, `activity_logs`), and database transactions.
- Inertia.js form handling integrated with `react-hook-form` + `@hookform/resolvers/zod`.
- `TanStack Table` v8 for server-side/client-side filtering, sorting, bulk actions, and pagination.
- `Tiptap` rich-text editor with structured JSON/HTML sanitization for case study blocks and blog posts.

## 6. Workflow
1. **Read Module Specification**: Consult `docs/ADMIN_DASHBOARD.md`, `docs/REQUIREMENTS.md`, and `docs/API_AND_ROUTES.md` for the target module's fields, actions, and permissions.
2. **Backend Implementation**:
   - Create/verify Model, Migration, Factory, and Policy.
   - Create `Store*Request` and `Update*Request` validating both English (`en`) and Arabic (`ar`) localized payloads plus structured JSON blocks.
   - Implement Service class (`ProjectService`, `ServiceCatalogService`, etc.) wrapping multi-table writes (entity + translations + media + tags + SEO metadata + activity log) inside `DB::transaction()`.
3. **Frontend Implementation**:
   - Build Index view with `DataTable` (`TanStack Table`), status filter badges, search input, quick visibility toggle, and drag-and-drop reorder mode where required.
   - Build Create/Edit view with bilingual language switcher tabs (`EN` | `AR`), `MediaPickerModal` integration, `SeoMetaEditor` panel, and live preview drawer/button.
   - Trigger `sonner` toast notifications on Inertia `onSuccess` / `onError` callbacks.
4. **Cache & Activity Audit**:
   - Log every `created`, `updated`, `deleted`, `published`, and `reordered` action via `ActivityLoggerService`.
   - Flush affected public cache keys (`CacheInvalidatorService`).

## 7. Coding Standards
- Never trust raw HTML from rich-text editors; sanitize Tiptap output on the server or store structured Tiptap JSON blocks.
- Every reorder endpoint (`PATCH /admin/{resource}/reorder`) must validate an array of `{ id, sort_order }` items and update them atomically.
- Every publishable entity (`Project`, `Page`, `BlogPost`) must support `status` (`draft`, `published`, `archived`) and `published_at`.
- Contact inquiries (`contact_messages`) must implement Honeypot + rate limiting (`throttle:contact-submissions`) + MIME-restricted attachment storage on a private disk.

## 8. Quality Standards
- Admin forms must preserve unsaved state across language tab switches (`EN` <-> `AR`).
- Validation errors from Laravel FormRequests must map cleanly to field-level error messages in React Hook Form, including nested localized fields (e.g., `translations.ar.title`).
- All destructive actions (Delete Project, Delete Media, Delete Section) require an accessible confirmation dialog (`AlertDialog`).

## 9. Validation Requirements
- Write PHPUnit Feature tests for every Admin CRUD endpoint covering: unauthenticated redirect, validation errors, successful creation with translations and SEO metadata, update, reorder, and deletion.
- Verify activity log creation on mutations.

## 10. Related Project Documents
- `docs/ADMIN_DASHBOARD.md`
- `docs/BACKEND_PLAN.md`
- `docs/FRONTEND_PLAN.md`
- `docs/API_AND_ROUTES.md`
- `docs/SECURITY_PLAN.md`
- `docs/TESTING_PLAN.md`

## 11. Dependencies on Other Skills
- `portfolio-architecture`
- `laravel-best-practices`
- `testing-best-practices`
- `media-library-management`
- `rtl-ltr-localization`

