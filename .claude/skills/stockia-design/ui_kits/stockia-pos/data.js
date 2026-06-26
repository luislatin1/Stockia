// Shared demo catalog + sidebar config for the Stockia POS UI kit.
window.StockiaKit = window.StockiaKit || {};

window.StockiaKit.catalog = [
  { id: 1, name: 'Coca-Cola 500ml', sku: 'CC-500', barcode: '7401001', price: 0.75, stock: 124, min: 20, category: 'Bebidas' },
  { id: 2, name: 'Pan francés (unidad)', sku: 'PAN-FR', barcode: '7401002', price: 0.15, stock: 8, min: 30, category: 'Panadería' },
  { id: 3, name: 'Café molido 250g', sku: 'CAFE-250', barcode: '7401003', price: 3.20, stock: 46, min: 10, category: 'Abarrotes' },
  { id: 4, name: 'Leche entera 1L', sku: 'LECHE-1L', barcode: '7401004', price: 1.10, stock: 62, min: 15, category: 'Lácteos' },
  { id: 5, name: 'Arroz 1kg', sku: 'ARROZ-1K', barcode: '7401005', price: 1.45, stock: 5, min: 25, category: 'Abarrotes' },
  { id: 6, name: 'Aceite vegetal 900ml', sku: 'ACEITE-900', barcode: '7401006', price: 2.30, stock: 38, min: 12, category: 'Abarrotes' },
  { id: 7, name: 'Huevos (docena)', sku: 'HUEVO-12', barcode: '7401007', price: 2.05, stock: 21, min: 10, category: 'Lácteos' },
  { id: 8, name: 'Agua mineral 600ml', sku: 'AGUA-600', barcode: '7401008', price: 0.50, stock: 200, min: 40, category: 'Bebidas' },
];

window.StockiaKit.sections = [
  { label: 'Gestión', items: [{ icon: '📊', label: 'Dashboard', route: 'dashboard' }] },
  { label: 'Comercial', items: [
    { icon: '💹', label: 'Resumen POS', route: 'summary' },
    { icon: '🛒', label: 'Punto de Venta', route: 'pos' },
    { icon: '🗃️', label: 'Ventas', route: 'sales' },
    { icon: '📝', label: 'Cotizaciones', route: 'quotes' },
  ]},
  { label: 'Inventario', items: [
    { icon: '📦', label: 'Productos', route: 'products' },
    { icon: '🏷', label: 'Categorías', route: 'categories' },
    { icon: '🔄', label: 'Movimientos', route: 'movements' },
  ]},
  { label: 'Administración', items: [
    { icon: '👤', label: 'Usuarios', route: 'users' },
    { icon: '💱', label: 'Monedas', route: 'currencies' },
  ]},
  { label: 'Sistema', items: [
    { icon: '🔁', label: 'Cambiar Almacén', route: 'warehouse' },
    { icon: '🚪', label: 'Cerrar Sesión', route: 'logout' },
  ]},
];

window.StockiaKit.money = (n) => '$' + Number(n).toFixed(2);
