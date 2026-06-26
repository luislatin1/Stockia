import React from 'react';

/**
 * Stockia StatCard — KPI tile from the POS summary: an uppercase muted
 * label over a large bold figure, in a bordered white card.
 */
export function StatCard({ label, value, icon, hint, tone = 'default', style }) {
  const accents = {
    default: 'var(--text-strong)',
    success: 'var(--success)',
    warning: 'var(--warning)',
    danger:  'var(--danger)',
    primary: 'var(--primary)',
  };
  return (
    <div
      style={{
        background: 'var(--surface-card)',
        border: '1px solid var(--border-subtle)',
        borderRadius: 'var(--radius-xl)',
        boxShadow: 'var(--shadow-sm)',
        padding: 'var(--pad-card)',
        ...style,
      }}
    >
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <p style={{ margin: 0, fontSize: 'var(--text-xs)', textTransform: 'uppercase', letterSpacing: 'var(--tracking-wide)', color: 'var(--text-muted)' }}>
          {label}
        </p>
        {icon ? <span aria-hidden style={{ fontSize: 'var(--text-lg)' }}>{icon}</span> : null}
      </div>
      <p style={{ margin: '0.25rem 0 0', fontSize: 'var(--text-2xl)', fontWeight: 'var(--weight-bold)', color: accents[tone] || accents.default }}>
        {value}
      </p>
      {hint ? <p style={{ margin: '0.25rem 0 0', fontSize: 'var(--text-xs)', color: 'var(--text-muted)' }}>{hint}</p> : null}
    </div>
  );
}
