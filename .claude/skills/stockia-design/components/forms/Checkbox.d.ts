import * as React from 'react';

/**
 * Stockia Checkbox — indigo-filled checkbox with optional label.
 */
export interface CheckboxProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'type'> {
  /** Text shown next to the box. */
  label?: string;
}

export function Checkbox(props: CheckboxProps): JSX.Element;
