import * as React from 'react';

/**
 * Stockia Badge — small tonal status pill.
 */
export interface BadgeProps {
  /** Semantic tone. @default "neutral" */
  tone?: 'neutral' | 'primary' | 'success' | 'warning' | 'danger' | 'info';
  /** Show a leading status dot. @default false */
  dot?: boolean;
  style?: React.CSSProperties;
  children?: React.ReactNode;
}

export function Badge(props: BadgeProps): JSX.Element;
