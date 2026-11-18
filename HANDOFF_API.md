# API REST (Postman/Swagger) — estado y continuación

Documento de traspaso de la API nueva de Stockia, construida 2026-11-18. Complementa a `MIGRATION_STATUS.md` (que cubre el resto del proyecto) — este archivo es solo sobre la API.

## 1. Qué existe hoy

**Fundación (Sanctum + puente de contexto + documentación):**
- `laravel/sanctum` instalado, `HasApiTokens` en `app/Models/User.php`, tabla `personal_access_tokens` migrada.
- `app/Http/Controllers/Api/V1/AuthController.php` — `POST /api/v1/login`, `POST /api/v1/logout`, `GET /api/v1/me`.
- `app/Http/Middleware/ResolveApiCompanyContext.php` (alias `api.company`) — lee headers `X-Company-Id`/`X-Warehouse-Id`, valida que el usuario pertenezca a esa empresa/bodega (misma lógica que `SetCurrentCompany`), y los deja en `session()` para esa request. **Por esto toda la API corre con `StartSession` explícito** (no el grupo `web` completo — ese trae `VerifyCsrfToken`, que rompería un cliente autenticado solo por Bearer token).
- `app/Http/Middleware/EnsureCompanySelected.php` y `EnsureCompanyRole.php` ahora devuelven JSON (422/403) en vez de redirigir cuando `$request->expectsJson()`.
- `dedoc/scramble` instalado — Swagger UI en `/docs/api`, spec OpenAPI en `/docs/api.json` (importable directo en Postman: Import → OpenAPI). No requiere anotaciones manuales, lee las rutas/controladores tal cual.
- `app/Http/Resources/` — `ProductResource`, `SaleResource`, `SaleItemResource`, `CompanyResource`, `WarehouseResource`.
- `RateLimiter::for('api', ...)` registrado en `app/Providers/AppServiceProvider.php::boot()` — **no existía antes**, lo necesita `throttle:api`.

**Núcleo — Productos y Ventas (`routes/api.php`):**
- `app/Http/Controllers/Api/V1/ProductController.php` — `index/show/store/update`.
- `app/Http/Controllers/Api/V1/SaleController.php` — `index/show/store`, con el mismo filtro "un Vendedor solo ve sus propias ventas" que ya tenía la web.
- `app/Actions/Sales/CreateSale.php` — la secuencia completa de crear una venta (cálculo DTE → transacción Sale+SaleItem+InventoryMovement → emisión DTE si `dte-sv-mh` está activo), extraída de `SaleController::store()` (web). **Ambos caminos (web y API) llaman esta misma clase** — no hay dos implementaciones.
- `app/Exceptions/SaleValidationException.php` — error de negocio (no de forma) al crear una venta; cada controlador decide cómo mostrarlo.

**Paquete `ptv-pos` (prueba de que el molde funciona dentro de un módulo):**
- `packages/ptv-pos/routes/api.php` — `GET /api/v1/ptv-pos/session` (estado de caja abierta + efectivo esperado), `POST /api/v1/ptv-pos/session/open` (abrir caja).
- `packages/ptv-pos/src/Http/Controllers/Api/PTVPosApiController.php`.
- Cargado desde `PTVPosServiceProvider::boot()` con el mismo `loadRoutesFrom()` + el mismo early-return por `Module::isEnabled('ptv-pos')` que ya protegía las rutas web del paquete.
- **Importante**: si el módulo `ptv-pos` está desactivado, `/api/v1/ptv-pos/*` da **404** (no una página "módulo bloqueado" como la web) — es intencional: para una API, 404 es la respuesta correcta cuando la funcionalidad no existe/no está instalada.

## 2. Bugs reales encontrados y arreglados durante la verificación

1. **Scoping por vendedor faltante en la API de ventas**: `SaleController::index/show` (web) ya restringía a un Vendedor a ver solo sus propias ventas; la primera versión de la API no lo hacía — cualquier Vendedor autenticado veía las ventas de toda la empresa. Corregido en `Api\V1\SaleController` para igualar el comportamiento web.
2. **`throttle:api` no estaba definido**: este proyecto nunca había usado ese middleware (la API vieja era un stub roto), así que el rate limiter nombrado `api` jamás se registró. Se agregó en `AppServiceProvider::boot()` — ahora lo usan tanto el core como `ptv-pos`.

