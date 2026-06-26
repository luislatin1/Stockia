/* @ds-bundle: {"format":3,"namespace":"StockiaDesignSystem_235f53","components":[{"name":"Button","sourcePath":"components/buttons/Button.jsx"},{"name":"IconButton","sourcePath":"components/buttons/IconButton.jsx"},{"name":"Card","sourcePath":"components/data-display/Card.jsx"},{"name":"DataTable","sourcePath":"components/data-display/DataTable.jsx"},{"name":"Alert","sourcePath":"components/feedback/Alert.jsx"},{"name":"Badge","sourcePath":"components/feedback/Badge.jsx"},{"name":"StatCard","sourcePath":"components/feedback/StatCard.jsx"},{"name":"Checkbox","sourcePath":"components/forms/Checkbox.jsx"},{"name":"Field","sourcePath":"components/forms/Field.jsx"},{"name":"Input","sourcePath":"components/forms/Input.jsx"},{"name":"Select","sourcePath":"components/forms/Select.jsx"},{"name":"Sidebar","sourcePath":"components/navigation/Sidebar.jsx"},{"name":"Topbar","sourcePath":"components/navigation/Topbar.jsx"},{"name":"Modal","sourcePath":"components/overlays/Modal.jsx"}],"sourceHashes":{"components/buttons/Button.jsx":"b80f2ee1ab3b","components/buttons/IconButton.jsx":"bc583e608e59","components/data-display/Card.jsx":"ef9a9d07e962","components/data-display/DataTable.jsx":"ff711714885e","components/feedback/Alert.jsx":"ef20dad5ddfd","components/feedback/Badge.jsx":"be85199213f4","components/feedback/StatCard.jsx":"b5d010c26fbb","components/forms/Checkbox.jsx":"9992e1137810","components/forms/Field.jsx":"57171584a260","components/forms/Input.jsx":"7191244c9062","components/forms/Select.jsx":"39c797ef64af","components/navigation/Sidebar.jsx":"6bc492d4275e","components/navigation/Topbar.jsx":"67868ca4e0f6","components/overlays/Modal.jsx":"2b2ffca425e5","ui_kits/stockia-pos/LoginScreen.jsx":"0faedc78d7ef","ui_kits/stockia-pos/PosScreen.jsx":"2461629f592f","ui_kits/stockia-pos/ProductsScreen.jsx":"89389630e282","ui_kits/stockia-pos/SummaryScreen.jsx":"ea6b8926d85c","ui_kits/stockia-pos/data.js":"e1a1f947e194"},"inlinedExternals":[],"unexposedExports":[]} */

(() => {

const __ds_ns = (window.StockiaDesignSystem_235f53 = window.StockiaDesignSystem_235f53 || {});

const __ds_scope = {};

(__ds_ns.__errors = __ds_ns.__errors || []);

// components/buttons/Button.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * Stockia Button — the primary action control.
 * Solid indigo by default; semantic variants map to the app's real palette
 * (emerald = cobrar/confirm, red = destructive, amber = authorize/warn).
 */
function Button({
  variant = 'primary',
  size = 'md',
  type = 'button',
  disabled = false,
  block = false,
  leadingIcon,
  trailingIcon,
  style,
  children,
  ...rest
}) {
  const sizes = {
    sm: {
      padding: '0.375rem 0.75rem',
      fontSize: 'var(--text-xs)'
    },
    md: {
      padding: '0.5rem 1rem',
      fontSize: 'var(--text-sm)'
    },
    lg: {
      padding: '0.625rem 1.25rem',
      fontSize: 'var(--text-base)'
    }
  };
  const variants = {
    primary: {
      bg: 'var(--primary)',
      fg: 'var(--text-oninvert)',
      border: 'transparent',
      hover: 'var(--primary-hover)'
    },
    success: {
      bg: 'var(--success)',
      fg: 'var(--text-oninvert)',
      border: 'transparent',
      hover: 'var(--emerald-700)'
    },
    danger: {
      bg: 'var(--danger)',
      fg: 'var(--text-oninvert)',
      border: 'transparent',
      hover: 'var(--red-700)'
    },
    warning: {
      bg: 'var(--warning)',
      fg: 'var(--text-oninvert)',
      border: 'transparent',
      hover: 'var(--amber-800)'
    },
    secondary: {
      bg: 'var(--surface-card)',
      fg: 'var(--text-body)',
      border: 'var(--border-input)',
      hover: 'var(--gray-50)'
    },
    ghost: {
      bg: 'transparent',
      fg: 'var(--primary)',
      border: 'transparent',
      hover: 'var(--indigo-50)'
    }
  };
  const v = variants[variant] || variants.primary;
  const s = sizes[size] || sizes.md;
  const [hover, setHover] = React.useState(false);
  return /*#__PURE__*/React.createElement("button", _extends({
    type: type,
    disabled: disabled,
    onMouseEnter: () => setHover(true),
    onMouseLeave: () => setHover(false),
    style: {
      display: block ? 'flex' : 'inline-flex',
      width: block ? '100%' : undefined,
      alignItems: 'center',
      justifyContent: 'center',
      gap: '0.5rem',
      padding: s.padding,
      fontSize: s.fontSize,
      fontFamily: 'var(--font-sans)',
      fontWeight: 'var(--weight-semibold)',
      lineHeight: 1.2,
      color: v.fg,
      background: disabled ? 'var(--gray-200)' : hover && !disabled ? v.hover : v.bg,
      border: `1px solid ${v.border === 'transparent' ? 'transparent' : v.border}`,
      borderRadius: 'var(--radius-md)',
      boxShadow: variant === 'secondary' ? 'var(--shadow-xs)' : 'none',
      cursor: disabled ? 'not-allowed' : 'pointer',
      opacity: disabled ? 0.6 : 1,
      transition: `background var(--duration-fast) var(--ease)`,
      whiteSpace: 'nowrap',
      ...style
    }
  }, rest), leadingIcon ? /*#__PURE__*/React.createElement("span", {
    "aria-hidden": true,
    style: {
      display: 'inline-flex'
    }
  }, leadingIcon) : null, children, trailingIcon ? /*#__PURE__*/React.createElement("span", {
    "aria-hidden": true,
    style: {
      display: 'inline-flex'
    }
  }, trailingIcon) : null);
}
Object.assign(__ds_scope, { Button });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/buttons/Button.jsx", error: String((e && e.message) || e) }); }

// components/buttons/IconButton.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * Stockia IconButton — square button for a single emoji/SVG icon.
 * Used for compact table-row actions and toolbar controls.
 */
function IconButton({
  variant = 'ghost',
  size = 'md',
  label,
  style,
  children,
  ...rest
}) {
  const sizes = {
    sm: '1.75rem',
    md: '2.25rem',
    lg: '2.75rem'
  };
  const dim = sizes[size] || sizes.md;
  const variants = {
    ghost: {
      bg: 'transparent',
      fg: 'var(--text-muted)',
      hover: 'var(--gray-100)'
    },
    secondary: {
      bg: 'var(--surface-card)',
      fg: 'var(--text-body)',
      hover: 'var(--gray-50)'
    },
    primary: {
      bg: 'var(--primary)',
      fg: 'var(--text-oninvert)',
      hover: 'var(--primary-hover)'
    },
    danger: {
      bg: 'var(--danger-soft)',
      fg: 'var(--danger-soft-fg)',
      hover: 'var(--red-100)'
    }
  };
  const v = variants[variant] || variants.ghost;
  const [hover, setHover] = React.useState(false);
  return /*#__PURE__*/React.createElement("button", _extends({
    "aria-label": label,
    title: label,
    onMouseEnter: () => setHover(true),
    onMouseLeave: () => setHover(false),
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      justifyContent: 'center',
      width: dim,
      height: dim,
      fontSize: size === 'sm' ? 'var(--text-sm)' : 'var(--text-base)',
      color: v.fg,
      background: hover ? v.hover : v.bg,
      border: variant === 'secondary' ? '1px solid var(--border-input)' : '1px solid transparent',
      borderRadius: 'var(--radius-md)',
      cursor: 'pointer',
      transition: 'background var(--duration-fast) var(--ease)',
      ...style
    }
  }, rest), children);
}
Object.assign(__ds_scope, { IconButton });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/buttons/IconButton.jsx", error: String((e && e.message) || e) }); }

// components/data-display/Card.jsx
try { (() => {
/**
 * Stockia Card — the standard surface: white, 1px gray-200 border,
 * rounded-xl, soft shadow. Optional header (title + actions) and footer.
 */
function Card({
  title,
  subtitle,
  actions,
  footer,
  padding = true,
  style,
  children
}) {
  return /*#__PURE__*/React.createElement("div", {
    style: {
      background: 'var(--surface-card)',
      border: '1px solid var(--border-subtle)',
      borderRadius: 'var(--radius-xl)',
      boxShadow: 'var(--shadow-sm)',
      overflow: 'hidden',
      ...style
    }
  }, title || actions ? /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: '1rem',
      padding: '0.875rem var(--pad-card)',
      borderBottom: '1px solid var(--border-subtle)'
    }
  }, /*#__PURE__*/React.createElement("div", null, title ? /*#__PURE__*/React.createElement("h3", {
    style: {
      margin: 0,
      fontSize: 'var(--text-lg)',
      fontWeight: 'var(--weight-semibold)',
      color: 'var(--text-strong)'
    }
  }, title) : null, subtitle ? /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '0.125rem 0 0',
      fontSize: 'var(--text-sm)',
      color: 'var(--text-muted)'
    }
  }, subtitle) : null), actions ? /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: '0.5rem',
      flexShrink: 0
    }
  }, actions) : null) : null, /*#__PURE__*/React.createElement("div", {
    style: {
      padding: padding ? 'var(--pad-card)' : 0
    }
  }, children), footer ? /*#__PURE__*/React.createElement("div", {
    style: {
      padding: '0.875rem var(--pad-card)',
      borderTop: '1px solid var(--border-subtle)',
      background: 'var(--surface-sunken)'
    }
  }, footer) : null);
}
Object.assign(__ds_scope, { Card });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/data-display/Card.jsx", error: String((e && e.message) || e) }); }

