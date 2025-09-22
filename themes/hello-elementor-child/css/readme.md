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

3. **`/css/reset.css`**
   - Purpose: Normalize styles across browsers.
   - Includes box-sizing reset, removes default spacing, and neutralizes Elementor Global Styles (colors, fonts, etc.).

4. **`/css/base.css`**
   - Purpose: Global design system.
   - Defines CSS custom properties (`--color-primary`, `--font-size-lg`, etc.)
   - Also includes base typography (`h1`, `p`, `body`), fonts, and line heights.

5. **`/css/layout.css`**
   - Purpose: Page-wide layout and section rules.
   - Section padding rules (desktop, tablet, mobile)
   - Structure for header, navigation, footer, etc.

6. **`/css/components.css`**
   - Purpose: Reusable UI building blocks.
   - Buttons, cards, sticky headers, alerts, modals, etc.
   - Should use design tokens for consistent styling.

7. **`/css/utilities.css`**
   - Purpose: Utility classes and helpers.
   - Responsive visibility classes, `.inverse` styles, margin/padding helpers, breakpoint-specific adjustments.

8. **`/css/elementor.css`**
   - Purpose: Elementor-specific tweaks.
   - Class name cleanup, widget alignment fixes, widget spacing, consistency corrections.

## 📝 Notes

- CSS variables are defined once in `base.css` under `:root`, and used throughout other files.
- The `.inverse` class modifies components (like `.heading-xl`, `.btn`) for dark backgrounds.
- Section-level styles should follow scoped naming: e.g., `.page-hero__title`, `.two-up-wide__subheading`.
- Avoid using Elementor Global Styles; they've been explicitly reset in `reset.css`.

## 📁 Optional Files

- `/js/header-sticky.js` contains scroll-based sticky header logic.

---

Maintained by: Matt Olson  
Last updated: June 2025
