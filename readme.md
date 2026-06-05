# SuperScanner

Plugin de WordPress para escanear códigos de barras y comparar precios en supermercados argentinos.

## Supermercados disponibles

| Supermercado | Slug |
|---|---|
| Mas Online | `masonline` |
| Carrefour | `carrefour` |
| Vea | `vea` |

## Instalación

1. Copiar la carpeta `super-scanner` a `wp-content/plugins/`
2. Activar desde el panel de WordPress (Plugins → SuperScanner)
3. Agregar `[super_scanner]` en cualquier página o post

## Cómo funciona

Agregás el shortcode `[super_scanner]` en una página. Te aparece:

- Un campo para **escribir el código de barras** a mano
- Un botón con **ícono de cámara** para escanear con el celular
- Un botón **"Comparar precios"**
- Una tarjeta por cada supermercado con: nombre, marca, precio y link al producto

## Endpoints REST (para usar desde otra app)

Cada supermercado tiene su propia URL:

```
GET /wp-json/super-scanner/v1/precio/masonline/{ean}
GET /wp-json/super-scanner/v1/precio/carrefour/{ean}
GET /wp-json/super-scanner/v1/precio/vea/{ean}
```

### Respuesta

```json
{
  "success": true,
  "store": "masonline",
  "store_name": "Mas Online",
  "nombre": "Leche En Polvo La Lechera Nutrifuerza 800 G",
  "marca": "La Lechera",
  "imagen": "https://masonlineprod.vteximg.com.br/...jpg",
  "link": "https://www.masonline.com.ar/.../p",
  "precio_lista": 13189,
  "precio_web": 13189,
  "tiene_desc": false
}
```

## Agregar un nuevo supermercado

1. Crear `stores/class-store-nombre.php` que extienda `Super_Scanner_Store`
2. Registrar en `super-scanner.php` con `$controller->add_store(new Super_Scanner_Store_Nombre())`
3. Agregar color en `includes/class-shortcode.php` en el array `$colors`

Ejemplo de store:

```php
class Super_Scanner_Store_Nombre extends Super_Scanner_Store {
    public function get_slug() { return 'nombre'; }
    public function get_name() { return 'Nombre'; }
    public function get_base_url() { return 'https://www.super.com.ar/api/catalog_system/pub/products/search'; }
    public function get_sales_channels() { return [1]; }
    public function get_ean_filter_prefix() { return 'alternateIds_Ean'; }
}
```

## Requisitos

- WordPress 5.0+
- PHP 7.4+
- Font Awesome (se carga automáticamente)

## Créditos

Creado por Martin Kanneman.
