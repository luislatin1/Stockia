// PosScreen — the signature Punto de Venta: scanner + cart + checkout.
function PosScreen({ onSale }) {
  const { Card, DataTable, Input, Button, Badge, IconButton, Field, Select, Modal, Alert } = window.StockiaDesignSystem_235f53;
  const { catalog, money } = window.StockiaKit;

  const [cart, setCart] = React.useState([
    { ...catalog[0], qty: 6 },
    { ...catalog[3], qty: 2 },
  ]);
  const [code, setCode] = React.useState('');
  const [qty, setQty] = React.useState(1);
  const [error, setError] = React.useState('');
  const [cash, setCash] = React.useState('');
  const [doc, setDoc] = React.useState('ticket');
  const [done, setDone] = React.useState(null);

  const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
  const tax = subtotal * 0.13;
  const total = subtotal + tax;

  function add(code, n) {
    const q = (catalog || []).find(p =>
      p.sku.toLowerCase() === code.toLowerCase() ||
      p.barcode === code ||
      p.name.toLowerCase().includes(code.toLowerCase()));
    if (!q) { setError('Producto no encontrado: ' + code); return; }
    setError('');
    setCart(prev => {
      const ex = prev.find(i => i.id === q.id);
      if (ex) return prev.map(i => i.id === q.id ? { ...i, qty: i.qty + n } : i);
      return [...prev, { ...q, qty: n }];
    });
  }
  function remove(id) { setCart(prev => prev.filter(i => i.id !== id)); }
  function submitScan(e) {
    e.preventDefault();
    if (!code.trim()) return;
    add(code.trim(), Math.max(1, parseInt(qty, 10) || 1));
    setCode(''); setQty(1);
  }
  function checkout() {
    if (cart.length === 0) { setError('Agrega productos al carrito.'); return; }
    const change = (parseFloat(cash) || 0) - total;
    setDone({ total, change, count: cart.length });
    onSale && onSale(total);
  }

  return (
    <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr', gap: '24px', alignItems: 'start' }}>
      {/* LEFT */}
      <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
        <Card>
          <form onSubmit={submitScan}>
            <label style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, color: 'var(--text-body)', marginBottom: '6px' }}>Escáner (código de barras o SKU)</label>
            <div style={{ display: 'flex', gap: '8px' }}>
              <Input value={code} onChange={(e) => setCode(e.target.value)} placeholder="Escribe SKU o código y presiona Enter" autoFocus />
              <Input type="number" min="1" value={qty} onChange={(e) => setQty(e.target.value)} style={{ width: 88, textAlign: 'right' }} />
              <Button type="submit" leadingIcon="＋">Agregar</Button>
            </div>
            {error ? <p style={{ margin: '8px 0 0', fontSize: 'var(--text-sm)', color: 'var(--danger)' }}>{error}</p> : null}
            <p style={{ margin: '8px 0 0', fontSize: 'var(--text-xs)', color: 'var(--text-muted)' }}>
              Prueba: {catalog.slice(0, 5).map(c => c.sku).join(' · ')}
            </p>
          </form>
        </Card>

        <DataTable
          columns={[
            { key: 'name', header: 'Producto' },
            { header: 'Código', render: r => <span style={{ fontFamily: 'var(--font-mono)', fontSize: 'var(--text-xs)', color: 'var(--text-muted)' }}>{r.barcode || r.sku}</span> },
            { header: 'Precio', align: 'right', render: r => money(r.price) },
            { header: 'Cantidad', align: 'right', render: r => r.qty },
            { header: 'Subtotal', align: 'right', render: r => <strong style={{ color: 'var(--text-strong)' }}>{money(r.price * r.qty)}</strong> },
            { header: '', align: 'right', render: r => <IconButton label="Quitar" variant="danger" size="sm" onClick={() => remove(r.id)}>🗑️</IconButton> },
          ]}
          rows={cart}
          rowKey="id"
          empty="Escanea un producto para comenzar la venta."
        />
      </div>

      {/* RIGHT */}
      <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
        <Card>
          <p style={{ margin: 0, fontSize: 'var(--text-xs)', textTransform: 'uppercase', letterSpacing: 'var(--tracking-wide)', color: 'var(--text-muted)' }}>Caja activa</p>
          <p style={{ margin: '6px 0 10px', fontSize: 'var(--text-sm)', color: 'var(--text-body)' }}>
            <strong style={{ color: 'var(--text-strong)' }}>Caja 1</strong> (CJ-01) <Badge tone="success" dot>Abierta</Badge>
          </p>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px' }}>
            <div style={{ background: 'var(--surface-sunken)', borderRadius: 'var(--radius-md)', padding: '8px' }}>
              <p style={{ margin: 0, fontSize: 'var(--text-xs)', color: 'var(--text-muted)' }}>Base apertura</p>
              <p style={{ margin: '2px 0 0', fontWeight: 600, color: 'var(--text-strong)' }}>{money(50)}</p>
            </div>
            <div style={{ background: 'var(--surface-sunken)', borderRadius: 'var(--radius-md)', padding: '8px' }}>
              <p style={{ margin: 0, fontSize: 'var(--text-xs)', color: 'var(--text-muted)' }}>Esperado actual</p>
              <p style={{ margin: '2px 0 0', fontWeight: 600, color: 'var(--text-strong)' }}>{money(50 + total)}</p>
            </div>
          </div>
        </Card>

        <Card>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
            <Row label="Subtotal" value={money(subtotal)} />
            <Row label="IVA 13%" value={money(tax)} />
            <div style={{ height: 1, background: 'var(--border-subtle)', margin: '2px 0' }} />
            <Row label="Total" value={money(total)} strong />
          </div>
        </Card>

        <Card>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            <Field label="Comprobante" htmlFor="doc">
              <Select id="doc" value={doc} onChange={(e) => setDoc(e.target.value)}>
                <option value="ticket">Ticket</option>
                <option value="factura">Factura</option>
              </Select>
            </Field>
            <Field label="Cliente (opcional)" htmlFor="cust">
              <Select id="cust"><option>Consumidor final</option><option>Acme S.A. de C.V.</option></Select>
            </Field>
            <Field label="Efectivo recibido" htmlFor="cash">
              <Input id="cash" type="number" step="0.01" value={cash} onChange={(e) => setCash(e.target.value)} placeholder="0.00" />
            </Field>
            <Button variant="success" block leadingIcon="💵" onClick={checkout}>Cobrar {money(total)}</Button>
          </div>
        </Card>
      </div>

      <Modal
        open={!!done}
        title="Venta registrada"
        description={done ? `Comprobante ${doc.toUpperCase()} · ${done.count} ítems` : ''}
        onClose={() => setDone(null)}
        footer={<><Button variant="secondary" onClick={() => setDone(null)}>Cerrar</Button><Button onClick={() => { setDone(null); setCart([]); setCash(''); }}>Nueva venta</Button></>}
      >
        {done ? (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
            <Alert tone="success" title={`Total cobrado: ${money(done.total)}`}>
              Cambio a entregar: <strong>{money(Math.max(0, done.change))}</strong>
            </Alert>
          </div>
        ) : null}
      </Modal>
    </div>
  );
}

function Row({ label, value, strong }) {
  return (
    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: strong ? 'var(--text-base)' : 'var(--text-sm)', fontWeight: strong ? 600 : 400, color: strong ? 'var(--text-strong)' : 'var(--text-body)' }}>
      <span>{label}</span><span>{value}</span>
    </div>
  );
}
window.PosScreen = PosScreen;
