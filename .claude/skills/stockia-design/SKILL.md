---
name: stockia-design
description: Use this skill to generate well-branded interfaces and assets for Stockia POS — a Spanish-language inventory + Point-of-Sale system with El Salvador electronic invoicing (DTE, IVA 13%). Contains essential design guidelines, colors, type, fonts, assets, and UI kit components for prototyping and production.
user-invocable: true
---

Read the `README.md` file within this skill, and explore the other available files (`styles.css` + `tokens/`, the `components/` primitives with their `.prompt.md` files, the `guidelines/` specimen cards, and the `ui_kits/stockia-pos/` recreation).

If creating visual artifacts (slides, mocks, throwaway prototypes, etc.), copy assets out and create static HTML files for the user to view — link `styles.css` for tokens and the brand font (`fonts/figtree-variable.woff2`), and reuse the component patterns. If working on production code, copy assets and read the rules here to become an expert in designing with this brand.

Quick brand checklist:
- **Language:** Spanish, with correct accents. Sentence case for copy; Title Case for nav; UPPERCASE only for small eyebrow labels.
- **Type:** Figtree only (400–700). 14px default UI text.
- **Color:** gray neutral, indigo-600 primary; emerald = success/cash, amber = warning, red = danger/low-stock, blue = links. No gradients, no purple, no decorative color.
- **Surfaces:** white cards, 1px gray-200 border, rounded-xl, soft shadow-sm. Dark gray-900 sidebar.
- **Money:** `$` + 2 decimals; tax is `IVA 13%`.
- **Icons:** emoji (the real nav vocabulary) — keep them.

If the user invokes this skill without other guidance, ask them what they want to build or design, ask a few clarifying questions, and act as an expert designer who outputs HTML artifacts *or* production code, depending on the need.
