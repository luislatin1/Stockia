import * as React from 'react';

/**
 * Stockia Button — primary action control with semantic variants.
 */
export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  /** Visual style. Maps to the app's real palette. @default "primary" */
  variant?: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'ghost';
  /** @default "md" */
  size?: 'sm' | 'md' | 'lg';
  /** Stretch to full container width. @default false */
  block?: boolean;
  /** Icon (emoji or SVG node) shown before the label. */
  leadingIcon?: React.ReactNode;
  /** Icon shown after the label. */
  trailingIcon?: React.ReactNode;
  children?: React.ReactNode;
}

export function Button(props: ButtonProps): JSX.Element;
