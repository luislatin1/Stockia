import React from 'react';

/**
 * Stockia Select — native dropdown styled to match Input.
 */
export function Select({ invalid = false, size = 'md', children, style, ...rest }) {
  const [focus, setFocus] = React.useState(false);
  const sizes = {
    sm: { padding: '0.375rem 2rem 0.375rem 0.625rem', fontSize: 'var(--text-xs)' },
    md: { padding: '0.5rem 2rem 0.5rem 0.75rem', fontSize: 'var(--text-sm)' },
    lg: { padding: '0.625rem 2.25rem 0.625rem 0.875rem', fontSize: 'var(--text-base)' },
  };
  const s = sizes[size] || sizes.md;
  const borderColor = invalid ? 'var(--danger)' : (focus ? 'var(--primary-ring)' : 'var(--border-input)');
  return (
    <select
      onFocus={() => setFocus(true)}
      onBlur={() => setFocus(false)}
      style={{
        width: '100%',
        appearance: 'none',
        padding: s.padding,
        fontSize: s.fontSize,
        fontFamily: 'var(--font-sans)',
        color: 'var(--text-strong)',
        background: `var(--surface-card) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 20 20' fill='%236b7280'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z' clip-rule='evenodd'/%3E%3C/svg%3E") no-repeat right 0.625rem center`,
        border: `1px solid ${borderColor}`,
        borderRadius: 'var(--radius-md)',
        boxShadow: focus ? `0 0 0 var(--ring-width) var(--indigo-100)` : 'var(--shadow-xs)',
        outline: 'none',
        cursor: 'pointer',
        transition: 'border-color var(--duration-fast) var(--ease), box-shadow var(--duration-fast) var(--ease)',
        ...style,
      }}
      {...rest}
    >
      {children}
    </select>
  );
}
