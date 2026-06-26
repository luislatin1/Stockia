import * as React from 'react';

/**
 * Stockia IconButton — square single-icon button for row/toolbar actions.
 */
export interface IconButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  /** @default "ghost" */
  variant?: 'ghost' | 'secondary' | 'primary' | 'danger';
  /** @default "md" */
  size?: 'sm' | 'md' | 'lg';
  /** Accessible label / tooltip (required — the button has no text). */
  label: string;
  /** The icon node (emoji or SVG). */
  children?: React.ReactNode;
}

export function IconButton(props: IconButtonProps): JSX.Element;
