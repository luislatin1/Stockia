One-sentence: Stockia's surface + list primitives — `Card` (the standard white rounded-xl bordered panel) and `DataTable` (the inventory/sales list table).

```jsx
<Card title="Últimas ventas">
  <p>Contenido…</p>
</Card>

<DataTable
  header={<Button leadingIcon="＋">Nuevo producto</Button>}
  columns={[
    { key: 'name', header: 'Nombre' },
    { key: 'sku', header: 'SKU' },
    { header: 'Precio', align: 'right', render: r => `$${r.price.toFixed(2)}` },
    { header: 'Stock', align: 'right', render: r =>
        r.stock <= r.min ? <Badge tone="danger">{r.stock} ⚠</Badge> : r.stock },
  ]}
  rows={products}
  rowKey="id"
  empty="No hay productos aún."
/>
```

`Card` takes `title` / `subtitle` / `actions` / `footer`; set `padding={false}` to mount a flush table. `DataTable` columns use `render` for custom cells (money, badges, row actions) and `align: 'right'` for figures.
