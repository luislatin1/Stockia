One-sentence: Stockia's primary action control — solid indigo by default, with semantic variants mapped to the real app palette.

```jsx
<Button variant="primary">Guardar</Button>
<Button variant="success" leadingIcon="💵">Cobrar</Button>
<Button variant="secondary">Cancelar</Button>
<Button variant="danger" size="sm">Eliminar</Button>
```

Variants: `primary` (indigo, default), `secondary` (white + border), `success` (emerald — checkout/confirm), `danger` (red — destructive), `warning` (amber — authorize), `ghost` (text-only). Sizes `sm | md | lg`. Pass `block` to stretch full width; `leadingIcon` / `trailingIcon` take an emoji or SVG node.

`IconButton` is the square single-icon sibling for table-row and toolbar actions — always give it a `label`.
