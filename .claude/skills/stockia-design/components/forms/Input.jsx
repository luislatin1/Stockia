import React from 'react';

/**
 * Stockia Input — text/number/search field. White, gray-300 border,
 * indigo focus ring. The workhorse of every form and the POS scanner.
 */
export function Input({ invalid = false, size = 'md', style, ...rest }) {
  const [focus, setFocus] = React.useState(false);
  const sizes = {
    sm: { padding: '0.375rem 0.625rem', fontSize: 'var(--text-xs)' },
    md: { padding: '0.5rem 0.75rem', fontSize: 'var(--text-sm)' },
    lg: { padding: '0.625rem 0.875rem', fontSize: 'var(--text-base)' },
  };
  const s = sizes[size] || sizes.md;
  const borderColor = invalid ? 'var(--danger)' : (focus ? 'var(--primary-ring)' : 'var(--border-input)');
  return (
    <input
      onFocus={(e) => { setFocus(true); rest.onFocus && rest.onFocus(e); }}
      onBlur={(e) => { setFocus(false); rest.onBlur && rest.onBlur(e); }}
      style={{
        width: '100%',
        padding: s.padding,
        fontSize: s.fontSize,
        fontFamily: 'var(--font-sans)',
        color: 'var(--text-strong)',
        background: 'var(--surface-card)',
        border: `1px solid ${borderColor}`,
        borderRadius: 'var(--radius-md)',
        boxShadow: focus ? `0 0 0 var(--ring-width) ${invalid ? 'var(--red-100)' : 'var(--indigo-100)'}` : 'var(--shadow-xs)',
        outline: 'none',
        transition: 'border-color var(--duration-fast) var(--ease), box-shadow var(--duration-fast) var(--ease)',
        ...style,
      }}
      {...rest}
    />
  );
}