// components/data-display/DataTable.jsx
try { (() => {
/**
 * Stockia DataTable — the inventory/sales table: a white rounded-xl card
 * wrapping a full-width table with a gray-50 uppercase header and
 * divided, hover-highlighted rows. Render cells via the `columns` map.
 */
function DataTable({
  columns = [],
  rows = [],
  rowKey,
  empty = 'No hay registros.',
  header,
  footer,
  style
}) {
  const align = a => a === 'right' ? 'right' : a === 'center' ? 'center' : 'left';
  return /*#__PURE__*/React.createElement("div", {
    style: {
      background: 'var(--surface-card)',
      border: '1px solid var(--border-subtle)',
      borderRadius: 'var(--radius-xl)',
      boxShadow: 'var(--shadow-sm)',
      overflow: 'hidden',
      ...style
    }
  }, header ? /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: '1rem',
      padding: '0.875rem var(--pad-card)',
      borderBottom: '1px solid var(--border-subtle)'
    }
  }, header) : null, /*#__PURE__*/React.createElement("div", {
    style: {
      overflowX: 'auto'
    }
  }, /*#__PURE__*/React.createElement("table", {
    style: {
      width: '100%',
      borderCollapse: 'collapse',
      fontSize: 'var(--text-sm)'
    }
  }, /*#__PURE__*/React.createElement("thead", null, /*#__PURE__*/React.createElement("tr", {
    style: {
      background: 'var(--surface-sunken)'
    }
  }, columns.map((c, i) => /*#__PURE__*/React.createElement("th", {
    key: c.key || i,
    style: {
      padding: '0.625rem var(--space-3)',
      textAlign: align(c.align),
      fontSize: 'var(--text-xs)',
      fontWeight: 'var(--weight-semibold)',
      textTransform: 'uppercase',
      letterSpacing: 'var(--tracking-wide)',
      color: 'var(--text-muted)',
      whiteSpace: 'nowrap'
    }
  }, c.header)))), /*#__PURE__*/React.createElement("tbody", null, rows.length === 0 ? /*#__PURE__*/React.createElement("tr", null, /*#__PURE__*/React.createElement("td", {
    colSpan: columns.length,
    style: {
      padding: 'var(--space-6)',
      textAlign: 'center',
      color: 'var(--text-muted)'
    }
  }, empty)) : rows.map((row, ri) => /*#__PURE__*/React.createElement(Row, {
    key: rowKey ? row[rowKey] : ri,
    row: row,
    columns: columns,
    align: align,
    last: ri === rows.length - 1
  }))))), footer ? /*#__PURE__*/React.createElement("div", {
    style: {
      padding: '0.875rem var(--pad-card)',
      borderTop: '1px solid var(--border-subtle)',
      background: 'var(--surface-sunken)'
    }
  }, footer) : null);
}
function Row({
  row,
  columns,
  align,
  last
}) {
  const [hover, setHover] = React.useState(false);
  return /*#__PURE__*/React.createElement("tr", {
    onMouseEnter: () => setHover(true),
    onMouseLeave: () => setHover(false),
    style: {
      borderTop: '1px solid var(--border-subtle)',
      background: hover ? 'var(--surface-sunken)' : 'transparent',
      transition: 'background var(--duration-fast) var(--ease)'
    }
  }, columns.map((c, ci) => /*#__PURE__*/React.createElement("td", {
    key: c.key || ci,
    style: {
      padding: '0.625rem var(--space-3)',
      textAlign: align(c.align),
      color: 'var(--text-body)',
      verticalAlign: 'middle'
    }
  }, c.render ? c.render(row) : row[c.key])));
}
Object.assign(__ds_scope, { DataTable });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/data-display/DataTable.jsx", error: String((e && e.message) || e) }); }

// components/feedback/Alert.jsx
try { (() => {
/**
 * Stockia Alert — full-width flash message. Mirrors the session
 * success / error / warning banners rendered at the top of every page.
 */
function Alert({
  tone = 'success',
  title,
  style,
  children
}) {
  const tones = {
    success: {
      bg: 'var(--success-soft)',
      fg: 'var(--success-soft-fg)',
      border: 'var(--emerald-100)'
    },
    warning: {
      bg: 'var(--warning-soft)',
      fg: 'var(--warning-soft-fg)',
      border: 'var(--amber-100)'
    },
    danger: {
      bg: 'var(--danger-soft)',
      fg: 'var(--danger-soft-fg)',
      border: 'var(--red-100)'
    },
    info: {
      bg: 'var(--blue-50)',
      fg: 'var(--blue-700)',
      border: 'var(--blue-100)'
    }
  };
  const t = tones[tone] || tones.success;
  return /*#__PURE__*/React.createElement("div", {
    role: "alert",
    style: {
      padding: '0.75rem 0.875rem',
      fontSize: 'var(--text-sm)',
      color: t.fg,
      background: t.bg,
      border: `1px solid ${t.border}`,
      borderRadius: 'var(--radius-lg)',
      ...style
    }
  }, title ? /*#__PURE__*/React.createElement("p", {
    style: {
      fontWeight: 'var(--weight-semibold)',
      margin: 0
    }
  }, title) : null, children ? /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: title ? '0.25rem' : 0
    }
  }, children) : null);
}
Object.assign(__ds_scope, { Alert });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/feedback/Alert.jsx", error: String((e && e.message) || e) }); }

