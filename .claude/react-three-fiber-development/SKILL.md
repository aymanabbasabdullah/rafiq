---
name: react-three-fiber-development
description: >-
  Use this skill when building or modifying 3D WebGL experiences, React Three Fiber
  (@react-three/fiber) and Drei (@react-three/drei) scenes, Spline embeds, the 3D
  Asset Manager, GPU capability detection, or mobile 3D fallbacks.
---

# React Three Fiber & 3D Engine Development Skill

## 1. Skill Name
`react-three-fiber-development`

## 2. Purpose
Guide the architecture, implementation, optimization, and CMS integration of interactive 3D experiences using Three.js, React Three Fiber (`@react-three/fiber`), Drei (`@react-three/drei`), and Spline (`@splinetool/react-spline`). Ensures every 3D scene is configurable via the Admin 3D Asset Manager, lazy-loaded, and equipped with automatic mobile/low-GPU fallbacks.

## 3. When to Use It
- Implementing the `<ThreeDStage>` wrapper, `<R3FSceneRenderer>`, `<SplineSceneRenderer>`, or built-in procedural 3D presets (3D Floating Studio Objects, Interactive 3D Typography/Monogram, Clay/Glass Morphing Geometry, Particle Field, 3D Device Mockup Showcase).
- Building the Admin 3D Asset Manager (`three_d_assets` CRUD, camera/light/interaction inspector, live 3D viewport preview).
- Implementing WebGL context detection, GPU tier adaptive DPR, and fallback image/video rendering.

## 4. When Not to Use It
- Implementing 2D DOM/CSS animations (use `animation-engine-development`).

## 5. Required Knowledge
- `three`, `@react-three/fiber` (React 19 compatible), `@react-three/drei` (`useGLTF`, `Environment`, `Float`, `PresentationControls`, `MeshTransmissionMaterial`, `ContactShadows`, `AdaptiveDpr`, `AdaptiveEvents`, `PerformanceMonitor`, `View`).
- `@splinetool/react-spline` lazy loading and runtime event binding.
- GLTF/GLB Draco & Meshopt compression and WebGL memory disposal (`geometry.dispose()`, `material.dispose()`).

## 6. Workflow
1. **Read 3D Architecture Spec**: Consult `docs/THREE_D_ARCHITECTURE.md`.
2. **Lazy Load Everything**:
   - Every 3D canvas and Three.js bundle must be dynamically imported via `React.lazy()` so routes/sections without 3D incur **0 KB** of Three.js bundle cost.
3. **Evaluate Device & Accessibility Gate (`<ThreeDCapabilityGate>`)**:
   - Before mounting `<Canvas>`, check:
     1. Is `three_d_asset.is_visible` enabled?
     2. Is WebGL2 available?
     3. Does the current device match `device_policy` (`desktop_only`, `all_devices`, `adaptive`)?
     4. Is `prefers-reduced-motion: reduce` active?
   - If 3D rendering is bypassed or while `<Suspense>` is loading the `.glb` / `.splinecode`, display the asset's configured high-resolution fallback poster (`fallback_media_id`) with smooth crossfade.
4. **Apply Database Configuration**:
   - Hydrate camera (`fov`, `position`), lighting (`ambient_intensity`, `directional_position`, `env_preset`), material overrides (`roughness`, `metalness`, `transmission`, `accent_color_sync`), and interaction (`mouse_tilt`, `scroll_rotate`, `float_speed`, `orbit_enabled` with `enableZoom={false}`) from the validated `three_d_assets` JSON configuration.
5. **Optimize Render Loop**:
   - Cap DPR (`dpr={[1, Math.min(window.devicePixelRatio, isMobile ? 1.25 : 2)]}`).
   - Pause the R3F `frameloop` (`frameloop={isInViewport ? 'always' : 'never'}` or `'demand'`) when the section scrolls out of the viewport using `IntersectionObserver`.

## 7. Coding Standards
- Never allow `<OrbitControls>` to hijack vertical page scrolling (`enableZoom={false}`, `enablePan={false}`).
- Synchronize 3D accent lights and emissive materials with the active theme's CSS tokens (`--theme-primary`, `--theme-accent`) when `sync_with_theme` is enabled.
- Always preload Draco decoders locally (`useGLTF.setDecoderPath('/draco/')`) so 3D scenes do not fail under strict CSP or offline/slow external CDNs.

## 8. Quality Standards
- Target `.glb` asset size < 5 MB (warn in Admin uploader if > 5 MB, hard cap at 25 MB).
- Maintain 60fps on desktop and automatic DPR downgrade via `<PerformanceMonitor onDecline={() => degrade(true)} />` on mid-tier hardware.

## 9. Validation Requirements
- Verify fallback image renders when WebGL is disabled or on mobile when `desktop_only` is selected.
- Verify `<Canvas>` pauses `useFrame` ticks when scrolled out of view.
- Verify no WebGL context loss (`Too many active WebGL contexts`) by using a single shared `<Canvas>` with `@react-three/drei` `<View>` portals when multiple 3D slots exist on the same page, or unmounting off-screen canvases.

## 10. Related Project Documents
- `docs/THREE_D_ARCHITECTURE.md`
- `docs/ANIMATION_ENGINE.md`
- `docs/PERFORMANCE_PLAN.md`
- `docs/ADMIN_DASHBOARD.md`

## 11. Dependencies on Other Skills
- `3d-web-experience`
- `premium-3d-website`
- `spline-3d-integration`
- `threejs-skills`
- `fixing-motion-performance`

