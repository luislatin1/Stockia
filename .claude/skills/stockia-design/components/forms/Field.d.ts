import * as React from 'react';

/**
 * Stockia Field — label + control + help/error wrapper for forms.
 */
export interface FieldProps {
  /** Field label text. */
  label?: string;
  /** id of the control this label points at. */
  htmlFor?: string;
  /** Show a red required asterisk. @default false */
  required?: boolean;
  /** Error message (renders red, replaces hint). */
  error?: string;
  /** Helper text below the control. */
  hint?: string;
  style?: React.CSSProperties;
  /** The input/select/etc. */
  children?: React.ReactNode;
}

export function Field(props: FieldProps): JSX.Element;