// components/feedback/Badge.jsx
try { (() => {
/**
 * Stockia Badge — small status pill. Soft tonal background with a
 * matching foreground, e.g. caja "Abierta" (emerald) / "Cerrada" (amber),
 * stock "Bajo" (red), or a neutral document type.
 */
function Badge({
  tone = 'neutral',
  dot = false,
  style,
  children
}) {
  const tones = {
    neutral: {
      bg: 'var(--gray-100)',
      fg: 'var(--gray-700)',
      dot: 'var(--gray-400)'
    },
    primary: {
      bg: 'var(--indigo-50)',
      fg: 'var(--indigo-700)',
      dot: 'var(--indigo-500)'
    },
    success: {
      bg: 'var(--success-soft)',
      fg: 'var(--success-soft-fg)',
      dot: 'var(--emerald-500)'
    },
    warning: {
      bg: 'var(--warning-soft)',
      fg: 'var(--warning-soft-fg)',
      dot: 'var(--amber-500)'
    },
    danger: {
      bg: 'var(--danger-soft)',
      fg: 'var(--danger-soft-fg)',
      dot: 'var(--red-500)'
    },
    info: {
      bg: 'var(--blue-50)',
      fg: 'var(--blue-700)',
      dot: 'var(--blue-600)'
    }
  };
  const t = tones[tone] || tones.neutral;
  return /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: '0.375rem',
      padding: '0.125rem 0.5rem',
      fontSize: 'var(--text-xs)',
      fontWeight: 'var(--weight-semibold)',
      lineHeight: 1.5,
      color: t.fg,
      background: t.bg,
      borderRadius: 'var(--radius-full)',
      whiteSpace: 'nowrap',
      ...style
    }
  }, dot ? /*#__PURE__*/React.createElement("span", {
    style: {
      width: '0.375rem',
      height: '0.375rem',
      borderRadius: '50%',
      background: t.dot
    }
  }) : null, children);
}
Object.assign(__ds_scope, { Badge });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/feedback/Badge.jsx", error: String((e && e.message) || e) }); }

// components/feedback/StatCard.jsx
try { (() => {
/**
 * Stockia StatCard — KPI tile from the POS summary: an uppercase muted
 * label over a large bold figure, in a bordered white card.
 */
function StatCard({
  label,
  value,
  icon,
  hint,
  tone = 'default',
  style
}) {
  const accents = {
    default: 'var(--text-strong)',
    success: 'var(--success)',
    warning: 'var(--warning)',
    danger: 'var(--danger)',
    primary: 'var(--primary)'
  };
  return /*#__PURE__*/React.createElement("div", {
    style: {
      background: 'var(--surface-card)',
      border: '1px solid var(--border-subtle)',
      borderRadius: 'var(--radius-xl)',
      boxShadow: 'var(--shadow-sm)',
      padding: 'var(--pad-card)',
      ...style
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between'
    }
  }, /*#__PURE__*/React.createElement("p", {
    style: {
      margin: 0,
      fontSize: 'var(--text-xs)',
      textTransform: 'uppercase',
      letterSpacing: 'var(--tracking-wide)',
      color: 'var(--text-muted)'
    }
  }, label), icon ? /*#__PURE__*/React.createElement("span", {
    "aria-hidden": true,
    style: {
      fontSize: 'var(--text-lg)'
    }
  }, icon) : null), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '0.25rem 0 0',
      fontSize: 'var(--text-2xl)',
      fontWeight: 'var(--weight-bold)',
      color: accents[tone] || accents.default
    }
  }, value), hint ? /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '0.25rem 0 0',
      fontSize: 'var(--text-xs)',
      color: 'var(--text-muted)'
    }
  }, hint) : null);
}
Object.assign(__ds_scope, { StatCard });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/feedback/StatCard.jsx", error: String((e && e.message) || e) }); }

// components/forms/Checkbox.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * Stockia Checkbox — indigo-filled box with label. Mirrors the
 * "Remember me" control in the auth screens.
 */
function Checkbox({
  label,
  checked,
  defaultChecked,
  style,
  ...rest
}) {
  return /*#__PURE__*/React.createElement("label", {
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: '0.5rem',
      cursor: 'pointer',
      ...style
    }
  }, /*#__PURE__*/React.createElement("input", _extends({
    type: "checkbox",
    checked: checked,
    defaultChecked: defaultChecked,
    style: {
      width: '1rem',
      height: '1rem',
      accentColor: 'var(--primary)',
      borderRadius: 'var(--radius-sm)',
      cursor: 'pointer'
    }
  }, rest)), label ? /*#__PURE__*/React.createElement("span", {
    style: {
      fontSize: 'var(--text-sm)',
      color: 'var(--text-body)'
    }
  }, label) : null);
}
Object.assign(__ds_scope, { Checkbox });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Checkbox.jsx", error: String((e && e.message) || e) }); }

// components/forms/Field.jsx
try { (() => {
/**
 * Stockia Field — label + control + help/error wrapper. Standardizes
 * the "block font-medium text-sm" label and red error text used in forms.
 */
function Field({
  label,
  htmlFor,
  required = false,
  error,
  hint,
  style,
  children
}) {
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: '0.25rem',
      ...style
    }
  }, label ? /*#__PURE__*/React.createElement("label", {
    htmlFor: htmlFor,
    style: {
      fontSize: 'var(--text-sm)',
      fontWeight: 'var(--weight-medium)',
      color: 'var(--text-body)'
    }
  }, label, required ? /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--danger)',
      marginLeft: '0.125rem'
    }
  }, "*") : null) : null, children, error ? /*#__PURE__*/React.createElement("span", {
    style: {
      fontSize: 'var(--text-xs)',
      color: 'var(--danger)'
    }
  }, error) : hint ? /*#__PURE__*/React.createElement("span", {
    style: {
      fontSize: 'var(--text-xs)',
      color: 'var(--text-muted)'
    }
  }, hint) : null);
}
Object.assign(__ds_scope, { Field });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Field.jsx", error: String((e && e.message) || e) }); }

// components/forms/Input.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * Stockia Input — text/number/search field. White, gray-300 border,
 * indigo focus ring. The workhorse of every form and the POS scanner.
 */
