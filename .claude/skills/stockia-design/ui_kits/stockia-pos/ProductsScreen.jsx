// ProductsScreen — inventory list with search, low-stock filter, table.
function ProductsScreen() {
  const { DataTable, Input, Button, Badge } = window.StockiaDesignSystem_235f53;
  const { catalog, money } = window.StockiaKit;
  const [q, setQ] = React.useState('');
  const [lowOnly, setLowOnly] = React.useState(false);

  const rows = catalog.filter(p => {
    const matches = !q || (p.name + p.sku + p.barcode).toLowerCase().includes(q.toLowerCase());
    const low = !lowOnly || p.stock <= p.min;
    return matches && low;
  });

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '12px', flexWrap: 'wrap' }}>
        <h2 style={{ margin: 0, fontSize: 'var(--text-2xl)', fontWeight: 700, color: 'var(--text-strong)' }}>Productos</h2>
        <div style={{ display: 'flex', gap: '8px' }}>
          <Button variant={lowOnly ? 'danger' : 'secondary'} onClick={() => setLowOnly(v => !v)}>
            {lowOnly ? 'Mostrando stock bajo' : 'Ver stock bajo'}
          </Button>
          <Button leadingIcon="＋">Nuevo producto</Button>
        </div>
      </div>

      <DataTable
        header={
          <div style={{ display: 'flex', gap: '8px', width: '100%' }}>
            <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Buscar por nombre, SKU o código de barras…" style={{ maxWidth: 380 }} />
            {q ? <Button variant="secondary" onClick={() => setQ('')}>Limpiar</Button> : null}
          </div>
        }
        columns={[
          { key: 'id', header: 'ID' },
          { key: 'name', header: 'Nombre' },
          { header: 'SKU', render: r => <span style={{ fontFamily: 'var(--font-mono)', fontSize: 'var(--text-xs)' }}>{r.sku}</span> },
          { key: 'category', header: 'Categoría', render: r => <Badge tone="neutral">{r.category}</Badge> },
          { header: 'Precio', align: 'right', render: r => money(r.price) },
          { header: 'Stock', align: 'right', render: r => r.stock <= r.min
              ? <Badge tone="danger">{r.stock} ⚠</Badge>
              : <span style={{ color: 'var(--text-strong)', fontWeight: 500 }}>{r.stock}</span> },
          { header: 'Acciones', align: 'right', render: () => (
            <span style={{ display: 'inline-flex', gap: '6px' }}>
              <Button variant="ghost" size="sm">Editar</Button>
              <Button variant="ghost" size="sm">Ajustar</Button>
            </span>
          )},
        ]}
        rows={rows}
        rowKey="id"
        empty="No hay productos que coincidan."
        footer={<span style={{ fontSize: 'var(--text-sm)', color: 'var(--text-muted)' }}>{rows.length} de {catalog.length} productos</span>}
      />
    </div>
  );
}
window.ProductsScreen = ProductsScreen;
