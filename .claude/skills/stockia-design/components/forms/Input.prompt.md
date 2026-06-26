One-sentence: The Stockia form primitives — `Field` (label + error wrapper), `Input`, `Select`, and `Checkbox` — all sharing the white surface, gray-300 border, and indigo focus ring.

```jsx
<Field label="Nombre del producto" htmlFor="name" required error={errors.name}>
  <Input id="name" placeholder="Ej: Coca-Cola 500ml" invalid={!!errors.name} />
</Field>

<Field label="Comprobante" htmlFor="doc">
  <Select id="doc">
    <option value="ticket">Ticket</option>
    <option value="factura">Factura</option>
  </Select>
</Field>

<Checkbox label="Recordarme" defaultChecked />
```

`Input` and `Select` take `invalid` (red border) and `size` (`sm | md | lg`). Always pair a control with `Field` for the label/error contract. Labels are sentence-case Spanish.
