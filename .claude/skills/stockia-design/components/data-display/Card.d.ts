import * as React from 'react';

/**
 * Stockia Card — standard white bordered surface with optional header/footer.
 */
export interface CardProps {
  /** Header title. */
  title?: string;
  /** Sub-label under the title. */
  subtitle?: string;
  /** Right-aligned header actions (buttons). */
  actions?: React.ReactNode;
  /** Footer content (e.g. pagination). */
  footer?: React.ReactNode;
  /** Pad the body. Set false for flush tables. @default true */
  padding?: boolean;
  style?: React.CSSProperties;
  children?: React.ReactNode;
}

export function Card(props: CardProps): JSX.Element;
