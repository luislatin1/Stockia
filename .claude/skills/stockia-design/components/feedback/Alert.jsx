import React from 'react';

/**
 * Stockia Alert — full-width flash message. Mirrors the session
 * success / error / warning banners rendered at the top of every page.
 */
export function Alert({ tone = 'success', title, style, children }) {
  const tones = {
    success: { bg: 'var(--success-soft)', fg: 'var(--success-soft-fg)', border: 'var(--emerald-100)' },
    warning: { bg: 'var(--warning-soft)', fg: 'var(--warning-soft-fg)', border: 'var(--amber-100)' },
    danger:  { bg: 'var(--danger-soft)', fg: 'var(--danger-soft-fg)', border: 'var(--red-100)' },
    info:    { bg: 'var(--blue-50)', fg: 'var(--blue-700)', border: 'var(--blue-100)' },
  };
  const t = tones[tone] || tones.success;
  return (
    <div
      role="alert"
      style={{
        padding: '0.75rem 0.875rem',
        fontSize: 'var(--text-sm)',
        color: t.fg,
        background: t.bg,
        border: `1px solid ${t.border}`,
        borderRadius: 'var(--radius-lg)',
        ...style,
      }}
    >
      {title ? <p style={{ fontWeight: 'var(--weight-semibold)', margin: 0 }}>{title}</p> : null}
      {children ? <div style={{ marginTop: title ? '0.25rem' : 0 }}>{children}</div> : null}
    </div>
  );
}
