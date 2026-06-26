import React from 'react';

/**
 * Stockia Badge — small status pill. Soft tonal background with a
 * matching foreground, e.g. caja "Abierta" (emerald) / "Cerrada" (amber),
 * stock "Bajo" (red), or a neutral document type.
 */
export function Badge({ tone = 'neutral', dot = false, style, children }) {
  const tones = {
    neutral: { bg: 'var(--gray-100)', fg: 'var(--gray-700)', dot: 'var(--gray-400)' },
    primary: { bg: 'var(--indigo-50)', fg: 'var(--indigo-700)', dot: 'var(--indigo-500)' },
    success: { bg: 'var(--success-soft)', fg: 'var(--success-soft-fg)', dot: 'var(--emerald-500)' },
    warning: { bg: 'var(--warning-soft)', fg: 'var(--warning-soft-fg)', dot: 'var(--amber-500)' },
    danger:  { bg: 'var(--danger-soft)', fg: 'var(--danger-soft-fg)', dot: 'var(--red-500)' },
    info:    { bg: 'var(--blue-50)', fg: 'var(--blue-700)', dot: 'var(--blue-600)' },
  };
  const t = tones[tone] || tones.neutral;
  return (
    <span
      style={{
        display: 'inline-flex',
        alignItems: 'center',
        gap: '0.375rem',
        padding: '0.125rem 0.5rem',
        fontSize: 'var(--text-xs)',
        fontWeight: 'var(--weight-semibold)',
        lineHeight: 1.5,
        color: t.fg,
        background: t.bg,
        borderRadius: 'var(--radius-full)',
        whiteSpace: 'nowrap',
        ...style,
      }}
    >
      {dot ? (
        <span style={{ width: '0.375rem', height: '0.375rem', borderRadius: '50%', background: t.dot }} />
      ) : null}
      {children}
    </span>
  );
}
