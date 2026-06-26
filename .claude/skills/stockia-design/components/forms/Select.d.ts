import * as React from 'react';

/**
 * Stockia Select — native dropdown styled to match Input.
 */
export interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  /** Error state (red border). @default false */
  invalid?: boolean;
  /** @default "md" */
  size?: 'sm' | 'md' | 'lg';
  children?: React.ReactNode;
}

export function Select(props: SelectProps): JSX.Element;
