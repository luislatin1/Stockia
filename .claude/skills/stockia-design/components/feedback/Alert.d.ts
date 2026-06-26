import * as React from 'react';

/**
 * Stockia Alert — full-width flash / inline message banner.
 */
export interface AlertProps {
  /** @default "success" */
  tone?: 'success' | 'warning' | 'danger' | 'info';
  /** Bold heading line. */
  title?: string;
  style?: React.CSSProperties;
  /** Body content (text or a list). */
  children?: React.ReactNode;
}

export function Alert(props: AlertProps): JSX.Element;