## 3. Cómo probarlo (verificado a mano, 2026-11-18)

Todo lo de abajo se probó contra la BD real de Quezalteca usando usuarios/productos/cajas **de prueba creados y borrados al final** — ver sección 5 antes de repetir esto. Nunca reutilizar al usuario real ni dejar basura en `products`/`sales`/`pos_sessions`/`pos_registers`.

```
POST /api/v1/login
  body: {"email":"...", "password":"...", "device_name":"postman"}
  -> {"token": "...", "companies": [{"id":1,"name":"...","role":"Admin"}]}

GET /api/v1/products
  headers: Authorization: Bearer <token>, X-Company-Id: 1, X-Warehouse-Id: 1
  -> sin X-Company-Id da 422; con empresa ajena al usuario da 422; sin token da 401.

POST /api/v1/sales
  headers: igual + Content-Type: application/json
  body: {"products":{"<product_id>":2}, "cash_received":30, "document_type":"ticket"}
  -> crea Sale+SaleItem+InventoryMovement en transacción, descuenta stock real.
```

Swagger UI: abrir `/docs/api` en el navegador. Spec para Postman: `GET /docs/api.json` → Postman → Import → OpenAPI.

Módulos: todos (`inventory`, `sales`, `ptv-pos`, `sales-quotes`, `dte-sv-mh`, `accounting`) están **desactivados** en esta BD ahora mismo (estado real encontrado, no algo que se tocó) — para probar DTE real en una venta, o la API de `ptv-pos`, hay que activarlos primero desde Ajustes → Módulos (o `Module::where('key',...)->update(['enabled'=>true])` y volver a dejarlo en `false` al terminar).

## 4. Qué falta (próxima sesión)

En orden sugerido:

1. **Cerrar caja + checkout por API en `ptv-pos`**: hoy solo hay `session` (estado) y `session/open`. Falta `session/close` y el `checkout` (venta desde POS, que además dispara DTE) — son más complejos que lo ya hecho porque `checkout` en web usa impresión/plantillas y el flujo de caja completo (`storeClose`, `checkout` en `PTVPosController.php`). Mismo molde: nuevo método en `PTVPosApiController`, nueva ruta en `packages/ptv-pos/routes/api.php`.
2. **Extender el mismo molde a los otros 5 paquetes** (`accounting`, `inventory`, `sales`, `sales-quotes`, `dte-sv-mh`) — cuando haya necesidad real de consumirlos por API. Receta exacta: `routes/api.php` dentro del paquete + `loadRoutesFrom()` adicional en su Service Provider (mismo early-return por `Module::isEnabled`) + middleware `['throttle:api','auth:sanctum', StartSession::class, ResolveApiCompanyContext::class, 'company.selected', 'can:<permiso>']`.
3. **Pendiente de siempre, documentado en `MIGRATION_STATUS.md`**: sin cobertura de tests automatizados — cualquier cambio a la API (o a lo que ya existe) se sigue verificando a mano. Si se agrega Pest/PHPUnit en algún momento, la API nueva es terreno fértil para los primeros tests de feature (login, scoping por empresa, permisos) porque ya quedó mapeado exactamente qué debe pasar en cada caso (sección 3).
4. **Antes de exponer esta API a un cliente externo real** (no solo Postman/pruebas internas): decidir política de expiración/revocación de tokens (Sanctum los deja sin expirar por defecto), y si hace falta CORS (`config/cors.php` no se tocó).

## 5. Reglas al seguir probando esto

- **Nunca** dejar usuarios/productos/ventas/cajas de prueba en esta BD — es la operación real de Quezalteca ([[stockia-dev-data-warning]] en memoria). Crear con nombre reconocible ("TEST API - borrar"), probar, borrar todo (incluyendo movimientos de inventario y pivots), confirmar conteos antes/después.
- **Nunca** dejar un módulo activado/desactivado distinto a como estaba si solo era para probar — todos estaban en `false` antes de esta sesión.
