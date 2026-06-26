import * as React from 'react';

/**
 * Stockia Modal — centered dialog over a translucent backdrop.
 */
export interface ModalProps {
  /** Whether the dialog is shown. @default true */
  open?: boolean;
  /** Heading. */
  title?: string;
  /** Sub-text under the heading. */
  description?: string;
  /** Called on backdrop click. */
  onClose?: () => void;
  /** Footer actions (right-aligned button row). */
  footer?: React.ReactNode;
  /** Max width of the dialog. @default "28rem" */
  maxWidth?: string;
  style?: React.CSSProperties;
  children?: React.ReactNode;
}

export function Modal(props: ModalProps): JSX.Element | null;
