One-sentence: Stockia's status & feedback set — `Badge` (tonal status pill), `Alert` (flash banner), and `StatCard` (KPI tile) — all keyed to the semantic color system.

```jsx
<Badge tone="success" dot>Abierta</Badge>
<Badge tone="warning">Stock bajo</Badge>

<Alert tone="danger" title="No se pudo completar la venta:">
  <ul><li>Stock insuficiente en sistema</li></ul>
</Alert>

<StatCard label="Total vendido" value="$1,240.00" hint="Hoy" />
<StatCard label="Stock bajo" value={7} tone="danger" icon="⚠️" />
```

Tones map to the palette: success=emerald, warning=amber, danger=red, info=blue, primary=indigo, neutral=gray. `StatCard` is the building block of the POS summary KPI row.
