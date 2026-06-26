import React from 'react';

/**
 * Stockia Checkbox — indigo-filled box with label. Mirrors the
 * "Remember me" control in the auth screens.
 */
export function Checkbox({ label, checked, defaultChecked, style, ...rest }) {
  return (
    <label style={{ display: 'inline-flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer', ...style }}>
      <input
        type="checkbox"
        checked={checked}
        defaultChecked={defaultChecked}
        style={{
          width: '1rem',
          height: '1rem',
          accentColor: 'var(--primary)',
          borderRadius: 'var(--radius-sm)',
          cursor: 'pointer',
        }}
        {...rest}
      />
      {label ? (
        <span style={{ fontSize: 'var(--text-sm)', color: 'var(--text-body)' }}>{label}</span>
      ) : null}
    </label>
  );
}
