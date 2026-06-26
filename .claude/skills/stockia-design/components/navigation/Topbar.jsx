import React from 'react';

/**
 * Stockia Topbar — white app header: page title on the left, the active
 * user / warehouse / company context on the right.
 */
export function Topbar({ title, user, warehouse, company, actions, style }) {
  return (
    <header
      style={{
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        gap: '1rem',
        padding: 'var(--pad-card)',
        background: 'var(--surface-card)',
        boxShadow: 'var(--shadow-sm)',
        fontFamily: 'var(--font-sans)',
        ...style,
      }}
    >
      <h1 style={{ margin: 0, fontSize: 'var(--text-xl)', fontWeight: 'var(--weight-semibold)', color: 'var(--text-strong)' }}>
        {title}
      </h1>
      <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
        {actions}
        {(user || warehouse || company) ? (
          <div style={{ textAlign: 'right', fontSize: 'var(--text-sm)', color: 'var(--text-muted)', lineHeight: 1.4 }}>
            {user ? <div style={{ color: 'var(--text-body)' }}>Usuario activo: <strong style={{ color: 'var(--text-strong)' }}>{user}</strong></div> : null}
            {(warehouse || company) ? (
              <div style={{ fontSize: 'var(--text-xs)' }}>
                {warehouse ? <>Almacén: {warehouse}</> : null}
                {warehouse && company ? ' · ' : ''}
                {company ? <>Empresa: {company}</> : null}
              </div>
            ) : null}
          </div>
        ) : null}
      </div>
    </header>
  );
}
