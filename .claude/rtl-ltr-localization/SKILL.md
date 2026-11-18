---
name: rtl-ltr-localization
description: >-
  Use this skill when implementing Arabic (RTL) and English (LTR) bilingual support,
  direction-aware layouts, logical CSS properties, Arabic/English typography pairing,
  localized database content, language switching, or localized SEO (hreflang).
---

# RTL/LTR Localization & Bilingual Architecture Skill

## 1. Skill Name
`rtl-ltr-localization`

## 2. Purpose
Ensure native-grade Arabic (`ar`, `RTL`) and English (`en`, `LTR`) bilingual experiences across the entire Public Website and Admin Dashboard. Prevents naive `text-align: right` hacks by enforcing CSS logical properties, direction-aware motion/3D choreography, Arabic typographic rules, and structured database translations.

## 3. When to Use It
- Creating or styling any React component, layout, navigation bar, form, or section.
- Designing database tables or Eloquent models for translatable content.
- Configuring URL routing (`/{locale}/...`), locale middleware, and language switchers.
- Implementing GSAP/Motion horizontal animations, carousels, or directional icons.
- Generating localized SEO tags (`og:locale`, `hreflang="ar"`, `hreflang="en"`, `hreflang="x-default"`), XML sitemaps, and JSON-LD structured data.

## 4. When Not to Use It
- Internal non-localized system tasks (e.g., raw queue worker supervisor config).

## 5. Required Knowledge
- CSS Logical Properties and Tailwind CSS v4 logical utilities (`ms-*`, `me-*`, `ps-*`, `pe-*`, `start-*`, `end-*`, `text-start`, `text-end`, `border-s-*`, `border-e-*`, `rounded-s-*`, `rounded-e-*`).
- Arabic typography principles (line-height `1.4–1.7`, zero negative letter-spacing `tracking-normal`, cursive glyph joining preservation during text animations).
- Laravel localization middleware, Inertia shared locale props, and bilingual content fallback rules.

## 6. Workflow
1. **Read Localization Plan**: Consult `docs/LOCALIZATION_PLAN.md` and `docs/UI_UX_DESIGN_SYSTEM.md`.
2. **HTML Root Attributes**:
   - Set `<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">` on the server in `app.blade.php` and update it dynamically in `<DirectionProvider>` when switching locales via Inertia.
3. **Direction-Aware Layout & Spacing**:
   - Use Flexbox and CSS Grid (which flip inline axes automatically under `dir="rtl"`).
   - Replace all physical horizontal utilities (`ml-*`, `mr-*`, `pl-*`, `pr-*`, `left-*`, `right-*`, `text-left`, `text-right`, `border-l-*`, `border-r-*`) with logical equivalents (`ms-*`, `me-*`, `ps-*`, `pe-*`, `start-*`, `end-*`, `text-start`, `text-end`, `border-s-*`, `border-e-*`).
4. **Directional Icons & Motion**:
   - Mirror directional icons (arrows, chevrons, back/next buttons, external-link diagonal arrows) in RTL using `rtl:-scale-x-100`. Do NOT mirror non-directional icons (checkmarks, software logos, search magnifiers, clocks).
   - In GSAP / Motion horizontal transitions, read `const { dir, isRTL } = useDirection()` and invert X offsets (`const xOffset = isRTL ? -24 : 24`).
5. **Arabic Typography Safeguards**:
   - In `[dir="rtl"]`, override display negative tracking (`letter-spacing: 0 !important`), increase heading line-height (`1.15–1.25` instead of `0.85–0.95`), and split animated Arabic text by **words or lines only**, never by individual characters.

## 7. Coding Standards
- **No Hardcoded UI Copy**: All static UI labels (buttons, filters, form labels, validation messages, empty states) must come from the `translations` table / dictionary accessed via the `useTranslation()` (`t('nav.projects')`) hook.
- **Database Content Translations**: All dynamic CMS entities (`projects`, `services`, `pages`, `sections`, `experiences`, `education`, `certificates`, `testimonials`, `blog_posts`, `faqs`, `navigation_items`) must resolve localized fields for the active locale with graceful fallback to the default locale if a translation is incomplete.
- **Bidirectional Text Isolation (`<bdi>`)**: Wrap phone numbers, email addresses, URLs, software version numbers (e.g., `Adobe After Effects 2026`), and English brand names inside Arabic sentences with `<bdi>` or `dir="ltr" className="inline-block"` to prevent punctuation and number reordering bugs.

## 8. Quality Standards
- Switching language on any public page (`EN` <-> `AR`) must keep the user on the exact same page/project/service in the target locale without losing scroll context or theme state.
- Both Arabic and English layouts must feel natively art-directed for their script.

## 9. Validation Requirements
- Inspect every UI component in both `dir="ltr"` and `dir="rtl"`.
- Run automated grep checks for forbidden physical horizontal classes (`ml-`, `mr-`, `pl-`, `pr-`, `text-left`, `text-right`) in component reviews unless explicitly justified for physical screen coordinates.
- Write feature tests verifying `/ar/...` and `/en/...` routes, locale persistence, and `hreflang` headers.

## 10. Related Project Documents
- `docs/LOCALIZATION_PLAN.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/ANIMATION_ENGINE.md`
- `docs/DATABASE_SCHEMA.md`

## 11. Dependencies on Other Skills
- `i18n-localization`
- `theme-system-development`
- `animation-engine-development`

