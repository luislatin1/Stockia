import React from 'react';

/**
 * Stockia DataTable — the inventory/sales table: a white rounded-xl card
 * wrapping a full-width table with a gray-50 uppercase header and
 * divided, hover-highlighted rows. Render cells via the `columns` map.
 */
export function DataTable({ columns = [], rows = [], rowKey, empty = 'No hay registros.', header, footer, style }) {
  const align = (a) => (a === 'right' ? 'right' : a === 'center' ? 'center' : 'left');
  return (
    <div
      style={{
        background: 'var(--surface-card)',
        border: '1px solid var(--border-subtle)',
        borderRadius: 'var(--radius-xl)',
        boxShadow: 'var(--shadow-sm)',
        overflow: 'hidden',
        ...style,
      }}
    >
      {header ? (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '1rem', padding: '0.875rem var(--pad-card)', borderBottom: '1px solid var(--border-subtle)' }}>
          {header}
        </div>
      ) : null}
      <div style={{ overflowX: 'auto' }}>
        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 'var(--text-sm)' }}>
          <thead>
            <tr style={{ background: 'var(--surface-sunken)' }}>
              {columns.map((c, i) => (
                <th
                  key={c.key || i}
                  style={{
                    padding: '0.625rem var(--space-3)',
                    textAlign: align(c.align),
                    fontSize: 'var(--text-xs)',
                    fontWeight: 'var(--weight-semibold)',
                    textTransform: 'uppercase',
                    letterSpacing: 'var(--tracking-wide)',
                    color: 'var(--text-muted)',
                    whiteSpace: 'nowrap',
                  }}
                >
                  {c.header}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 ? (
              <tr>
                <td colSpan={columns.length} style={{ padding: 'var(--space-6)', textAlign: 'center', color: 'var(--text-muted)' }}>
                  {empty}
                </td>
              </tr>
            ) : (
              rows.map((row, ri) => (
                <Row key={rowKey ? row[rowKey] : ri} row={row} columns={columns} align={align} last={ri === rows.length - 1} />
              ))
            )}
          </tbody>
        </table>
      </div>
      {footer ? (
        <div style={{ padding: '0.875rem var(--pad-card)', borderTop: '1px solid var(--border-subtle)', background: 'var(--surface-sunken)' }}>
          {footer}
        </div>
      ) : null}
    </div>
  );
}

function Row({ row, columns, align, last }) {
  const [hover, setHover] = React.useState(false);
  return (
    <tr
      onMouseEnter={() => setHover(true)}
      onMouseLeave={() => setHover(false)}
      style={{
        borderTop: '1px solid var(--border-subtle)',
        background: hover ? 'var(--surface-sunken)' : 'transparent',
        transition: 'background var(--duration-fast) var(--ease)',
      }}
    >
      {columns.map((c, ci) => (
        <td
          key={c.key || ci}
          style={{
            padding: '0.625rem var(--space-3)',
            textAlign: align(c.align),
            color: 'var(--text-body)',
            verticalAlign: 'middle',
          }}
        >
          {c.render ? c.render(row) : row[c.key]}
        </td>
      ))}
    </tr>
  );
}
