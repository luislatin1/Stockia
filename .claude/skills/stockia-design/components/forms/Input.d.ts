import * as React from 'react';

/**
 * Stockia Input — text/number/search field with indigo focus ring.
 */
export interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  /** Render in the error state (red border). @default false */
  invalid?: boolean;
  /** @default "md" */
  size?: 'sm' | 'md' | 'lg';
}

export function Input(props: InputProps): JSX.Element;
