import * as React from 'react';

export interface Column<Row = any> {
  /** Field key on the row (used when `render` is omitted). */
  key?: string;
  /** Column header label. */
  header: React.ReactNode;
  /** Cell alignment. @default "left" */
  align?: 'left' | 'center' | 'right';
  /** Custom cell renderer. */
  render?: (row: Row) => React.ReactNode;
}

/**
 * Stockia DataTable — bordered card table with uppercase header and
 * hover rows; the standard list view for products, sales, movements.
 *
 * @startingPoint section="Components" subtitle="Data tables / list views" viewport="700x300"
 */
export interface DataTableProps<Row = any> {
  /** Column definitions. */
  columns: Column<Row>[];
  /** Row data. */
  rows: Row[];
  /** Field to use as React key. */
  rowKey?: string;
  /** Empty-state message. @default "No hay registros." */
  empty?: string;
  /** Toolbar content above the table (search, create button). */
  header?: React.ReactNode;
  /** Footer content (pagination). */
  footer?: React.ReactNode;
  style?: React.CSSProperties;
}

export function DataTable<Row = any>(props: DataTableProps<Row>): JSX.Element;
