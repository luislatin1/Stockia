import React from 'react';

/**
 * Stockia Card — the standard surface: white, 1px gray-200 border,
 * rounded-xl, soft shadow. Optional header (title + actions) and footer.
 */
export function Card({ title, subtitle, actions, footer, padding = true, style, children }) {
  return (
    <div
      style={{
        background: 'var(--surface-card)',
        border: '1px solid var(--border-subtle)',
        borderRadius: 'var(--radius-xl)',
        boxShadow: 'var(--shadow-sm)',
        overflow: 'hidden',
        ...style,
      }}
    >
      {(title || actions) ? (
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            gap: '1rem',
            padding: '0.875rem var(--pad-card)',
            borderBottom: '1px solid var(--border-subtle)',
          }}
        >
          <div>
            {title ? <h3 style={{ margin: 0, fontSize: 'var(--text-lg)', fontWeight: 'var(--weight-semibold)', color: 'var(--text-strong)' }}>{title}</h3> : null}
            {subtitle ? <p style={{ margin: '0.125rem 0 0', fontSize: 'var(--text-sm)', color: 'var(--text-muted)' }}>{subtitle}</p> : null}
          </div>
          {actions ? <div style={{ display: 'flex', gap: '0.5rem', flexShrink: 0 }}>{actions}</div> : null}
        </div>
      ) : null}
      <div style={{ padding: padding ? 'var(--pad-card)' : 0 }}>{children}</div>
      {footer ? (
        <div style={{ padding: '0.875rem var(--pad-card)', borderTop: '1px solid var(--border-subtle)', background: 'var(--surface-sunken)' }}>
          {footer}
        </div>
      ) : null}
    </div>
  );
}
