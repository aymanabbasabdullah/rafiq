---
name: media-library-management
description: >-
  Use this skill when implementing or modifying the Media Library, Uppy file uploader,
  image optimization pipeline (WebP/AVIF responsive variants), 3D model (.glb/.gltf)
  and video upload handling, SVG sanitization, or file security validation.
---

# Media Library & Asset Pipeline Skill

## 1. Skill Name
`media-library-management`

## 2. Purpose
Provide a secure, high-performance Media Library and asset processing pipeline supporting images (`JPG`, `PNG`, `WebP`, `AVIF`, sanitized `SVG`), videos (`MP4`, `WebM`), 3D models (`GLB`, `GLTF`), Spline scene references, and documents (`PDF`). Handles upload, replacement, deletion, categorization, responsive derivative generation, and polymorphic attachment across all CMS modules.

## 3. When to Use It
- Implementing `MediaLibraryController`, `MediaService`, `ProcessMediaVariantsJob`, and the `media_library` / `project_media` tables.
- Building the Admin Media Library page and the reusable `<MediaPickerModal>` / Uppy upload dropzone.
- Implementing the public `<ResponsiveMedia>` component (`<picture>` with `AVIF` / `WebP` `srcset`, `width`/`height` aspect ratio reservation, blurhash/LQIP placeholder, and lazy loading).
- Implementing contact form attachment handling on a private storage disk.

## 4. When Not to Use It
- Implementing non-media text CRUD operations.

## 5. Required Knowledge
- Laravel `Storage` disks (`public` for portfolio media, `local`/`private` for contact inquiry attachments), MIME + magic-byte validation (`File::types()`), and queued job processing.
- Image manipulation (`intervention/image` v3 or `spatie/image` with GD/Imagick) for generating `sm` (`640w`), `md` (`1024w`), `lg` (`1600w`), and `xl` (`2400w`) WebP/AVIF variants and dominant color / blur placeholders.
- `Uppy` (`@uppy/core`, `@uppy/react`, `@uppy/xhr-upload`) for multi-file drag-and-drop uploads with progress bars and metadata editing.
- SVG security sanitization (`enshrined/svg-sanitize`) and GLB binary header verification.

## 6. Workflow
1. **Read Media & Security Specs**: Consult `docs/ADMIN_DASHBOARD.md`, `docs/SECURITY_PLAN.md`, and `docs/PERFORMANCE_PLAN.md`.
2. **Strict Upload Validation**:
   - Validate file size limits by media type (Images: 15 MB, Videos: 100 MB, 3D `.glb`: 25 MB, PDFs: 20 MB).
   - Validate both extension and actual MIME magic bytes.
   - Generate UUID-based non-guessable filenames (`storage/app/public/media/{year}/{month}/{uuid}.{ext}`) while storing the original human-readable filename and localized `alt_text` (`en`/`ar`) in `media_library`.
3. **Derivative Processing Pipeline**:
   - For raster images (`jpeg`, `png`, `webp`), extract intrinsic `width`, `height`, and aspect ratio immediately, then dispatch `ProcessMediaVariantsJob` to generate WebP/AVIF responsive sizes and a tiny blurred placeholder (`lqip_base64`).
   - For SVGs, run strict server-side XML/SVG sanitization stripping `<script>`, `onload`, `onerror`, and external `xlink:href` before saving.
4. **Media Replacement & Safe Deletion**:
   - Support "Replace Media File" (`POST /admin/media/{media}/replace`) which swaps the underlying file and regenerates variants while keeping the same `media_library.id` so all referencing projects, services, and sections update automatically.
   - Before deleting a media item, check reference counts across `projects`, `services`, `sections`, `three_d_assets`, and `blog_posts`, warning the admin if the asset is currently in use.

## 7. Coding Standards
- Always render portfolio images through `<ResponsiveMedia media={item} sizes="..." priority={isHero} />` which outputs explicit `width` and `height` attributes to guarantee `CLS = 0`.
- Store contact form attachments on the `private` disk (`Storage::disk('local')`) and serve them only to authenticated admins via a signed/authorized download controller.
- Never allow `.php`, `.phar`, `.phtml`, `.exe`, `.sh`, `.js`, or `.html` uploads under any circumstances.

## 8. Quality Standards
- Hero/LCP images must render with `fetchPriority="high"` and `loading="eager"`; all below-the-fold gallery images must use `loading="lazy"` and `decoding="async"`.
- Media Picker Modal must support instant filtering by folder/category (`projects`, `services`, `software`, `3d`, `blog`, `branding`), media type (`image`, `video`, `model_3d`, `document`), and search query.

## 9. Validation Requirements
- Write feature tests using `Storage::fake('public')` and `UploadedFile::fake()` testing valid image/GLB uploads, SVG script stripping, oversized file rejection,invalid MIME rejection, media replacement, and cleanup of disk files on deletion.

## 10. Related Project Documents
- `docs/ADMIN_DASHBOARD.md`
- `docs/DATABASE_SCHEMA.md`
- `docs/SECURITY_PLAN.md`
- `docs/PERFORMANCE_PLAN.md`

## 11. Dependencies on Other Skills
- `laravel-best-practices`
- `laravel-security-audit`
- `portfolio-cms-development`