function Input({
  invalid = false,
  size = 'md',
  style,
  ...rest
}) {
  const [focus, setFocus] = React.useState(false);
  const sizes = {
    sm: {
      padding: '0.375rem 0.625rem',
      fontSize: 'var(--text-xs)'
    },
    md: {
      padding: '0.5rem 0.75rem',
      fontSize: 'var(--text-sm)'
    },
    lg: {
      padding: '0.625rem 0.875rem',
      fontSize: 'var(--text-base)'
    }
  };
  const s = sizes[size] || sizes.md;
  const borderColor = invalid ? 'var(--danger)' : focus ? 'var(--primary-ring)' : 'var(--border-input)';
  return /*#__PURE__*/React.createElement("input", _extends({
    onFocus: e => {
      setFocus(true);
      rest.onFocus && rest.onFocus(e);
    },
    onBlur: e => {
      setFocus(false);
      rest.onBlur && rest.onBlur(e);
    },
    style: {
      width: '100%',
      padding: s.padding,
      fontSize: s.fontSize,
      fontFamily: 'var(--font-sans)',
      color: 'var(--text-strong)',
      background: 'var(--surface-card)',
      border: `1px solid ${borderColor}`,
      borderRadius: 'var(--radius-md)',
      boxShadow: focus ? `0 0 0 var(--ring-width) ${invalid ? 'var(--red-100)' : 'var(--indigo-100)'}` : 'var(--shadow-xs)',
      outline: 'none',
      transition: 'border-color var(--duration-fast) var(--ease), box-shadow var(--duration-fast) var(--ease)',
      ...style
    }
  }, rest));
}
Object.assign(__ds_scope, { Input });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Input.jsx", error: String((e && e.message) || e) }); }

// components/forms/Select.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * Stockia Select — native dropdown styled to match Input.
 */
function Select({
  invalid = false,
  size = 'md',
  children,
  style,
  ...rest
}) {
  const [focus, setFocus] = React.useState(false);
  const sizes = {
    sm: {
      padding: '0.375rem 2rem 0.375rem 0.625rem',
      fontSize: 'var(--text-xs)'
    },
    md: {
      padding: '0.5rem 2rem 0.5rem 0.75rem',
      fontSize: 'var(--text-sm)'
    },
    lg: {
      padding: '0.625rem 2.25rem 0.625rem 0.875rem',
      fontSize: 'var(--text-base)'
    }
  };
  const s = sizes[size] || sizes.md;
  const borderColor = invalid ? 'var(--danger)' : focus ? 'var(--primary-ring)' : 'var(--border-input)';
  return /*#__PURE__*/React.createElement("select", _extends({
    onFocus: () => setFocus(true),
    onBlur: () => setFocus(false),
    style: {
      width: '100%',
      appearance: 'none',
      padding: s.padding,
      fontSize: s.fontSize,
      fontFamily: 'var(--font-sans)',
      color: 'var(--text-strong)',
      background: `var(--surface-card) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 20 20' fill='%236b7280'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z' clip-rule='evenodd'/%3E%3C/svg%3E") no-repeat right 0.625rem center`,
      border: `1px solid ${borderColor}`,
      borderRadius: 'var(--radius-md)',
      boxShadow: focus ? `0 0 0 var(--ring-width) var(--indigo-100)` : 'var(--shadow-xs)',
      outline: 'none',
      cursor: 'pointer',
      transition: 'border-color var(--duration-fast) var(--ease), box-shadow var(--duration-fast) var(--ease)',
      ...style
    }
  }, rest), children);
}
Object.assign(__ds_scope, { Select });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Select.jsx", error: String((e && e.message) || e) }); }

// components/navigation/Sidebar.jsx
try { (() => {
/**
 * Stockia Sidebar — the dark (gray-900) navigation rail. A brand block
 * at top, collapsible uppercase sections of emoji-iconed links, and a
 * footer. Mirrors the Blade sidebar partials exactly.
 */
function Sidebar({
  systemName = 'Stockia POS',
  logo,
  sections = [],
  activeRoute,
  onNavigate,
  footer,
  style
}) {
  return /*#__PURE__*/React.createElement("aside", {
    style: {
      width: 'var(--sidebar-width)',
      flexShrink: 0,
      display: 'flex',
      flexDirection: 'column',
      background: 'var(--surface-sidebar)',
      color: 'var(--text-ondark)',
      fontFamily: 'var(--font-sans)',
      ...style
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      padding: 'var(--pad-card)',
      borderBottom: '1px solid var(--gray-700)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: '0.75rem'
    }
  }, logo ? /*#__PURE__*/React.createElement("img", {
    src: logo,
    alt: "",
    style: {
      height: '2.5rem',
      width: '2.5rem',
      borderRadius: 'var(--radius-md)',
      background: 'var(--white)',
      objectFit: 'contain',
      padding: '0.25rem'
    }
  }) : null, /*#__PURE__*/React.createElement("div", {
    style: {
      lineHeight: 1.2
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 'var(--text-xs)',
      textTransform: 'uppercase',
      letterSpacing: 'var(--tracking-wide)',
      color: 'var(--gray-400)'
    }
  }, "Sistema"), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 'var(--text-xl)',
      fontWeight: 'var(--weight-bold)',
      color: 'var(--white)'
    }
  }, systemName)))), /*#__PURE__*/React.createElement("nav", {
    style: {
      flex: 1,
      overflowY: 'auto',
      padding: 'var(--pad-card)',
      display: 'flex',
      flexDirection: 'column',
      gap: '0.75rem'
    }
  }, sections.map((section, si) => /*#__PURE__*/React.createElement(Section, {
    key: section.label || si,
    section: section,
    activeRoute: activeRoute,
    onNavigate: onNavigate
  }))), /*#__PURE__*/React.createElement("div", {
    style: {
      padding: 'var(--pad-card)',
      borderTop: '1px solid var(--gray-700)',
      fontSize: 'var(--text-xs)',
      color: 'var(--gray-400)'
    }
  }, footer || systemName));
}
function Section({
  section,
  activeRoute,
  onNavigate
}) {
  const hasActive = (section.items || []).some(it => it.route === activeRoute);
  const [open, setOpen] = React.useState(section.open ?? hasActive ?? true);
  return /*#__PURE__*/React.createElement("div", {
    style: {
      borderBottom: '1px solid rgb(31 41 55 / 0.5)',
      paddingBottom: '0.5rem'
    }
  }, /*#__PURE__*/React.createElement("button", {
    onClick: () => setOpen(o => !o),
    style: {
      width: '100%',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      padding: '0.5rem 0',
      background: 'transparent',
      border: 'none',
      cursor: 'pointer',
      fontSize: 'var(--text-xs)',
      textTransform: 'uppercase',
      letterSpacing: 'var(--tracking-wide)',
      color: 'var(--gray-400)'
    }
  }, /*#__PURE__*/React.createElement("span", null, section.label), /*#__PURE__*/React.createElement("svg", {
    width: "12",
    height: "12",
    viewBox: "0 0 20 20",
    fill: "currentColor",
    style: {
      color: 'var(--gray-500)',
      transform: open ? 'rotate(90deg)' : 'none',
      transition: 'transform var(--duration-fast) var(--ease)'
    }
  }, /*#__PURE__*/React.createElement("path", {
    fillRule: "evenodd",
    d: "M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z",
    clipRule: "evenodd"
  }))), open ? /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: '0.25rem',
      marginTop: '0.5rem'
    }
  }, (section.items || []).map((item, ii) => /*#__PURE__*/React.createElement(NavItem, {
    key: item.route || item.label || ii,
    item: item,
    active: item.route === activeRoute,
    onNavigate: onNavigate
  }))) : null);
}
function NavItem({
  item,
  active,
  onNavigate
}) {
  const [hover, setHover] = React.useState(false);
  const bg = active ? 'var(--gray-800)' : hover ? 'rgb(31 41 55 / 0.8)' : 'transparent';
  return /*#__PURE__*/React.createElement("a", {
    href: item.href || '#',
    onClick: e => {
      if (onNavigate) {
        e.preventDefault();
        onNavigate(item);
      }
    },
    onMouseEnter: () => setHover(true),
    onMouseLeave: () => setHover(false),
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: '0.5rem',
      padding: '0.5rem 0.75rem',
      borderRadius: 'var(--radius-md)',
      fontSize: 'var(--text-sm)',
      color: active ? 'var(--white)' : 'var(--text-ondark)',
      background: bg,
      textDecoration: 'none',
      transition: 'background var(--duration-fast) var(--ease)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    "aria-hidden": true,
    style: {
      width: '1.25rem',
      textAlign: 'center'
    }
  }, item.icon || '•'), /*#__PURE__*/React.createElement("span", null, item.label));
}
Object.assign(__ds_scope, { Sidebar });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/navigation/Sidebar.jsx", error: String((e && e.message) || e) }); }

