import React from 'react';

/**
 * Stockia IconButton — square button for a single emoji/SVG icon.
 * Used for compact table-row actions and toolbar controls.
 */
export function IconButton({
  variant = 'ghost',
  size = 'md',
  label,
  style,
  children,
  ...rest
}) {
  const sizes = { sm: '1.75rem', md: '2.25rem', lg: '2.75rem' };
  const dim = sizes[size] || sizes.md;
  const variants = {
    ghost:     { bg: 'transparent', fg: 'var(--text-muted)', hover: 'var(--gray-100)' },
    secondary: { bg: 'var(--surface-card)', fg: 'var(--text-body)', hover: 'var(--gray-50)' },
    primary:   { bg: 'var(--primary)', fg: 'var(--text-oninvert)', hover: 'var(--primary-hover)' },
    danger:    { bg: 'var(--danger-soft)', fg: 'var(--danger-soft-fg)', hover: 'var(--red-100)' },
  };
  const v = variants[variant] || variants.ghost;
  const [hover, setHover] = React.useState(false);
  return (
    <button
      aria-label={label}
      title={label}
      onMouseEnter={() => setHover(true)}
      onMouseLeave={() => setHover(false)}
      style={{
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        width: dim,
        height: dim,
        fontSize: size === 'sm' ? 'var(--text-sm)' : 'var(--text-base)',
        color: v.fg,
        background: hover ? v.hover : v.bg,
        border: variant === 'secondary' ? '1px solid var(--border-input)' : '1px solid transparent',
        borderRadius: 'var(--radius-md)',
        cursor: 'pointer',
        transition: 'background var(--duration-fast) var(--ease)',
        ...style,
      }}
      {...rest}
    >
      {children}
    </button>
  );
}
