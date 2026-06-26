import React from 'react';

/**
 * Stockia Modal — centered dialog over a translucent black backdrop.
 * Mirrors the admin-password / stock-adjust dialogs in the POS.
 */
export function Modal({ open = true, title, description, onClose, footer, maxWidth = '28rem', style, children }) {
  if (!open) return null;
  return (
    <div
      onClick={onClose}
      style={{
        position: 'fixed',
        inset: 0,
        zIndex: 50,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '1rem',
        background: 'var(--overlay)',
      }}
    >
      <div
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        style={{
          width: '100%',
          maxWidth,
          background: 'var(--surface-card)',
          borderRadius: 'var(--radius-lg)',
          boxShadow: 'var(--shadow-xl)',
          overflow: 'hidden',
          fontFamily: 'var(--font-sans)',
          ...style,
        }}
      >
        {(title || description) ? (
          <div style={{ padding: 'var(--pad-card)', borderBottom: footer ? 'none' : undefined }}>
            {title ? <h3 style={{ margin: 0, fontSize: 'var(--text-lg)', fontWeight: 'var(--weight-semibold)', color: 'var(--text-strong)' }}>{title}</h3> : null}
            {description ? <p style={{ margin: '0.25rem 0 0', fontSize: 'var(--text-sm)', color: 'var(--text-muted)' }}>{description}</p> : null}
          </div>
        ) : null}
        <div style={{ padding: (title || description) ? '0 var(--pad-card) var(--pad-card)' : 'var(--pad-card)' }}>
          {children}
        </div>
        {footer ? (
          <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.5rem', padding: '0.875rem var(--pad-card)', background: 'var(--surface-sunken)', borderTop: '1px solid var(--border-subtle)' }}>
            {footer}
          </div>
        ) : null}
      </div>
    </div>
  );
}
