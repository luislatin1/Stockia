import React from 'react';

/**
 * Stockia Field — label + control + help/error wrapper. Standardizes
 * the "block font-medium text-sm" label and red error text used in forms.
 */
export function Field({ label, htmlFor, required = false, error, hint, style, children }) {
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '0.25rem', ...style }}>
      {label ? (
        <label
          htmlFor={htmlFor}
          style={{
            fontSize: 'var(--text-sm)',
            fontWeight: 'var(--weight-medium)',
            color: 'var(--text-body)',
          }}
        >
          {label}
          {required ? <span style={{ color: 'var(--danger)', marginLeft: '0.125rem' }}>*</span> : null}
        </label>
      ) : null}
      {children}
      {error ? (
        <span style={{ fontSize: 'var(--text-xs)', color: 'var(--danger)' }}>{error}</span>
      ) : hint ? (
        <span style={{ fontSize: 'var(--text-xs)', color: 'var(--text-muted)' }}>{hint}</span>
      ) : null}
    </div>
  );
}