// components/navigation/Topbar.jsx
try { (() => {
/**
 * Stockia Topbar — white app header: page title on the left, the active
 * user / warehouse / company context on the right.
 */
function Topbar({
  title,
  user,
  warehouse,
  company,
  actions,
  style
}) {
  return /*#__PURE__*/React.createElement("header", {
    style: {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: '1rem',
      padding: 'var(--pad-card)',
      background: 'var(--surface-card)',
      boxShadow: 'var(--shadow-sm)',
      fontFamily: 'var(--font-sans)',
      ...style
    }
  }, /*#__PURE__*/React.createElement("h1", {
    style: {
      margin: 0,
      fontSize: 'var(--text-xl)',
      fontWeight: 'var(--weight-semibold)',
      color: 'var(--text-strong)'
    }
  }, title), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: '1rem'
    }
  }, actions, user || warehouse || company ? /*#__PURE__*/React.createElement("div", {
    style: {
      textAlign: 'right',
      fontSize: 'var(--text-sm)',
      color: 'var(--text-muted)',
      lineHeight: 1.4
    }
  }, user ? /*#__PURE__*/React.createElement("div", {
    style: {
      color: 'var(--text-body)'
    }
  }, "Usuario activo: ", /*#__PURE__*/React.createElement("strong", {
    style: {
      color: 'var(--text-strong)'
    }
  }, user)) : null, warehouse || company ? /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 'var(--text-xs)'
    }
  }, warehouse ? /*#__PURE__*/React.createElement(React.Fragment, null, "Almac\xE9n: ", warehouse) : null, warehouse && company ? ' · ' : '', company ? /*#__PURE__*/React.createElement(React.Fragment, null, "Empresa: ", company) : null) : null) : null));
}
Object.assign(__ds_scope, { Topbar });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/navigation/Topbar.jsx", error: String((e && e.message) || e) }); }

// components/overlays/Modal.jsx
try { (() => {
/**
 * Stockia Modal — centered dialog over a translucent black backdrop.
 * Mirrors the admin-password / stock-adjust dialogs in the POS.
 */
function Modal({
  open = true,
  title,
  description,
  onClose,
  footer,
  maxWidth = '28rem',
  style,
  children
}) {
  if (!open) return null;
  return /*#__PURE__*/React.createElement("div", {
    onClick: onClose,
    style: {
      position: 'fixed',
      inset: 0,
      zIndex: 50,
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      padding: '1rem',
      background: 'var(--overlay)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    onClick: e => e.stopPropagation(),
    role: "dialog",
    "aria-modal": "true",
    style: {
      width: '100%',
      maxWidth,
      background: 'var(--surface-card)',
      borderRadius: 'var(--radius-lg)',
      boxShadow: 'var(--shadow-xl)',
      overflow: 'hidden',
      fontFamily: 'var(--font-sans)',
      ...style
    }
  }, title || description ? /*#__PURE__*/React.createElement("div", {
    style: {
      padding: 'var(--pad-card)',
      borderBottom: footer ? 'none' : undefined
    }
  }, title ? /*#__PURE__*/React.createElement("h3", {
    style: {
      margin: 0,
      fontSize: 'var(--text-lg)',
      fontWeight: 'var(--weight-semibold)',
      color: 'var(--text-strong)'
    }
  }, title) : null, description ? /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '0.25rem 0 0',
      fontSize: 'var(--text-sm)',
      color: 'var(--text-muted)'
    }
  }, description) : null) : null, /*#__PURE__*/React.createElement("div", {
    style: {
      padding: title || description ? '0 var(--pad-card) var(--pad-card)' : 'var(--pad-card)'
    }
  }, children), footer ? /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'flex-end',
      gap: '0.5rem',
      padding: '0.875rem var(--pad-card)',
      background: 'var(--surface-sunken)',
      borderTop: '1px solid var(--border-subtle)'
    }
  }, footer) : null));
}
Object.assign(__ds_scope, { Modal });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/overlays/Modal.jsx", error: String((e && e.message) || e) }); }

