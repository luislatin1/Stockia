// SummaryScreen — Resumen del cajero: KPIs + latest sales + top products.
function SummaryScreen({ salesToday }) {
  const { Card, StatCard, DataTable, Badge } = window.StockiaDesignSystem_235f53;
  const { money } = window.StockiaKit;
  const base = 18;
  const count = base + (salesToday ? salesToday.length : 0);
  const gross = 333.5 + (salesToday ? salesToday.reduce((s, n) => s + n, 0) : 0);

  const latest = [
    ...(salesToday || []).map((n, i) => ({ id: 1041 + i, doc: 'TICKET', total: n, time: 'ahora' })),
    { id: 1040, doc: 'FACTURA', total: 42.30, time: '14:22' },
    { id: 1039, doc: 'TICKET', total: 7.85, time: '14:08' },
    { id: 1038, doc: 'TICKET', total: 15.40, time: '13:51' },
    { id: 1037, doc: 'FACTURA', total: 88.10, time: '13:30' },
  ].slice(0, 6);

  const top = [
    { name: 'Coca-Cola 500ml', qty: 96, sub: 72.00 },
    { name: 'Pan francés (unidad)', qty: 240, sub: 36.00 },
    { name: 'Agua mineral 600ml', qty: 58, sub: 29.00 },
    { name: 'Café molido 250g', qty: 8, sub: 25.60 },
  ];

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
      <Card>
        <h2 style={{ margin: 0, fontSize: 'var(--text-xl)', fontWeight: 700, color: 'var(--text-strong)' }}>Resumen del cajero</h2>
        <p style={{ margin: '4px 0 8px', fontSize: 'var(--text-sm)', color: 'var(--text-muted)' }}>Rendimiento de hoy en el almacén activo.</p>
        <p style={{ margin: 0, fontSize: 'var(--text-sm)', color: 'var(--text-body)' }}>
          Estado de caja: <Badge tone="success" dot>Abierta</Badge> <span style={{ color: 'var(--text-muted)' }}>— Caja 1 (CJ-01)</span>
        </p>
      </Card>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(5, 1fr)', gap: '16px' }}>
        <StatCard label="Ventas" value={count} />
        <StatCard label="Total vendido" value={money(gross)} tone="success" />
        <StatCard label="Efectivo recibido" value={money(gross * 0.82)} />
        <StatCard label="Ticket promedio" value={money(gross / count)} tone="primary" />
        <StatCard label="Unidades vendidas" value={462} />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '24px' }}>
        <DataTable
          header={<strong style={{ color: 'var(--text-strong)' }}>Últimas ventas</strong>}
          columns={[
            { header: 'Venta', render: r => '#' + r.id },
            { header: 'Documento', render: r => <Badge tone={r.doc === 'FACTURA' ? 'primary' : 'neutral'}>{r.doc}</Badge> },
            { header: 'Total', align: 'right', render: r => money(r.total) },
            { key: 'time', header: 'Hora' },
          ]}
          rows={latest}
          rowKey="id"
        />
        <DataTable
          header={<strong style={{ color: 'var(--text-strong)' }}>Top productos</strong>}
          columns={[
            { key: 'name', header: 'Producto' },
            { header: 'Unidades', align: 'right', render: r => r.qty },
            { header: 'Importe', align: 'right', render: r => money(r.sub) },
          ]}
          rows={top}
          rowKey="name"
        />
      </div>
    </div>
  );
}
window.SummaryScreen = SummaryScreen;
