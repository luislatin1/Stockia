# Stockia POS — UI kit

An interactive, click-through recreation of the Stockia POS web app, composed entirely from the design-system component primitives (`window.StockiaDesignSystem_235f53`).

## Run
Open `index.html`. It loads React + Babel, the compiled `_ds_bundle.js`, and the screen files.

## Flow
1. **Login** (`LoginScreen.jsx`) — guest layout: centered card on the gray-100 background with the Stockia wordmark. Submit to enter the app.
2. **App shell** — `Sidebar` (dark nav rail, real emoji icon vocabulary, collapsible sections) + `Topbar` (page title + user / warehouse / company context).
3. **Punto de Venta** (`PosScreen.jsx`) — the signature screen. Scanner input (type a SKU like `CC-500` and Enter, or click Agregar), live cart table, caja-activa panel, totals with **IVA 13%**, and a `Cobrar` checkout that opens a confirmation modal with change due.
4. **Resumen POS** (`SummaryScreen.jsx`) — KPI stat row + "Últimas ventas" and "Top productos" tables. Reflects sales completed in the POS this session.
5. **Productos** (`ProductsScreen.jsx`) — inventory list with search, a "Ver stock bajo" filter, low-stock badges, and row actions.

Other sidebar routes render a labelled placeholder — only these surfaces are built in the demo.

## Files
- `data.js` — demo catalog, sidebar config, `money()` helper (`window.StockiaKit`).
- `LoginScreen.jsx`, `PosScreen.jsx`, `ProductsScreen.jsx`, `SummaryScreen.jsx` — each assigns to `window`.
- `index.html` — app shell, routing, auth state.

## Fidelity notes
Screens follow the real Blade views (`packages/ptv-pos/resources/views/pos.blade.php`, `resources/views/products/index.blade.php`, `packages/ptv-pos/resources/views/index.blade.php`). Copy is Spanish; currency is USD (`$`, 2 decimals); tax is the El Salvador IVA at 13%. This is a cosmetic recreation — no real persistence or backend.