// ui_kits/stockia-pos/LoginScreen.jsx
try { (() => {
// LoginScreen — Stockia auth (guest layout): centered card on gray-100.
function LoginScreen({
  onLogin
}) {
  const {
    Card,
    Field,
    Input,
    Checkbox,
    Button
  } = window.StockiaDesignSystem_235f53;
  const [email, setEmail] = React.useState('maria.lopez@acme.sv');
  const [pw, setPw] = React.useState('demo1234');
  return /*#__PURE__*/React.createElement("div", {
    style: {
      minHeight: '100%',
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      justifyContent: 'center',
      background: 'var(--surface-app)',
      padding: '24px'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: '12px',
      marginBottom: '24px'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      height: 48,
      width: 48,
      borderRadius: 'var(--radius-lg)',
      background: 'var(--primary)',
      color: '#fff',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      fontWeight: 700,
      fontSize: 26
    }
  }, "S"), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 30,
      fontWeight: 700,
      color: 'var(--text-strong)',
      letterSpacing: '-0.01em'
    }
  }, "Stock", /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--primary)'
    }
  }, "ia"))), /*#__PURE__*/React.createElement("div", {
    style: {
      width: '100%',
      maxWidth: 420
    }
  }, /*#__PURE__*/React.createElement(Card, null, /*#__PURE__*/React.createElement("form", {
    onSubmit: e => {
      e.preventDefault();
      onLogin();
    },
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: '16px'
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("h1", {
    style: {
      margin: 0,
      fontSize: 'var(--text-xl)',
      fontWeight: 600,
      color: 'var(--text-strong)'
    }
  }, "Iniciar sesi\xF3n"), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '4px 0 0',
      fontSize: 'var(--text-sm)',
      color: 'var(--text-muted)'
    }
  }, "Accede a tu punto de venta e inventario.")), /*#__PURE__*/React.createElement(Field, {
    label: "Email",
    htmlFor: "email"
  }, /*#__PURE__*/React.createElement(Input, {
    id: "email",
    type: "email",
    value: email,
    onChange: e => setEmail(e.target.value),
    autoComplete: "username"
  })), /*#__PURE__*/React.createElement(Field, {
    label: "Contrase\xF1a",
    htmlFor: "pw"
  }, /*#__PURE__*/React.createElement(Input, {
    id: "pw",
    type: "password",
    value: pw,
    onChange: e => setPw(e.target.value),
    autoComplete: "current-password"
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between'
    }
  }, /*#__PURE__*/React.createElement(Checkbox, {
    label: "Recordarme",
    defaultChecked: true
  }), /*#__PURE__*/React.createElement("a", {
    href: "#",
    onClick: e => e.preventDefault(),
    style: {
      fontSize: 'var(--text-sm)',
      color: 'var(--text-muted)',
      textDecoration: 'underline'
    }
  }, "\xBFOlvidaste tu contrase\xF1a?")), /*#__PURE__*/React.createElement(Button, {
    type: "submit",
    block: true
  }, "Iniciar sesi\xF3n"))), /*#__PURE__*/React.createElement("p", {
    style: {
      textAlign: 'center',
      marginTop: '16px',
      fontSize: 'var(--text-xs)',
      color: 'var(--text-muted)'
    }
  }, "Stockia POS \xB7 Demo")));
}
window.LoginScreen = LoginScreen;
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/stockia-pos/LoginScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/stockia-pos/PosScreen.jsx
try { (() => {
// PosScreen — the signature Punto de Venta: scanner + cart + checkout.
function PosScreen({
  onSale
}) {
  const {
    Card,
    DataTable,
    Input,
    Button,
    Badge,
    IconButton,
    Field,
    Select,
    Modal,
    Alert
  } = window.StockiaDesignSystem_235f53;
  const {
    catalog,
    money
  } = window.StockiaKit;
  const [cart, setCart] = React.useState([{
    ...catalog[0],
    qty: 6
  }, {
    ...catalog[3],
    qty: 2
  }]);
  const [code, setCode] = React.useState('');
  const [qty, setQty] = React.useState(1);
  const [error, setError] = React.useState('');
  const [cash, setCash] = React.useState('');
  const [doc, setDoc] = React.useState('ticket');
  const [done, setDone] = React.useState(null);
  const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
  const tax = subtotal * 0.13;
  const total = subtotal + tax;
  function add(code, n) {
    const q = (catalog || []).find(p => p.sku.toLowerCase() === code.toLowerCase() || p.barcode === code || p.name.toLowerCase().includes(code.toLowerCase()));
    if (!q) {
      setError('Producto no encontrado: ' + code);
      return;
    }
    setError('');
    setCart(prev => {
      const ex = prev.find(i => i.id === q.id);
      if (ex) return prev.map(i => i.id === q.id ? {
        ...i,
        qty: i.qty + n
      } : i);
      return [...prev, {
        ...q,
        qty: n
      }];
    });
  }
  function remove(id) {
    setCart(prev => prev.filter(i => i.id !== id));
  }
  function submitScan(e) {
    e.preventDefault();
    if (!code.trim()) return;
    add(code.trim(), Math.max(1, parseInt(qty, 10) || 1));
    setCode('');
    setQty(1);
  }
  function checkout() {
    if (cart.length === 0) {
      setError('Agrega productos al carrito.');
      return;
    }
    const change = (parseFloat(cash) || 0) - total;
    setDone({
      total,
      change,
      count: cart.length
    });
    onSale && onSale(total);
  }
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '2fr 1fr',
      gap: '24px',
      alignItems: 'start'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: '16px'
    }
  }, /*#__PURE__*/React.createElement(Card, null, /*#__PURE__*/React.createElement("form", {
    onSubmit: submitScan
  }, /*#__PURE__*/React.createElement("label", {
    style: {
      display: 'block',
      fontSize: 'var(--text-sm)',
      fontWeight: 500,
      color: 'var(--text-body)',
      marginBottom: '6px'
    }
  }, "Esc\xE1ner (c\xF3digo de barras o SKU)"), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: '8px'
    }
  }, /*#__PURE__*/React.createElement(Input, {
    value: code,
    onChange: e => setCode(e.target.value),
    placeholder: "Escribe SKU o c\xF3digo y presiona Enter",
    autoFocus: true
  }), /*#__PURE__*/React.createElement(Input, {
    type: "number",
    min: "1",
    value: qty,
    onChange: e => setQty(e.target.value),
    style: {
      width: 88,
      textAlign: 'right'
    }
  }), /*#__PURE__*/React.createElement(Button, {
    type: "submit",
    leadingIcon: "\uFF0B"
  }, "Agregar")), error ? /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '8px 0 0',
      fontSize: 'var(--text-sm)',
      color: 'var(--danger)'
    }
  }, error) : null, /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '8px 0 0',
      fontSize: 'var(--text-xs)',
      color: 'var(--text-muted)'
    }
  }, "Prueba: ", catalog.slice(0, 5).map(c => c.sku).join(' · ')))), /*#__PURE__*/React.createElement(DataTable, {
    columns: [{
      key: 'name',
      header: 'Producto'
    }, {
      header: 'Código',
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          fontFamily: 'var(--font-mono)',
          fontSize: 'var(--text-xs)',
          color: 'var(--text-muted)'
        }
      }, r.barcode || r.sku)
    }, {
      header: 'Precio',
      align: 'right',
      render: r => money(r.price)
    }, {
      header: 'Cantidad',
      align: 'right',
      render: r => r.qty
    }, {
      header: 'Subtotal',
      align: 'right',
      render: r => /*#__PURE__*/React.createElement("strong", {
        style: {
          color: 'var(--text-strong)'
        }
      }, money(r.price * r.qty))
    }, {
      header: '',
      align: 'right',
      render: r => /*#__PURE__*/React.createElement(IconButton, {
        label: "Quitar",
        variant: "danger",
        size: "sm",
        onClick: () => remove(r.id)
      }, "\uD83D\uDDD1\uFE0F")
    }],
    rows: cart,
    rowKey: "id",
    empty: "Escanea un producto para comenzar la venta."
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: '16px'
    }
  }, /*#__PURE__*/React.createElement(Card, null, /*#__PURE__*/React.createElement("p", {
    style: {
      margin: 0,
      fontSize: 'var(--text-xs)',
      textTransform: 'uppercase',
      letterSpacing: 'var(--tracking-wide)',
      color: 'var(--text-muted)'
    }
  }, "Caja activa"), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '6px 0 10px',
      fontSize: 'var(--text-sm)',
      color: 'var(--text-body)'
    }
  }, /*#__PURE__*/React.createElement("strong", {
    style: {
      color: 'var(--text-strong)'
    }
  }, "Caja 1"), " (CJ-01) ", /*#__PURE__*/React.createElement(Badge, {
    tone: "success",
    dot: true
  }, "Abierta")), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: '8px'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      background: 'var(--surface-sunken)',
      borderRadius: 'var(--radius-md)',
      padding: '8px'
    }
  }, /*#__PURE__*/React.createElement("p", {
    style: {
      margin: 0,
      fontSize: 'var(--text-xs)',
      color: 'var(--text-muted)'
    }
  }, "Base apertura"), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '2px 0 0',
      fontWeight: 600,
      color: 'var(--text-strong)'
    }
  }, money(50))), /*#__PURE__*/React.createElement("div", {
    style: {
      background: 'var(--surface-sunken)',
      borderRadius: 'var(--radius-md)',
      padding: '8px'
    }
  }, /*#__PURE__*/React.createElement("p", {
    style: {
      margin: 0,
      fontSize: 'var(--text-xs)',
      color: 'var(--text-muted)'
    }
  }, "Esperado actual"), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '2px 0 0',
      fontWeight: 600,
      color: 'var(--text-strong)'
    }
  }, money(50 + total))))), /*#__PURE__*/React.createElement(Card, null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: '8px'
    }
  }, /*#__PURE__*/React.createElement(Row, {
    label: "Subtotal",
    value: money(subtotal)
  }), /*#__PURE__*/React.createElement(Row, {
    label: "IVA 13%",
    value: money(tax)
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      height: 1,
      background: 'var(--border-subtle)',
      margin: '2px 0'
    }
  }), /*#__PURE__*/React.createElement(Row, {
    label: "Total",
    value: money(total),
    strong: true
  }))), /*#__PURE__*/React.createElement(Card, null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: '12px'
    }
  }, /*#__PURE__*/React.createElement(Field, {
    label: "Comprobante",
    htmlFor: "doc"
  }, /*#__PURE__*/React.createElement(Select, {
    id: "doc",
    value: doc,
    onChange: e => setDoc(e.target.value)
  }, /*#__PURE__*/React.createElement("option", {
    value: "ticket"
  }, "Ticket"), /*#__PURE__*/React.createElement("option", {
    value: "factura"
  }, "Factura"))), /*#__PURE__*/React.createElement(Field, {
    label: "Cliente (opcional)",
    htmlFor: "cust"
  }, /*#__PURE__*/React.createElement(Select, {
    id: "cust"
  }, /*#__PURE__*/React.createElement("option", null, "Consumidor final"), /*#__PURE__*/React.createElement("option", null, "Acme S.A. de C.V."))), /*#__PURE__*/React.createElement(Field, {
    label: "Efectivo recibido",
    htmlFor: "cash"
  }, /*#__PURE__*/React.createElement(Input, {
    id: "cash",
    type: "number",
    step: "0.01",
    value: cash,
    onChange: e => setCash(e.target.value),
    placeholder: "0.00"
  })), /*#__PURE__*/React.createElement(Button, {
    variant: "success",
    block: true,
    leadingIcon: "\uD83D\uDCB5",
    onClick: checkout
  }, "Cobrar ", money(total))))), /*#__PURE__*/React.createElement(Modal, {
    open: !!done,
    title: "Venta registrada",
    description: done ? `Comprobante ${doc.toUpperCase()} · ${done.count} ítems` : '',
    onClose: () => setDone(null),
    footer: /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "secondary",
      onClick: () => setDone(null)
    }, "Cerrar"), /*#__PURE__*/React.createElement(Button, {
      onClick: () => {
        setDone(null);
        setCart([]);
        setCash('');
      }
    }, "Nueva venta"))
  }, done ? /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: '10px'
    }
  }, /*#__PURE__*/React.createElement(Alert, {
    tone: "success",
    title: `Total cobrado: ${money(done.total)}`
  }, "Cambio a entregar: ", /*#__PURE__*/React.createElement("strong", null, money(Math.max(0, done.change))))) : null));
}
function Row({
  label,
  value,
  strong
}) {
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      fontSize: strong ? 'var(--text-base)' : 'var(--text-sm)',
      fontWeight: strong ? 600 : 400,
      color: strong ? 'var(--text-strong)' : 'var(--text-body)'
    }
  }, /*#__PURE__*/React.createElement("span", null, label), /*#__PURE__*/React.createElement("span", null, value));
}
window.PosScreen = PosScreen;
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/stockia-pos/PosScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/stockia-pos/ProductsScreen.jsx
try { (() => {
// ProductsScreen — inventory list with search, low-stock filter, table.
function ProductsScreen() {
  const {
    DataTable,
    Input,
    Button,
    Badge
  } = window.StockiaDesignSystem_235f53;
  const {
    catalog,
    money
  } = window.StockiaKit;
  const [q, setQ] = React.useState('');
  const [lowOnly, setLowOnly] = React.useState(false);
  const rows = catalog.filter(p => {
    const matches = !q || (p.name + p.sku + p.barcode).toLowerCase().includes(q.toLowerCase());
    const low = !lowOnly || p.stock <= p.min;
    return matches && low;
  });
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: '16px'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: '12px',
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement("h2", {
    style: {
      margin: 0,
      fontSize: 'var(--text-2xl)',
      fontWeight: 700,
      color: 'var(--text-strong)'
    }
  }, "Productos"), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: '8px'
    }
  }, /*#__PURE__*/React.createElement(Button, {
    variant: lowOnly ? 'danger' : 'secondary',
    onClick: () => setLowOnly(v => !v)
  }, lowOnly ? 'Mostrando stock bajo' : 'Ver stock bajo'), /*#__PURE__*/React.createElement(Button, {
    leadingIcon: "\uFF0B"
  }, "Nuevo producto"))), /*#__PURE__*/React.createElement(DataTable, {
    header: /*#__PURE__*/React.createElement("div", {
      style: {
        display: 'flex',
        gap: '8px',
        width: '100%'
      }
    }, /*#__PURE__*/React.createElement(Input, {
      value: q,
      onChange: e => setQ(e.target.value),
      placeholder: "Buscar por nombre, SKU o c\xF3digo de barras\u2026",
      style: {
        maxWidth: 380
      }
    }), q ? /*#__PURE__*/React.createElement(Button, {
      variant: "secondary",
      onClick: () => setQ('')
    }, "Limpiar") : null),
    columns: [{
      key: 'id',
      header: 'ID'
    }, {
      key: 'name',
      header: 'Nombre'
    }, {
      header: 'SKU',
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          fontFamily: 'var(--font-mono)',
          fontSize: 'var(--text-xs)'
        }
      }, r.sku)
    }, {
      key: 'category',
      header: 'Categoría',
      render: r => /*#__PURE__*/React.createElement(Badge, {
        tone: "neutral"
      }, r.category)
    }, {
      header: 'Precio',
      align: 'right',
      render: r => money(r.price)
    }, {
      header: 'Stock',
      align: 'right',
      render: r => r.stock <= r.min ? /*#__PURE__*/React.createElement(Badge, {
        tone: "danger"
      }, r.stock, " \u26A0") : /*#__PURE__*/React.createElement("span", {
        style: {
          color: 'var(--text-strong)',
          fontWeight: 500
        }
      }, r.stock)
    }, {
      header: 'Acciones',
      align: 'right',
      render: () => /*#__PURE__*/React.createElement("span", {
        style: {
          display: 'inline-flex',
          gap: '6px'
        }
      }, /*#__PURE__*/React.createElement(Button, {
        variant: "ghost",
        size: "sm"
      }, "Editar"), /*#__PURE__*/React.createElement(Button, {
        variant: "ghost",
        size: "sm"
      }, "Ajustar"))
    }],
    rows: rows,
    rowKey: "id",
    empty: "No hay productos que coincidan.",
    footer: /*#__PURE__*/React.createElement("span", {
      style: {
        fontSize: 'var(--text-sm)',
        color: 'var(--text-muted)'
      }
    }, rows.length, " de ", catalog.length, " productos")
  }));
}
window.ProductsScreen = ProductsScreen;
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/stockia-pos/ProductsScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/stockia-pos/SummaryScreen.jsx
try { (() => {
// SummaryScreen — Resumen del cajero: KPIs + latest sales + top products.
function SummaryScreen({
  salesToday
}) {
  const {
    Card,
    StatCard,
    DataTable,
    Badge
  } = window.StockiaDesignSystem_235f53;
  const {
    money
  } = window.StockiaKit;
  const base = 18;
  const count = base + (salesToday ? salesToday.length : 0);
  const gross = 333.5 + (salesToday ? salesToday.reduce((s, n) => s + n, 0) : 0);
  const latest = [...(salesToday || []).map((n, i) => ({
    id: 1041 + i,
    doc: 'TICKET',
    total: n,
    time: 'ahora'
  })), {
    id: 1040,
    doc: 'FACTURA',
    total: 42.30,
    time: '14:22'
  }, {
    id: 1039,
    doc: 'TICKET',
    total: 7.85,
    time: '14:08'
  }, {
    id: 1038,
    doc: 'TICKET',
    total: 15.40,
    time: '13:51'
  }, {
    id: 1037,
    doc: 'FACTURA',
    total: 88.10,
    time: '13:30'
  }].slice(0, 6);
  const top = [{
    name: 'Coca-Cola 500ml',
    qty: 96,
    sub: 72.00
  }, {
    name: 'Pan francés (unidad)',
    qty: 240,
    sub: 36.00
  }, {
    name: 'Agua mineral 600ml',
    qty: 58,
    sub: 29.00
  }, {
    name: 'Café molido 250g',
    qty: 8,
    sub: 25.60
  }];
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: '24px'
    }
  }, /*#__PURE__*/React.createElement(Card, null, /*#__PURE__*/React.createElement("h2", {
    style: {
      margin: 0,
      fontSize: 'var(--text-xl)',
      fontWeight: 700,
      color: 'var(--text-strong)'
    }
  }, "Resumen del cajero"), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '4px 0 8px',
      fontSize: 'var(--text-sm)',
      color: 'var(--text-muted)'
    }
  }, "Rendimiento de hoy en el almac\xE9n activo."), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: 0,
      fontSize: 'var(--text-sm)',
      color: 'var(--text-body)'
    }
  }, "Estado de caja: ", /*#__PURE__*/React.createElement(Badge, {
    tone: "success",
    dot: true
  }, "Abierta"), " ", /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--text-muted)'
    }
  }, "\u2014 Caja 1 (CJ-01)"))), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(5, 1fr)',
      gap: '16px'
    }
  }, /*#__PURE__*/React.createElement(StatCard, {
    label: "Ventas",
    value: count
  }), /*#__PURE__*/React.createElement(StatCard, {
    label: "Total vendido",
    value: money(gross),
    tone: "success"
  }), /*#__PURE__*/React.createElement(StatCard, {
    label: "Efectivo recibido",
    value: money(gross * 0.82)
  }), /*#__PURE__*/React.createElement(StatCard, {
    label: "Ticket promedio",
    value: money(gross / count),
    tone: "primary"
  }), /*#__PURE__*/React.createElement(StatCard, {
    label: "Unidades vendidas",
    value: 462
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: '24px'
    }
  }, /*#__PURE__*/React.createElement(DataTable, {
    header: /*#__PURE__*/React.createElement("strong", {
      style: {
        color: 'var(--text-strong)'
      }
    }, "\xDAltimas ventas"),
    columns: [{
      header: 'Venta',
      render: r => '#' + r.id
    }, {
      header: 'Documento',
      render: r => /*#__PURE__*/React.createElement(Badge, {
        tone: r.doc === 'FACTURA' ? 'primary' : 'neutral'
      }, r.doc)
    }, {
      header: 'Total',
      align: 'right',
      render: r => money(r.total)
    }, {
      key: 'time',
      header: 'Hora'
    }],
    rows: latest,
    rowKey: "id"
  }), /*#__PURE__*/React.createElement(DataTable, {
    header: /*#__PURE__*/React.createElement("strong", {
      style: {
        color: 'var(--text-strong)'
      }
    }, "Top productos"),
    columns: [{
      key: 'name',
      header: 'Producto'
    }, {
      header: 'Unidades',
      align: 'right',
      render: r => r.qty
    }, {
      header: 'Importe',
      align: 'right',
      render: r => money(r.sub)
    }],
    rows: top,
    rowKey: "name"
  })));
}
window.SummaryScreen = SummaryScreen;
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/stockia-pos/SummaryScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/stockia-pos/data.js
try { (() => {
// Shared demo catalog + sidebar config for the Stockia POS UI kit.
window.StockiaKit = window.StockiaKit || {};
window.StockiaKit.catalog = [{
  id: 1,
  name: 'Coca-Cola 500ml',
  sku: 'CC-500',
  barcode: '7401001',
  price: 0.75,
  stock: 124,
  min: 20,
  category: 'Bebidas'
}, {
  id: 2,
  name: 'Pan francés (unidad)',
  sku: 'PAN-FR',
  barcode: '7401002',
  price: 0.15,
  stock: 8,
  min: 30,
  category: 'Panadería'
}, {
  id: 3,
  name: 'Café molido 250g',
  sku: 'CAFE-250',
  barcode: '7401003',
  price: 3.20,
  stock: 46,
  min: 10,
  category: 'Abarrotes'
}, {
  id: 4,
  name: 'Leche entera 1L',
  sku: 'LECHE-1L',
  barcode: '7401004',
  price: 1.10,
  stock: 62,
  min: 15,
  category: 'Lácteos'
}, {
  id: 5,
  name: 'Arroz 1kg',
  sku: 'ARROZ-1K',
  barcode: '7401005',
  price: 1.45,
  stock: 5,
  min: 25,
  category: 'Abarrotes'
}, {
  id: 6,
  name: 'Aceite vegetal 900ml',
  sku: 'ACEITE-900',
  barcode: '7401006',
  price: 2.30,
  stock: 38,
  min: 12,
  category: 'Abarrotes'
}, {
  id: 7,
  name: 'Huevos (docena)',
  sku: 'HUEVO-12',
  barcode: '7401007',
  price: 2.05,
  stock: 21,
  min: 10,
  category: 'Lácteos'
}, {
  id: 8,
  name: 'Agua mineral 600ml',
  sku: 'AGUA-600',
  barcode: '7401008',
  price: 0.50,
  stock: 200,
  min: 40,
  category: 'Bebidas'
}];
window.StockiaKit.sections = [{
  label: 'Gestión',
  items: [{
    icon: '📊',
    label: 'Dashboard',
    route: 'dashboard'
  }]
}, {
  label: 'Comercial',
  items: [{
    icon: '💹',
    label: 'Resumen POS',
    route: 'summary'
  }, {
    icon: '🛒',
    label: 'Punto de Venta',
    route: 'pos'
  }, {
    icon: '🗃️',
    label: 'Ventas',
    route: 'sales'
  }, {
    icon: '📝',
    label: 'Cotizaciones',
    route: 'quotes'
  }]
}, {
  label: 'Inventario',
  items: [{
    icon: '📦',
    label: 'Productos',
    route: 'products'
  }, {
    icon: '🏷',
    label: 'Categorías',
    route: 'categories'
  }, {
    icon: '🔄',
    label: 'Movimientos',
    route: 'movements'
  }]
}, {
  label: 'Administración',
  items: [{
    icon: '👤',
    label: 'Usuarios',
    route: 'users'
  }, {
    icon: '💱',
    label: 'Monedas',
    route: 'currencies'
  }]
}, {
  label: 'Sistema',
  items: [{
    icon: '🔁',
    label: 'Cambiar Almacén',
    route: 'warehouse'
  }, {
    icon: '🚪',
    label: 'Cerrar Sesión',
    route: 'logout'
  }]
}];
window.StockiaKit.money = n => '$' + Number(n).toFixed(2);
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/stockia-pos/data.js", error: String((e && e.message) || e) }); }

__ds_ns.Button = __ds_scope.Button;

__ds_ns.IconButton = __ds_scope.IconButton;

__ds_ns.Card = __ds_scope.Card;

__ds_ns.DataTable = __ds_scope.DataTable;

__ds_ns.Alert = __ds_scope.Alert;

__ds_ns.Badge = __ds_scope.Badge;

__ds_ns.StatCard = __ds_scope.StatCard;

__ds_ns.Checkbox = __ds_scope.Checkbox;

__ds_ns.Field = __ds_scope.Field;

__ds_ns.Input = __ds_scope.Input;

__ds_ns.Select = __ds_scope.Select;

__ds_ns.Sidebar = __ds_scope.Sidebar;

__ds_ns.Topbar = __ds_scope.Topbar;

__ds_ns.Modal = __ds_scope.Modal;

})();
