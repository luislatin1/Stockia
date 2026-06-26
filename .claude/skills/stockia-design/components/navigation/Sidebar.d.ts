import * as React from 'react';

export interface SidebarItem {
  /** Emoji or icon node. */
  icon?: React.ReactNode;
  /** Link label. */
  label: string;
  /** Route id (compared with `activeRoute` for the active state). */
  route?: string;
  /** Optional href. */
  href?: string;
}

export interface SidebarSection {
  /** Uppercase group heading, e.g. "Comercial". */
  label: string;
  items: SidebarItem[];
  /** Force open/closed initial state (defaults to open if it holds the active item). */
  open?: boolean;
}

/**
 * Stockia Sidebar — dark navigation rail with collapsible emoji-iconed sections.
 */
export interface SidebarProps {
  /** Brand name under the "Sistema" eyebrow. @default "Stockia POS" */
  systemName?: string;
  /** Optional company logo URL. */
  logo?: string;
  sections: SidebarSection[];
  /** route id of the current page. */
  activeRoute?: string;
  /** Click handler; prevents default navigation when provided. */
  onNavigate?: (item: SidebarItem) => void;
  /** Footer content (defaults to systemName). */
  footer?: React.ReactNode;
  style?: React.CSSProperties;
}

export function Sidebar(props: SidebarProps): JSX.Element;
