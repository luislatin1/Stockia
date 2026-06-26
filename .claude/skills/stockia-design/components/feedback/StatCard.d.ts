import * as React from 'react';

/**
 * Stockia StatCard — KPI tile (uppercase label + large figure).
 */
export interface StatCardProps {
  /** Uppercase caption, e.g. "Total vendido". */
  label: string;
  /** The figure, e.g. "$1,240.00" or 128. */
  value: React.ReactNode;
  /** Optional trailing emoji/icon. */
  icon?: React.ReactNode;
  /** Small caption under the figure. */
  hint?: string;
  /** Tint the figure for emphasis. @default "default" */
  tone?: 'default' | 'success' | 'warning' | 'danger' | 'primary';
  style?: React.CSSProperties;
}

export function StatCard(props: StatCardProps): JSX.Element;
