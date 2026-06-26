# Stockia Design System

A design system distilled from **Stockia POS** — a multi-company, multi-warehouse **inventory + Point-of-Sale** web application for small and mid-size retailers, with built-in **electronic invoicing (DTE)** for El Salvador (Ministerio de Hacienda). The UI is entirely in **Spanish**, currency is **USD** ($, two decimals), and sales tax is the El Salvador **IVA at 13%**.

This system gives design agents the tokens, components, and full-screen UI kits needed to produce on-brand Stockia interfaces, mocks, and prototypes.

## Sources
Built by reading the Stockia codebase (read-only):
- **Local codebase:** `Stockia/` — Laravel 12 + Blade + Tailwind CSS 3 + Alpine.js + ApexCharts.
- **GitHub:** `luislatin1/Stockia` (and related repos under https://github.com/luislatin1 — e.g. `inventory-sys`, `activos-app`). Explore these for deeper context on data models and flows.

Key files studied: `resources/views/layouts/app.blade.php` (app shell), `layouts/partials/sidebar*.blade.php` + `config/sidebar.php` (navigation + icon vocabulary), `resources/views/components/*` (Breeze primitives), `packages/ptv-pos/resources/views/pos.blade.php` (the POS), `resources/views/products/index.blade.php`, `tailwind.config.js` (Figtree).

> Note: Stockia is built on the **Laravel Breeze** starter, so its visual language is the Tailwind default palette with an evolving, cleaner "POS package" treatment (indigo primary, `rounded-xl` bordered cards). This system standardizes on that mature treatment.

---

## Content fundamentals

**Language.** All UI copy is **Spanish (Latin American / Salvadoran)**. Accents are applied inconsistently in the source (`código`/`codigo`, `Categorías`/`Categorias`, `Almacén`/`Almacen`); **this system writes copy *with* correct accents** as the standard.

**Voice & tone.** Plain, operational, transactional — software for cashiers and shop managers, not consumers. Labels are short noun phrases (`Punto de Venta`, `Resumen del cajero`, `Movimientos Caja`, `Total vendido`). Actions are bare imperative verbs (`Cobrar`, `Agregar`, `Buscar`, `Limpiar`, `Autorizar y cobrar`, `Cerrar Caja`). No marketing language, no exclamation, no personality.

**Person.** Mostly impersonal/imperative. Occasional second-person informal *tú* in instructions and confirmations (`Escribe SKU o código y presiona Enter`, `Agrega productos al carrito`, `¿Estás seguro de eliminar este producto?`). Status is stated as fact (`Caja abierta`, `Stock insuficiente en sistema`).

**Casing.** Sentence case for body and field labels. Title Case for navigation items (`Punto de Venta`, `Cambiar Almacén`). UPPERCASE (with wide tracking) only for small eyebrow labels — stat captions (`TOTAL VENDIDO`), table headers (`PRODUCTO`, `PRECIO`), and section headings in the sidebar (`COMERCIAL`, `INVENTARIO`). The legacy Breeze buttons used uppercase tracking-widest labels; **the standard button is sentence case**.

**Numbers & money.** Always `$` prefix, two decimals (`$1,240.00`). Tax line reads `IVA 13%`. Documents are `Ticket` / `Factura`; fiscal types referenced as DTE codes (`01`, `03`, `05`). Times as `HH:mm`, dates as `dd/mm/yyyy`.

**Emoji.** Yes — emoji are a deliberate, load-bearing part of the product: they are the **navigation icon system** (see Iconography). They are not used decoratively in body copy.

**Examples (verbatim from the app):**
- `Escáner (código de barras o SKU)` · `Escribe SKU o código y presiona Enter`
- `No se pudo completar la venta:` → `Stock insuficiente en sistema`
- `Consumidor final` (default customer) · `Efectivo recibido` · `Esperado actual`
- `Ver stock bajo` · `Sin ventas registradas hoy.` · `No hay productos aún.`

---

## Visual foundations

**Typeface.** A single sans — **Figtree** (variable, weights 400–700; declared in `tailwind.config.js`). Self-hosted here at `fonts/figtree-variable.woff2`. Figures and titles go bold (700); card titles and nav-active semibold (600); body is 400 at 14px. The product is information-dense: 14px (`--text-sm`) is the default UI size, 12px (`--text-xs`) for eyebrows/headers, 24px (`--text-2xl`) for page titles and KPI figures.

**Color.** Tailwind default palette. **Gray** is the neutral (page = gray-100, cards = white, body text = gray-700, the sidebar is gray-900). **Indigo-600** is the single primary/action color (buttons, links, active nav, focus). Four semantic colors carry fixed meaning:
- **Emerald** = success / cash / open register (`Cobrar`, `Caja abierta`).
- **Amber** = warning / closed register / authorizations.
- **Red** = destructive / low stock / errors (`Stock bajo ⚠`).
- **Blue** = links and secondary actions.

Soft status uses a tonal pair (e.g. `bg emerald-50 / text emerald-700`); solid status uses the 600 weight. There are no brand gradients, no purple, no decorative color.

**Backgrounds.** Flat, functional. App canvas is solid gray-100; surfaces are solid white. **No** imagery, illustration, texture, pattern, or gradient anywhere in the product chrome. The only "art" in the codebase is the unmodified Laravel welcome splash (not part of the product). The dark sidebar (gray-900) is the one strong field of color.

**Cards & surfaces.** The mature surface is **white, 1px gray-200 border, `rounded-xl` (12px), `shadow-sm`** (a very soft, low elevation). Tables live inside the same shell with `overflow:hidden`. Legacy surfaces used `rounded` + `shadow` with no border; new work uses the bordered xl card. Inset/stat sub-tiles use gray-50 with `rounded-md`.

**Corner radii.** `sm` 4px (inputs, small buttons), `md` 6px (buttons), `lg` 8px (tables, modals), `xl` 12px (cards, panels), `full` (pills, avatars, status dots).

**Borders.** 1px, gray-200 for card/table separation, gray-300 for input outlines, gray-700 inside the dark sidebar. Rows are separated by `divide-y` (1px gray-200), not heavy rules.

**Shadows.** Light and purposeful — `shadow-xs` on inputs/buttons, `shadow-sm` on cards and the top bar, `shadow-xl` on modals. No glow, no colored shadow, no inner shadow system.

**Spacing.** Tailwind 4px base. Table cells pad 12px; cards pad 16px; the page gutter and section gaps are 24px; grids gap 16px. Sidebar is a fixed 16rem (256px).

**Focus & states.** Focus = a 2px **indigo** ring (with a soft indigo-100 halo on inputs). Hover = a small step down/up in tone (buttons darken to the 700; secondary/ghost go to gray-50/indigo-50; table rows go gray-50; sidebar links go gray-800). No scale/shrink on press, no bounce. Transitions are short (150ms) `ease-in-out` — the Tailwind defaults. Modals fade + slightly scale in (Alpine `x-transition`, ~200–300ms).

**Transparency & blur.** Used sparingly: the modal backdrop is `black / 50%`. No frosted glass / backdrop-blur in the product.

**Layout rules.** Fixed left sidebar + sticky white top bar + scrolling main. Content is a single column of cards or a responsive grid (`grid-cols-3` stats, `lg:grid-cols-3` for the POS 2/1 split). Right-aligned numeric columns in tables. Charts via ApexCharts (line/area), kept simple.

---

## Iconography

**Stockia's icon system is emoji.** The navigation (`config/sidebar.php`) labels every item with a single emoji glyph — 📊 Dashboard, 🛒 Punto de Venta, 🗃️ Ventas, 📝 Cotizaciones, 💹 Resumen POS, 💸 Movimientos Caja, 🔓 Abrir Caja, 🔒 Cerrar Caja, 📦 Productos, 🏷 Categorías, 🔄 Movimientos, 🧾 Clientes DTE, 🧩 Panel DTE, 👤 Usuarios, 💱 Monedas, ⚙️ Configuración, 🔁 Cambiar Almacén, 🚪 Cerrar Sesión. See the **Brand → Icon vocabulary** card.

This is faithful, so **recreations should keep emoji for navigation.** Within content, the app also uses the **warning sign ⚠** as a low-stock marker next to a quantity.

Beyond emoji, the codebase contains only a handful of **inline SVGs**: a chevron (`▸`) on collapsible sidebar sections and small arrow glyphs on the (unmodified) Laravel welcome page. There is **no icon font** (no Font Awesome / Lucide / Heroicons) and **no PNG icon set**. There is **no dedicated logo mark** — brand identity is the text wordmark "Stockia" (the app shows an optional per-company uploaded logo, and the default Laravel cube SVG on guest pages). For richer UI work, a thin-stroke set like **Lucide** (CDN) is a reasonable, clearly-flagged *substitution* — but emoji is the authentic default.

---

## Index / manifest

**Root**
- `styles.css` — global entry point (imports only). Consumers link this one file.
- `tokens/` — `fonts.css`, `colors.css`, `typography.css`, `spacing.css`, `effects.css`.
- `fonts/figtree-variable.woff2` — the self-hosted brand typeface.
- `README.md` (this file), `SKILL.md`.

**Components** (`window.StockiaDesignSystem_235f53`) — each dir has `.jsx` + `.d.ts` + `.prompt.md` + a `@dsCard` html:
- `components/buttons/` — **Button**, **IconButton**
- `components/forms/` — **Input**, **Select**, **Checkbox**, **Field**
- `components/feedback/` — **Badge**, **Alert**, **StatCard**
- `components/data-display/` — **Card**, **DataTable**
- `components/navigation/` — **Sidebar**, **Topbar**
- `components/overlays/` — **Modal**

**Foundations** (`guidelines/`) — specimen cards: color (primary / neutral / semantic), type (family / scale), spacing (scale / radii & shadows), brand (wordmark / icon vocabulary).

**UI kit** (`ui_kits/stockia-pos/`) — interactive recreation: login → Resumen POS → Punto de Venta → Productos. See its `README.md`.

The Design System tab renders every `@dsCard`-tagged file, grouped by `group`.
