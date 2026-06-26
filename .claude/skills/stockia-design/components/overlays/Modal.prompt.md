One-sentence: Stockia's centered dialog over a translucent black backdrop — used for confirmations and gated actions like the POS admin-password and stock-adjust prompts.

```jsx
<Modal
  title="Clave de administrador"
  description="Se requiere para cambiar precios."
  onClose={close}
  footer={<>
    <Button variant="secondary" onClick={close}>Cancelar</Button>
    <Button onClick={confirm}>Validar</Button>
  </>}
>
  <Field label="Clave admin"><Input type="password" /></Field>
</Modal>
```

Backdrop click calls `onClose`. Put primary/secondary buttons in `footer`. Default width suits a short form; widen with `maxWidth`.
