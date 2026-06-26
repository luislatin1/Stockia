import * as React from 'react';

/**
 * Stockia Topbar — white app header with page title and user/warehouse context.
 */
export interface TopbarProps {
  /** Page title. */
  title: string;
  /** Active user's name. */
  user?: string;
  /** Active warehouse label. */
  warehouse?: string;
  /** Active company label. */
  company?: string;
  /** Right-aligned actions (before the context block). */
  actions?: React.ReactNode;
  style?: React.CSSProperties;
}

export function Topbar(props: TopbarProps): JSX.Element;
