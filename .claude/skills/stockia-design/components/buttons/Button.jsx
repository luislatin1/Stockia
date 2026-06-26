import React from 'react';

/**
 * Stockia Button — the primary action control.
 * Solid indigo by default; semantic variants map to the app's real palette
 * (emerald = cobrar/confirm, red = destructive, amber = authorize/warn).
 */
export function Button({
  variant = 'primary',
  size = 'md',
  type = 'button',
  disabled = false,
  block = false,
  leadingIcon,
  trailingIcon,
  style,
  children,
  ...rest
}) {
  const sizes = {
    sm: { padding: '0.375rem 0.75rem', fontSize: 'var(--text-xs)' },
    md: { padding: '0.5rem 1rem', fontSize: 'var(--text-sm)' },
    lg: { padding: '0.625rem 1.25rem', fontSize: 'var(--text-base)' },
  };

  const variants = {
    primary:   { bg: 'var(--primary)', fg: 'var(--text-oninvert)', border: 'transparent', hover: 'var(--primary-hover)' },
    success:   { bg: 'var(--success)', fg: 'var(--text-oninvert)', border: 'transparent', hover: 'var(--emerald-700)' },
    danger:    { bg: 'var(--danger)',  fg: 'var(--text-oninvert)', border: 'transparent', hover: 'var(--red-700)' },
    warning:   { bg: 'var(--warning)', fg: 'var(--text-oninvert)', border: 'transparent', hover: 'var(--amber-800)' },
    secondary: { bg: 'var(--surface-card)', fg: 'var(--text-body)', border: 'var(--border-input)', hover: 'var(--gray-50)' },
    ghost:     { bg: 'transparent', fg: 'var(--primary)', border: 'transparent', hover: 'var(--indigo-50)' },
  };

  const v = variants[variant] || variants.primary;
  const s = sizes[size] || sizes.md;
  const [hover, setHover] = React.useState(false);

  return (
    <button
      type={type}
      disabled={disabled}
      onMouseEnter={() => setHover(true)}
      onMouseLeave={() => setHover(false)}
      style={{
        display: block ? 'flex' : 'inline-flex',
        width: block ? '100%' : undefined,
        alignItems: 'center',
        justifyContent: 'center',
        gap: '0.5rem',
        padding: s.padding,
        fontSize: s.fontSize,
        fontFamily: 'var(--font-sans)',
        fontWeight: 'var(--weight-semibold)',
        lineHeight: 1.2,
        color: v.fg,
        background: disabled ? 'var(--gray-200)' : (hover && !disabled ? v.hover : v.bg),
        border: `1px solid ${v.border === 'transparent' ? 'transparent' : v.border}`,
        borderRadius: 'var(--radius-md)',
        boxShadow: variant === 'secondary' ? 'var(--shadow-xs)' : 'none',
        cursor: disabled ? 'not-allowed' : 'pointer',
        opacity: disabled ? 0.6 : 1,
        transition: `background var(--duration-fast) var(--ease)`,
        whiteSpace: 'nowrap',
        ...style,
      }}
      {...rest}
    >
      {leadingIcon ? <span aria-hidden style={{ display: 'inline-flex' }}>{leadingIcon}</span> : null}
      {children}
      {trailingIcon ? <span aria-hidden style={{ display: 'inline-flex' }}>{trailingIcon}</span> : null}
    </button>
  );
}
