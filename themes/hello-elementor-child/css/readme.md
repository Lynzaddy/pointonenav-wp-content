# ✈️ Elementor Child Theme - CSS Loading Order & File Descriptions

This document explains the load order and purpose of each CSS file in the `/css/` folder. This helps maintain clarity, modularity, and consistency across the child theme build.

## 🧾 CSS Loading Order

The order below matches how the files are enqueued in `functions.php`. Load order matters for proper overrides.

1. **Parent Theme CSS (`style.css`)**
    - Comes from the Hello Theme parent.
    - Basic theme resets and default structure.

2. **Child Theme CSS (`style.css`)**
    - Only used for WordPress theme metadata or global overrides.
    - Avoid putting too much logic here—use modular files below instead.

3. **`/css/base.css`**
    - Purpose: Global design system.
    - Defines CSS custom properties (`--color-primary`, `--font-size-lg`, etc.)
    - Also includes base typography (`h1`, `p`, `body`), fonts, and line heights.

4. **`/css/layout.css`**
    - Purpose: Page-wide layout and section rules.
    - Section padding rules (desktop, tablet, mobile)
    - Structure for header, navigation, footer, etc.

5. **`/css/components.css`**
    - Purpose: Reusable UI building blocks.
    - Buttons, cards, sticky headers, alerts, modals, etc.
    - Should use design tokens for consistent styling.

6. **`/css/utilities.css`**
    - Purpose: Utility classes and helpers.
    - Responsive visibility classes, `.inverse` styles, margin/padding helpers, breakpoint-specific adjustments.

## 📝 Notes

- Design tokens (colors, type, component sizes) are taken directly from the Figma file and defined once as CSS variables on `:root` in the `/base` files. Use them throughout instead of typed values.
- The `.inverse` class switches components (text, `.btn-*`, icons, lines) to their light-on-dark versions for dark backgrounds.
- The top-level files (`base.css`, `layout.css`, `components.css`, `utilities.css`) are not `@import` files any more: each partial in the subfolders is enqueued in `functions.php` with a `filemtime` version, so edits bust the browser and Cloudflare cache. Add styles to the files in the subfolders, and register any new partial in the list in `functions.php`.
- Avoid Elementor Global Colors and Fonts for design system styling; use these tokens and classes.
- `layout/retire-with-components.css` holds legacy page styles. Remove it only after the Homepage, Platform and other legacy pages are rebuilt.

---

Maintained by: Matt Olson  
Last updated: October 2026
