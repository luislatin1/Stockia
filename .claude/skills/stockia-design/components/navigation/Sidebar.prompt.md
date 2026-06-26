One-sentence: Stockia's app chrome — `Sidebar` (the dark gray-900 nav rail with collapsible emoji sections) and `Topbar` (the white header with page title + user/warehouse context).

```jsx
<Sidebar
  systemName="Stockia POS"
  activeRoute="products.index"
  sections={[
    { label: 'Gestión', items: [{ icon: '📊', label: 'Dashboard', route: 'dashboard' }] },
    { label: 'Comercial', items: [
      { icon: '🛒', label: 'Punto de Venta', route: 'ptvpos.pos' },
      { icon: '🗃️', label: 'Ventas', route: 'sales.index' },
    ]},
    { label: 'Inventario', items: [
      { icon: '📦', label: 'Productos', route: 'products.index' },
      { icon: '🏷', label: 'Categorías', route: 'categories.index' },
    ]},
  ]}
  onNavigate={(item) => go(item.route)}
/>

<Topbar title="Productos" user="María López" warehouse="Central" company="Acme S.A." />
```

The Sidebar uses the real emoji icon vocabulary from `config/sidebar.php`. Sections collapse on click and auto-open the one holding `activeRoute`. Pair Sidebar + Topbar to frame any full screen.
