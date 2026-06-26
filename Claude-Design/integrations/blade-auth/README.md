# Stockia Login — Blade Integration

Vistas Blade listas para copiar al proyecto Laravel. Implementan el diseño del **Stockia Login** template: pantalla dividida (panel de marca + formulario), flujo completo de autenticación y selección de empresa/almacén.

---

## Archivos incluidos

```
blade-auth/
  layouts/guest.blade.php          → layout guest actualizado (split)
  auth/login.blade.php             → formulario de login
  auth/forgot-password.blade.php   → recuperar contraseña
  auth/select-company.blade.php    → selección de empresa (Alpine)
  auth/select-warehouse.blade.php  → selección de almacén (Alpine)
```

---

## Paso 1 — Copiar los componentes Stockia

Copia los archivos de `integrations/blade/` en tu proyecto:

```
resources/views/components/stockia/
  button.blade.php
  card.blade.php
  stat-card.blade.php
  badge.blade.php
  alert.blade.php
  input.blade.php
  field.blade.php
  table.blade.php
```

Los componentes quedan disponibles como `<x-stockia.button>`, `<x-stockia.alert>`, etc.

---

## Paso 2 — Reemplazar el guest layout

Sustituye `resources/views/layouts/guest.blade.php` con `layouts/guest.blade.php`.

> El panel de marca (izquierda) se oculta automáticamente en pantallas menores a `lg` (1024 px).

---

## Paso 3 — Reemplazar las vistas de auth

Copia los archivos de `auth/` a `resources/views/auth/`, sobreescribiendo los existentes:

| Archivo origen                   | Destino en tu proyecto                        |
|----------------------------------|-----------------------------------------------|
| `auth/login.blade.php`           | `resources/views/auth/login.blade.php`        |
| `auth/forgot-password.blade.php` | `resources/views/auth/forgot-password.blade.php` |
| `auth/select-company.blade.php`  | `resources/views/auth/select-company.blade.php`  |
| `auth/select-warehouse.blade.php`| `resources/views/auth/select-warehouse.blade.php`|

---

## Paso 4 — Ajustar select-company y select-warehouse

Estas vistas ahora usan `<x-guest-layout>` en lugar de `@extends('layouts.app')`.
Asegúrate de que los controladores pasen las variables correctas:

```php
// CompanyController@select (GET)
return view('auth.select-company', [
    'companies' => auth()->user()->companies,
]);

// WarehouseController@select (GET)
return view('auth.select-warehouse', [
    'warehouses' => $company->warehouses,
]);
```

Las rutas `company.select` y `warehouse.select` deben existir en `routes/auth.php` o `routes/web.php`.

---

## Paso 5 — Verificar Alpine.js

Los selectores de empresa y almacén usan `x-data` / `@click` / `x-show` de Alpine.js.
Tu proyecto ya incluye Alpine en `resources/js/app.js` (via Breeze) — no se requiere nada adicional.

---

## Personalización

| Qué cambiar                  | Dónde                                      |
|------------------------------|--------------------------------------------|
| Nombre de la app             | `config/app.php` → `'name' => 'Stockia'`  |
| Claim del panel de marca     | `layouts/guest.blade.php` línea ~28        |
| Features del panel           | `layouts/guest.blade.php` líneas ~31-43   |
| Layout centrado (sin panel)  | Elimina el `<div class="hidden lg:flex …">` en `guest.blade.php` y agrega el wordmark sobre el formulario |
| Logo en lugar del initial    | Reemplaza el `<div>` de inicial por `<img src="…">` en `guest.blade.php` |
