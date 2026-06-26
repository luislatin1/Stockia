import React from 'react';

/**
 * Stockia Sidebar — the dark (gray-900) navigation rail. A brand block
 * at top, collapsible uppercase sections of emoji-iconed links, and a
 * footer. Mirrors the Blade sidebar partials exactly.
 */
export function Sidebar({ systemName = 'Stockia POS', logo, sections = [], activeRoute, onNavigate, footer, style }) {
  return (
    <aside
      style={{
        width: 'var(--sidebar-width)',
        flexShrink: 0,
        display: 'flex',
        flexDirection: 'column',
        background: 'var(--surface-sidebar)',
        color: 'var(--text-ondark)',
        fontFamily: 'var(--font-sans)',
        ...style,
      }}
    >
      {/* Brand */}
      <div style={{ padding: 'var(--pad-card)', borderBottom: '1px solid var(--gray-700)' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
          {logo ? (
            <img src={logo} alt="" style={{ height: '2.5rem', width: '2.5rem', borderRadius: 'var(--radius-md)', background: 'var(--white)', objectFit: 'contain', padding: '0.25rem' }} />
          ) : null}
          <div style={{ lineHeight: 1.2 }}>
            <div style={{ fontSize: 'var(--text-xs)', textTransform: 'uppercase', letterSpacing: 'var(--tracking-wide)', color: 'var(--gray-400)' }}>Sistema</div>
            <div style={{ fontSize: 'var(--text-xl)', fontWeight: 'var(--weight-bold)', color: 'var(--white)' }}>{systemName}</div>
          </div>
        </div>
      </div>

      {/* Nav */}
      <nav style={{ flex: 1, overflowY: 'auto', padding: 'var(--pad-card)', display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
        {sections.map((section, si) => (
          <Section key={section.label || si} section={section} activeRoute={activeRoute} onNavigate={onNavigate} />
        ))}
      </nav>

      {/* Footer */}
      <div style={{ padding: 'var(--pad-card)', borderTop: '1px solid var(--gray-700)', fontSize: 'var(--text-xs)', color: 'var(--gray-400)' }}>
        {footer || systemName}
      </div>
    </aside>
  );
}

function Section({ section, activeRoute, onNavigate }) {
  const hasActive = (section.items || []).some((it) => it.route === activeRoute);
  const [open, setOpen] = React.useState(section.open ?? hasActive ?? true);
  return (
    <div style={{ borderBottom: '1px solid rgb(31 41 55 / 0.5)', paddingBottom: '0.5rem' }}>
      <button
        onClick={() => setOpen((o) => !o)}
        style={{
          width: '100%',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          padding: '0.5rem 0',
          background: 'transparent',
          border: 'none',
          cursor: 'pointer',
          fontSize: 'var(--text-xs)',
          textTransform: 'uppercase',
          letterSpacing: 'var(--tracking-wide)',
          color: 'var(--gray-400)',
        }}
      >
        <span>{section.label}</span>
        <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor" style={{ color: 'var(--gray-500)', transform: open ? 'rotate(90deg)' : 'none', transition: 'transform var(--duration-fast) var(--ease)' }}>
          <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clipRule="evenodd" />
        </svg>
      </button>
      {open ? (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.25rem', marginTop: '0.5rem' }}>
          {(section.items || []).map((item, ii) => (
            <NavItem key={item.route || item.label || ii} item={item} active={item.route === activeRoute} onNavigate={onNavigate} />
          ))}
        </div>
      ) : null}
    </div>
  );
}

function NavItem({ item, active, onNavigate }) {
  const [hover, setHover] = React.useState(false);
  const bg = active ? 'var(--gray-800)' : (hover ? 'rgb(31 41 55 / 0.8)' : 'transparent');
  return (
    <a
      href={item.href || '#'}
      onClick={(e) => { if (onNavigate) { e.preventDefault(); onNavigate(item); } }}
      onMouseEnter={() => setHover(true)}
      onMouseLeave={() => setHover(false)}
      style={{
        display: 'flex',
        alignItems: 'center',
        gap: '0.5rem',
        padding: '0.5rem 0.75rem',
        borderRadius: 'var(--radius-md)',
        fontSize: 'var(--text-sm)',
        color: active ? 'var(--white)' : 'var(--text-ondark)',
        background: bg,
        textDecoration: 'none',
        transition: 'background var(--duration-fast) var(--ease)',
      }}
    >
      <span aria-hidden style={{ width: '1.25rem', textAlign: 'center' }}>{item.icon || '•'}</span>
      <span>{item.label}</span>
    </a>
  );
}
