---
name: theme-system-development
description: >-
  Use this skill when implementing or modifying the Design Token System, Theme Builder,
  Dark/Light/Custom theme switching, CSS variable injection, Tailwind CSS 4 @theme
  mapping, glassmorphism tokens, or typography token management.
---

# Theme System Development Skill

## 1. Skill Name
`theme-system-development`

## 2. Purpose
Implement a database-driven, zero-hardcoding Visual Identity & Design Token System powered by CSS Custom Properties and Tailwind CSS v4. Allows the designer to customize colors, gradients, Arabic/English typography, border radii, shadows, glassmorphism parameters, and component variants from the Admin Theme Builder with instant live preview and zero FOUC (Flash of Unstyled Content).

## 3. When to Use It
- Implementing the `themes` and `theme_settings` database models, services, and caching layer.
- Configuring `resources/css/app.css` with Tailwind CSS v4 `@import "tailwindcss";` and `@theme` token bindings.
- Building the `ThemeProvider`, server-side inline `<style id="theme-tokens">` injection in `app.blade.php`, and visitor Dark/Light mode switcher.
- Building the Admin Theme Builder UI (`react-colorful`, font family/scale controls, glass blur sliders, preset selector).

## 4. When Not to Use It
- Writing backend business logic unrelated to visual presentation or theming.

## 5. Required Knowledge
- Tailwind CSS v4 CSS-first `@theme` directive and dynamic CSS variable mapping.
- Color spaces (`oklch` / `hsl` / `hex`) and WCAG 2.1 AA contrast ratios (minimum 4.5:1 for body text, 3:1 for large display text and UI components).
- High-end agency aesthetics (`high-end-visual-design` Double-Bezel card architecture, Framer/Linear dark-canvas discipline, glassmorphism performance rules).

## 6. Workflow
1. **Read Design System Spec**: Consult `docs/UI_UX_DESIGN_SYSTEM.md`.
2. **Token Storage & Compilation**:
   - Store semantic tokens in `themes` (`dark_tokens`, `light_tokens`, `typography_tokens`, `component_tokens` JSON columns).
   - Compile active theme tokens via `ThemeCompilerService` into a cached CSS string injected directly into the `<head>` of `app.blade.php` (`:root` for default/light tokens, `[data-theme="dark"]` or `.dark` for dark tokens, `[dir="rtl"]` for Arabic typography overrides).
3. **Tailwind CSS v4 Binding**:
   - Map all Tailwind utility tokens in `resources/css/app.css` via `@theme inline` to the CSS variables (e.g., `--color-primary: var(--theme-primary);`, `--radius-card: var(--theme-radius-card);`, `--font-heading: var(--theme-font-heading);`).
4. **Component Usage**:
   - Use semantic utility classes (`bg-canvas`, `bg-surface-1`, `bg-surface-2`, `text-ink`, `text-ink-muted`, `border-hairline`, `rounded-card`) across all components.
   - Never use raw hex colors (`#000000`) or arbitrary hardcoded Tailwind palette classes (`bg-slate-900`) for themed surfaces.

## 7. Coding Standards
- **Zero FOUC**: Determine initial theme mode (`dark` / `light` / `system`) in an inline blocking `<script>` in `app.blade.php` before first paint, falling back to the admin-configured `default_mode` (Dark by default).
- **Glassmorphism Guardrail**: Apply `backdrop-filter: blur(var(--theme-glass-blur))` only on navigation bars, floating pills, modals, and isolated overlay cards—never on giant scrolling wrappers.
- **Locale-Aware Typography**: Automatically bind `--theme-font-heading` and `--theme-font-body` to `--theme-font-en-*` when `dir="ltr"` and `--theme-font-ar-*` when `dir="rtl"`, adjusting line-height and letter-spacing (never apply negative letter-spacing to Arabic script!).

## 8. Quality Standards
- Changing any token in the Admin Theme Builder must update the preview canvas in < 16ms via direct CSS variable mutation on the preview root element.
- Both Dark and Light token sets must pass WCAG AA contrast checks, with an automatic contrast warning badge in the Theme Builder UI when the user picks low-contrast combinations.

## 9. Validation Requirements
- Verify that switching between Dark Mode, Light Mode, and Custom Presets updates all surfaces, text, borders, and glass cards without page reload.
- Verify that Arabic (`dir="rtl"`) disables negative letter-spacing and loads the configured Arabic font family.

## 10. Related Project Documents
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/FRONTEND_PLAN.md`
- `docs/LOCALIZATION_PLAN.md`
- `docs/PERFORMANCE_PLAN.md`

## 11. Dependencies on Other Skills
- `tailwindcss-development`
- `tailwind-design-system`
- `high-end-visual-design`
- `ui-ux-pro-max`
- `rtl-ltr-localization`

