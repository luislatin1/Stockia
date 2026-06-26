// LoginScreen — Stockia auth (guest layout): centered card on gray-100.
function LoginScreen({ onLogin }) {
  const { Card, Field, Input, Checkbox, Button } = window.StockiaDesignSystem_235f53;
  const [email, setEmail] = React.useState('maria.lopez@acme.sv');
  const [pw, setPw] = React.useState('demo1234');
  return (
    <div style={{ minHeight: '100%', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', background: 'var(--surface-app)', padding: '24px' }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '24px' }}>
        <div style={{ height: 48, width: 48, borderRadius: 'var(--radius-lg)', background: 'var(--primary)', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 700, fontSize: 26 }}>S</div>
        <div style={{ fontSize: 30, fontWeight: 700, color: 'var(--text-strong)', letterSpacing: '-0.01em' }}>Stock<span style={{ color: 'var(--primary)' }}>ia</span></div>
      </div>
      <div style={{ width: '100%', maxWidth: 420 }}>
        <Card>
          <form onSubmit={(e) => { e.preventDefault(); onLogin(); }} style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
            <div>
              <h1 style={{ margin: 0, fontSize: 'var(--text-xl)', fontWeight: 600, color: 'var(--text-strong)' }}>Iniciar sesión</h1>
              <p style={{ margin: '4px 0 0', fontSize: 'var(--text-sm)', color: 'var(--text-muted)' }}>Accede a tu punto de venta e inventario.</p>
            </div>
            <Field label="Email" htmlFor="email">
              <Input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="username" />
            </Field>
            <Field label="Contraseña" htmlFor="pw">
              <Input id="pw" type="password" value={pw} onChange={(e) => setPw(e.target.value)} autoComplete="current-password" />
            </Field>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <Checkbox label="Recordarme" defaultChecked />
              <a href="#" onClick={(e) => e.preventDefault()} style={{ fontSize: 'var(--text-sm)', color: 'var(--text-muted)', textDecoration: 'underline' }}>¿Olvidaste tu contraseña?</a>
            </div>
            <Button type="submit" block>Iniciar sesión</Button>
          </form>
        </Card>
        <p style={{ textAlign: 'center', marginTop: '16px', fontSize: 'var(--text-xs)', color: 'var(--text-muted)' }}>Stockia POS · Demo</p>
      </div>
    </div>
  );
}
window.LoginScreen = LoginScreen;
