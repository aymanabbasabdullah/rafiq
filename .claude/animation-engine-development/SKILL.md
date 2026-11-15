---
name: animation-engine-development
description: >-
  Use this skill when building or modifying the declarative Animation Engine,
  Animation Registry, GSAP 3 + ScrollTrigger timelines, Motion for React components,
  Lenis smooth scrolling, or the Admin Animation Studio.
---

# Animation Engine Development Skill

## 1. Skill Name
`animation-engine-development`

## 2. Purpose
Architect and implement a reusable, declarative, schema-validated Animation Engine and Animation Studio. Allows the graphic designer to configure text animations, background effects, interactive hover/cursor physics, scroll-driven choreographies, and page transitions from the Admin Dashboard without writing or executing arbitrary JavaScript.

## 3. When to Use It
- Building the `AnimationRegistry`, `AnimationProvider`, `AnimatedSection`, `AnimatedText`, `BackgroundEffectRenderer`, or `PageTransitionLayout`.
- Writing GSAP 3 + `ScrollTrigger` + `@gsap/react` (`useGSAP`) hooks and presets.
- Implementing `Motion` (`motion/react`) layout animations, micro-interactions, and Inertia route transitions.
- Integrating `Lenis` smooth scrolling synchronized with `gsap.ticker` and `ScrollTrigger.update`.
- Building the Admin Animation Studio (`resources/js/Pages/Admin/AnimationStudio/*`).

## 4. When Not to Use It
- Implementing 3D WebGL geometry or GLSL mesh materials (use `react-three-fiber-development`).

## 5. Required Knowledge
- GSAP 3 (`gsap.timeline`, `ScrollTrigger`, `SplitText` / custom accessible word-char splitter, `quickTo`, `gsap.matchMedia`) and `@gsap/react` (`useGSAP`).
- `motion` (formerly Framer Motion) for declarative React mount/unmount (`AnimatePresence`) and layout (`layoutId`) animations.
- `lenis` (`lenis/react`) smooth scroll synchronization with GSAP ScrollTrigger and Inertia router navigation events.
- Compositor-only animation performance rules (`fixing-motion-performance` skill).

## 6. Workflow
1. **Read Animation Specification**: Consult `docs/ANIMATION_ENGINE.md`.
2. **Register Declarative Preset**:
   - Every animation preset is identified by a registry key (e.g., `text.split_chars_stagger`, `bg.aurora_mesh`, `interaction.magnetic_button`, `scroll.pinned_showcase`, `transition.curtain_wipe`).
   - Define its Zod/JSON configuration schema (`duration`, `delay`, `easing`, `trigger`, `stagger`, `intensity`, `direction`, `enabled_desktop`, `enabled_mobile`, `reduced_motion_fallback`).
3. **Implement Safe Execution**:
   - Map the registry key and validated JSON parameters to a typed React hook or wrapper component using `useGSAP(() => { ... }, { scope: containerRef, dependencies: [config, dir] })`.
   - Never use `eval()`, `new Function()`, or raw script strings from the database.
4. **Enforce Accessibility & Device Adaptation**:
   - Wrap all GSAP timelines in `gsap.matchMedia()` checking `(prefers-reduced-motion: no-preference)` and viewport breakpoints (`(min-width: 768px)`).
   - When `prefers-reduced-motion: reduce` is active, skip spatial displacement/pinning and render the readable final state or a subtle opacity fade (`<= 200ms`).
5. **Direction-Aware Motion (RTL/LTR)**:
   - Multiply horizontal `x` / `xPercent` slide and stagger directions by `dir === 'rtl' ? -1 : 1` so animations flow naturally in both Arabic and English.
   - IMPORTANT: For Arabic (`dir === 'rtl'`), never split text by individual characters (`chars`), as wrapping Arabic letters in separate `<span>` elements breaks cursive letter joining! Always fall back to `words` or `lines` splitting in Arabic.

## 7. Coding Standards
- Animate `transform` (`x`, `y`, `xPercent`, `yPercent`, `scale`, `rotation`) and `opacity` exclusively on large surfaces.
- Use `gsap.quickTo()` for high-frequency pointer/cursor tracking (`pointermove`) and always clean up listeners in the `useGSAP` or `useEffect` return function.
- Pause continuous background animations (`Particles`, `Aurora`, `Waves`, `FloatingShapes`) via `IntersectionObserver` when off-screen and via `document.visibilityState` when the browser tab is hidden.
- Refresh `ScrollTrigger.refresh()` after Inertia page transitions, font loading (`document.fonts.ready`), and lazy image loads.

## 8. Quality Standards
- Maintain 60fps on desktop and 60fps (or smooth 30fps minimum on low-end mobile) with zero layout thrashing (`CLS < 0.05`).
- Limit pinned sections (`pin: true`) to at most 1–2 focal sections per page and disable heavy pinning on small touch screens where configured.

## 9. Validation Requirements
- Test every animation preset in `EN (LTR)` and `AR (RTL)` (verifying Arabic word joining remains intact).
- Test with OS `prefers-reduced-motion: reduce` enabled.
- Verify no memory leaks or orphaned `ScrollTrigger` instances across Inertia page navigations.

## 10. Related Project Documents
- `docs/ANIMATION_ENGINE.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/LOCALIZATION_PLAN.md`
- `docs/PERFORMANCE_PLAN.md`

## 11. Dependencies on Other Skills
- `fixing-motion-performance`
- `ui-motion`
- `high-end-visual-design`
- `rtl-ltr-localization`

