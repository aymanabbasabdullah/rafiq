---
name: section-builder-development
description: >-
  Use this skill when building or modifying the Visual Section-Based Page Builder,
  the dynamic SectionRenderer, section configuration JSON schemas, drag-and-drop
  section ordering (@dnd-kit), or the real-time Admin Live Preview split screen.
---

# Section Builder Development Skill

## 1. Skill Name
`section-builder-development`

## 2. Purpose
Guide the design and implementation of the schema-driven Visual Page Builder and public `SectionRenderer`. Enables the graphic designer to add, remove, duplicate, hide/show, reorder, and configure page sections (content, layout variant, background effect, spacing, animation preset, and 3D scene attachment) with desktop/mobile/RTL live preview—without fragile free-form DOM editing.

## 3. When to Use It
- Implementing the Admin Page Builder & Home Page Builder (`resources/js/Pages/Admin/PageBuilder/*`).
- Creating or registering a new section type (`hero`, `designer_intro`, `featured_projects`, `about_preview`, `services_overview`, `software_tools`, `skills_matrix`, `experience_timeline`, `creative_process`, `testimonials`, `stats_achievements`, `client_logos`, `contact_cta`, `custom_content`, `project_case_study_blocks`).
- Building `@dnd-kit/core` and `@dnd-kit/sortable` drag-and-drop section trees.
- Implementing the `SectionRenderer` on public pages or the `postMessage` / Zustand Live Preview synchronization bridge.

## 4. When Not to Use It
- Managing global site settings or static auth pages that do not use the dynamic section pipeline.

## 5. Required Knowledge
- Schema-driven UI architecture (Zod + JSON Schema validation on both frontend and Laravel backend).
- `@dnd-kit/core`, `@dnd-kit/sortable`, and `@dnd-kit/modifiers` with keyboard and RTL support.
- `zustand` for managing optimistic builder state, undo/redo history stack, and dirty-state tracking.
- React `lazy()` + `Suspense` for code-splitting heavy section variants.

## 6. Workflow
1. **Read Builder Specification**: Consult `docs/PAGE_BUILDER.md` and `docs/UI_UX_DESIGN_SYSTEM.md`.
2. **Define Section Contract**:
   - Every section type must have a registered `section_type` key, a PHP validation rule set (`SectionSchemaValidator`), a TypeScript interface, default configuration values, and supported `layout_variant` options.
3. **Register Component & Inspector**:
   - Map `section_type` in `resources/js/Features/PageBuilder/sectionRegistry.ts` to:
     - Public Renderer Component (`<HeroSection />`, `<FeaturedProjectsSection />`, etc.)
     - Admin Inspector Form (`<HeroSectionInspector />`, etc.)
     - Default preset payload for "Add Section" and "Duplicate Section".
4. **Connect Wrapper Layers**:
   - Wrap every rendered section in `<SectionShell>` which automatically applies:
     - Visibility check (`is_visible`)
     - Container width & vertical spacing tokens (`spacing_config`)
     - Background effect layer (`background_config`)
     - Declarative animation wrapper (`animation_config_id` / inline override)
     - Optional 3D scene slot (`three_d_asset_id`)
5. **Live Preview Synchronization**:
   - Stream uncommitted builder state from the Admin Inspector to the embedded `<PreviewViewport>` iframe via a typed `postMessage` channel (`BUILDER_STATE_SYNC`), supporting instant switching between Desktop (`1440px`), Tablet (`768px`), Mobile (`390px`), Dark/Light theme, and `AR (RTL)` / `EN (LTR)`.

## 7. Coding Standards
- Never store raw JSX or executable JS inside the `sections` table. Store only validated JSON in `content_config`, `layout_config`, `style_config`, and `animation_config`.
- Ensure section duplication generates a fresh UUID/database record and appends `(Copy)` to the internal admin label while preserving all localized content.
- Use `<ErrorBoundary>` around each individual section in `SectionRenderer` so a misconfigured section degrades gracefully without crashing the entire page.

## 8. Quality Standards
- Drag-and-drop reordering must run at 60fps with accessible keyboard navigation (`Space` + Arrow keys).
- Hidden sections (`is_visible = false`) must be completely omitted from the public Inertia payload (unless the request is an authenticated Admin Preview request).

## 9. Validation Requirements
- Test section creation, duplication, reordering, visibility toggling, and schema validation rejection of malformed section configs.
- Verify that every registered section renders properly in both LTR (`en`) and RTL (`ar`) and across all supported `layout_variant` options.

## 10. Related Project Documents
- `docs/PAGE_BUILDER.md`
- `docs/ADMIN_DASHBOARD.md`
- `docs/FRONTEND_PLAN.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/ANIMATION_ENGINE.md`

## 11. Dependencies on Other Skills
- `portfolio-architecture`
- `portfolio-cms-development`
- `theme-system-development`
- `animation-engine-development`

