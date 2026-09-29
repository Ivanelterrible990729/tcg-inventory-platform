
> **Versión:** V1  
> **Fuente:** `BD-TCG-Platform.txt`  
> **Alcance:** catálogo TCG, productos, cartas single, imágenes, inventario, carrito, órdenes y recolección en tienda.

## 1. Propósito

Este documento describe la estructura de datos de **TCG Inventory Platform**. La V1 está diseñada alrededor de una regla simple: **si dos artículos requieren stock independiente, se registran como productos independientes**. Por ello no existe un sistema de variantes; diferencias como color, rareza o condición pueden representar productos distintos.

Una carta single se identifica por la existencia de una relación 1:1 entre `products` y `card_details`. La V1 tampoco contempla direcciones ni envíos: las órdenes se entregan mediante recolección física en tienda.

## 2. Convenciones

- **PK**: clave primaria.
- **FK**: clave foránea.
- **NOT NULL**: el campo es obligatorio.
- **UNIQUE**: no admite valores duplicados.
- Los campos `created_at` y `updated_at` siguen la convención de timestamps del sistema.
- Los campos monetarios utilizan `decimal(12,2)`.
- Los valores enumerados documentados mediante `note` representan los estados previstos por el esquema V1.

## 3. Resumen de tablas

| Tabla                 | Propósito                                                                                                                             |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| `users`               | Usuarios con acceso a la plataforma. Centraliza autenticación, rol y estado de la cuenta.                                             |
| `customers`           | Extensión de `users` para información propia de clientes que realizan compras.                                                        |
| `tcgs`                | Catálogo de juegos TCG soportados por la plataforma, por ejemplo Yu-Gi-Oh!, Pokémon o Riftbound.                                      |
| `product_types`       | Catálogo de tipos de producto: single, deckbox, playmat, producto sellado, etc.                                                       |
| `products`            | Entidad central del catálogo e inventario. Cada artículo que requiere stock independiente se registra como un producto independiente. |
| `card_details`        | Información especializada de cartas single. La existencia de este registro identifica a un `product` como carta.                      |
| `product_images`      | Imágenes asociadas a productos, ya sean almacenadas localmente o referenciadas desde una fuente externa.                              |
| `inventory_movements` | Bitácora de cambios de existencias: compras, ventas, devoluciones, ajustes, reservas y liberaciones.                                  |
| `carts`               | Carritos de compra de clientes autenticados o sesiones anónimas.                                                                      |
| `cart_items`          | Productos y cantidades contenidos en un carrito.                                                                                      |
| `orders`              | Órdenes generadas por los clientes. En V1 todas se entregan mediante recolección física en tienda.                                    |
| `order_items`         | Detalle histórico de productos comprados. Conserva snapshots de nombre, SKU y precio para no alterar órdenes pasadas.                 |
| `pickups`             | Control de preparación y entrega física de una orden en el punto de recolección.                                                      |

## 4. Diccionario por tabla

### 4.1 `users`

Usuarios con acceso a la plataforma. Centraliza autenticación, rol y estado de la cuenta.

| Campo | Tipo | Restricciones | Descripción |
| --- | --- | --- | --- |
| `id` | `bigint` | PK; Autoincremental | Identificador único autoincremental. |
| `name` | `varchar(150)` | NOT NULL | Nombre descriptivo. |
| `email` | `varchar(255)` | NOT NULL; UNIQUE | Correo electrónico del usuario. |
| `password` | `varchar(255)` | NOT NULL | Contraseña almacenada de forma segura mediante hash. |
| `role` | `varchar(30)` | NOT NULL; Default: 'customer'; Valores/nota: admin, staff, customer | Rol de autorización del usuario. |
| `is_active` | `boolean` | NOT NULL; Default: true | Indica si el registro está habilitado para uso. |
| `email_verified_at` | `timestamp` | — | Fecha y hora en que se verificó el correo. |
| `created_at` | `timestamp` | — | Fecha y hora de creación. |
| `updated_at` | `timestamp` | — | Fecha y hora de última actualización. |

### 4.2 `customers`

Extensión de `users` para información propia de clientes que realizan compras.

Todo usuario debe tener un cliente asociado, independientemente de su rol (`admin`, `staff` o `customer`). `customers.user_id` es una clave foránea única a `users.id`; al eliminar un usuario, su cliente se elimina en cascada para mantener compatible la eliminación de cuenta existente.

El teléfono admite `NULL` en la base de datos para cuentas de seeders, pero es obligatorio en la interfaz y la lógica de creación/edición de usuarios que se implementará en los siguientes incrementos. `UserObserver` crea automáticamente el cliente con teléfono nulo en el evento `created` de Eloquent. Las inserciones directas o con eventos deshabilitados no ejecutan el observer. Los flujos de creación deberán usar una transacción para que ambas escrituras se confirmen o reviertan juntas; el observer se ejecuta dentro de esa transacción, no después del commit.

Los valores de rol se centralizan en `App\Enums\RoleEnum`; la migración de usuarios usa `RoleEnum::CUSTOMER->value` como valor predeterminado.

| Campo | Tipo | Restricciones | Descripción |
| --- | --- | --- | --- |
| `id` | `bigint` | PK; Autoincremental | Identificador único autoincremental. |
| `user_id` | `bigint` | NOT NULL; UNIQUE | Usuario asociado. |
| `phone` | `varchar(30)` | — | Número telefónico del cliente. |
| `created_at` | `timestamp` | — | Fecha y hora de creación. |
| `updated_at` | `timestamp` | — | Fecha y hora de última actualización. |

### 4.3 `tcgs`

Catálogo de juegos TCG soportados por la plataforma, por ejemplo Yu-Gi-Oh!, Pokémon o Riftbound.

| Campo | Tipo | Restricciones | Descripción |
| --- | --- | --- | --- |
| `id` | `bigint` | PK; Autoincremental | Identificador único autoincremental. |
| `name` | `varchar(100)` | NOT NULL; UNIQUE | Nombre descriptivo. |
| `slug` | `varchar(120)` | NOT NULL; UNIQUE | Identificador legible para URLs. |
| `description` | `text` | — | Descripción del registro. |
| `is_active` | `boolean` | NOT NULL; Default: true | Indica si el registro está habilitado para uso. |
| `created_at` | `timestamp` | — | Fecha y hora de creación. |
| `updated_at` | `timestamp` | — | Fecha y hora de última actualización. |

### 4.4 `product_types`

Catálogo de tipos de producto: single, deckbox, playmat, producto sellado, etc.

| Campo | Tipo | Restricciones | Descripción |
| --- | --- | --- | --- |
| `id` | `bigint` | PK; Autoincremental | Identificador único autoincremental. |
| `name` | `varchar(100)` | NOT NULL; UNIQUE | Nombre descriptivo. |
| `slug` | `varchar(120)` | NOT NULL; UNIQUE | Identificador legible para URLs. |
| `description` | `text` | — | Descripción del registro. |
| `is_active` | `boolean` | NOT NULL; Default: true | Indica si el registro está habilitado para uso. |
| `created_at` | `timestamp` | — | Fecha y hora de creación. |
| `updated_at` | `timestamp` | — | Fecha y hora de última actualización. |

### 4.5 `products`

Entidad central del catálogo e inventario. Cada artículo que requiere stock independiente se registra como un producto independiente.

| Campo | Tipo | Restricciones | Descripción |
| --- | --- | --- | --- |
| `id` | `bigint` | PK; Autoincremental | Identificador único autoincremental. |
| `tcg_id` | `bigint` | — | TCG al que pertenece el producto. Puede ser nulo para productos no asociados a un TCG específico. |
| `product_type_id` | `bigint` | NOT NULL | Tipo o categoría funcional del producto. |
| `name` | `varchar(255)` | NOT NULL | Nombre descriptivo. |
| `slug` | `varchar(255)` | NOT NULL; UNIQUE | Identificador legible para URLs. |
| `description` | `text` | — | Descripción del registro. |
| `cost_price` | `decimal(12,2)` | — | Costo de adquisición del producto. |
| `base_price` | `decimal(12,2)` | NOT NULL | Precio regular de venta. |
| `sale_price` | `decimal(12,2)` | — | Precio promocional opcional. |
| `sale_starts_at` | `timestamp` | — | Inicio de vigencia del precio promocional. |
| `sale_ends_at` | `timestamp` | — | Fin de vigencia del precio promocional. |
| `stock` | `int` | NOT NULL; Default: 0 | Existencias físicas registradas. |
| `reserved_stock` | `int` | NOT NULL; Default: 0 | Existencias temporalmente reservadas para procesos de compra. |
| `status` | `varchar(30)` | NOT NULL; Default: 'draft'; Valores/nota: draft, active, inactive, archived | Estado actual del registro dentro de su flujo. |
| `is_featured` | `boolean` | NOT NULL; Default: false | Indica si el producto debe destacarse visualmente. |
| `created_at` | `timestamp` | — | Fecha y hora de creación. |
| `updated_at` | `timestamp` | — | Fecha y hora de última actualización. |

**Regla de negocio:** dos artículos que necesitan existencias independientes son dos productos distintos. Esto aplica, por ejemplo, a deckboxes de distinto color y a una misma carta con distinta rareza o condición.

### 4.6 `card_details`

Información especializada de cartas single. La existencia de este registro identifica a un `product` como carta.

| Campo | Tipo | Restricciones | Descripción |
| --- | --- | --- | --- |
| `id` | `bigint` | PK; Autoincremental | Identificador único autoincremental. |
| `product_id` | `bigint` | NOT NULL; UNIQUE | Producto relacionado. |
| `card_name` | `varchar(255)` | NOT NULL | Nombre canónico de la carta. |
| `set_name` | `varchar(255)` | — | Nombre del set o expansión. |
| `set_code` | `varchar(100)` | — | Código identificador del set o impresión. |
| `card_number` | `varchar(100)` | — | Número de la carta dentro del set, cuando aplique. |
| `rarity` | `varchar(100)` | — | Rareza de la impresión de la carta. |
| `condition` | `varchar(50)` | Valores/nota: near_mint, lightly_played, moderately_played, heavily_played, damaged | Condición física de la carta. |
| `language` | `varchar(50)` | Valores/nota: english, spanish, japanese, etc. | Idioma de la carta. |
| `edition` | `varchar(100)` | Valores/nota: 1st_edition, unlimited, limited, promo, etc. | Edición o tiraje de la carta. |
| `foil_type` | `varchar(100)` | — | Tipo de acabado foil/holográfico, cuando aplique. |
| `external_provider` | `varchar(100)` | Valores/nota: ygoprodeck, pokemontcg, tcgdex, scryfall, etc. | Proveedor externo utilizado como fuente de datos. |
| `external_card_id` | `varchar(255)` | — | Identificador de la carta en el proveedor externo. |
| `external_printing_id` | `varchar(255)` | — | Identificador externo de una impresión específica, cuando exista. |
| `external_image_url` | `varchar(1000)` | — | URL de imagen proporcionada por una fuente externa. |
| `api_payload` | `json` | — | Respuesta o datos adicionales del proveedor conservados en JSON. |
| `last_synced_at` | `timestamp` | — | Última fecha y hora de sincronización con el proveedor externo. |
| `created_at` | `timestamp` | — | Fecha y hora de creación. |
| `updated_at` | `timestamp` | — | Fecha y hora de última actualización. |

**Regla de negocio:** `product_id` es único. Por tanto, un producto puede tener como máximo un registro de detalles de carta. La presencia de este registro permite identificar un producto como single.

### 4.7 `product_images`

Imágenes asociadas a productos, ya sean almacenadas localmente o referenciadas desde una fuente externa.

| Campo | Tipo | Restricciones | Descripción |
| --- | --- | --- | --- |
| `id` | `bigint` | PK; Autoincremental | Identificador único autoincremental. |
| `product_id` | `bigint` | NOT NULL | Producto relacionado. |
| `image_source` | `varchar(30)` | NOT NULL; Default: 'local'; Valores/nota: local, external | Origen de la imagen: local o externo. |
| `image_path` | `varchar(1000)` | — | Ruta de una imagen almacenada por la aplicación. |
| `external_image_url` | `varchar(1000)` | — | URL de imagen proporcionada por una fuente externa. |
| `alt_text` | `varchar(255)` | — | Texto alternativo de accesibilidad para la imagen. |
| `sort_order` | `int` | NOT NULL; Default: 0 | Orden de presentación de la imagen. |
| `is_primary` | `boolean` | NOT NULL; Default: false | Indica si es la imagen principal del producto. |
| `created_at` | `timestamp` | — | Fecha y hora de creación. |
| `updated_at` | `timestamp` | — | Fecha y hora de última actualización. |

### 4.8 `inventory_movements`

Bitácora de cambios de existencias: compras, ventas, devoluciones, ajustes, reservas y liberaciones.

| Campo | Tipo | Restricciones | Descripción |
| --- | --- | --- | --- |
| `id` | `bigint` | PK; Autoincremental | Identificador único autoincremental. |
| `product_id` | `bigint` | NOT NULL | Producto relacionado. |
| `created_by` | `bigint` | — | Usuario administrativo responsable del movimiento. |
| `type` | `varchar(50)` | NOT NULL; Valores/nota: purchase, sale, return, adjustment, reservation, release, cancellation | Tipo de movimiento de inventario. |
| `quantity` | `int` | NOT NULL | Cantidad afectada o solicitada. |
| `previous_stock` | `int` | — | Stock registrado antes del movimiento. |
| `new_stock` | `int` | — | Stock resultante después del movimiento. |
| `reason` | `varchar(255)` | — | Motivo o comentario del movimiento. |
| `reference_type` | `varchar(50)` | Valores/nota: order, manual, purchase, return | Tipo de entidad/proceso que originó el movimiento. |
| `reference_id` | `bigint` | — | Identificador de la entidad que originó el movimiento. |
| `created_at` | `timestamp` | — | Fecha y hora de creación. |

### 4.9 `carts`

Carritos de compra de clientes autenticados o sesiones anónimas.

| Campo | Tipo | Restricciones | Descripción |
| --- | --- | --- | --- |
| `id` | `bigint` | PK; Autoincremental | Identificador único autoincremental. |
| `customer_id` | `bigint` | — | Cliente asociado. |
| `session_id` | `varchar(255)` | — | Identificador de sesión para carritos sin cliente asociado. |
| `status` | `varchar(30)` | NOT NULL; Default: 'active'; Valores/nota: active, converted, abandoned | Estado actual del registro dentro de su flujo. |
| `created_at` | `timestamp` | — | Fecha y hora de creación. |
| `updated_at` | `timestamp` | — | Fecha y hora de última actualización. |

### 4.10 `cart_items`

Productos y cantidades contenidos en un carrito.

| Campo | Tipo | Restricciones | Descripción |
| --- | --- | --- | --- |
| `id` | `bigint` | PK; Autoincremental | Identificador único autoincremental. |
| `cart_id` | `bigint` | NOT NULL | Carrito al que pertenece el elemento. |
| `product_id` | `bigint` | NOT NULL | Producto relacionado. |
| `quantity` | `int` | NOT NULL; Default: 1 | Cantidad afectada o solicitada. |
| `unit_price` | `decimal(12,2)` | NOT NULL | Precio unitario registrado en ese momento. |
| `created_at` | `timestamp` | — | Fecha y hora de creación. |
| `updated_at` | `timestamp` | — | Fecha y hora de última actualización. |

**Restricción compuesta:** `(cart_id, product_id)` es único, por lo que un mismo producto no aparece en dos filas distintas dentro del mismo carrito; se actualiza su cantidad.

### 4.11 `orders`

Órdenes generadas por los clientes. En V1 todas se entregan mediante recolección física en tienda.

| Campo | Tipo | Restricciones | Descripción |
| --- | --- | --- | --- |
| `id` | `bigint` | PK; Autoincremental | Identificador único autoincremental. |
| `customer_id` | `bigint` | — | Cliente asociado. |
| `order_number` | `varchar(100)` | NOT NULL; UNIQUE | Folio público y único de la orden. |
| `subtotal` | `decimal(12,2)` | NOT NULL | Importe antes de descuentos. |
| `discount_total` | `decimal(12,2)` | NOT NULL; Default: 0 | Total de descuentos aplicados. |
| `total` | `decimal(12,2)` | NOT NULL | Importe final. |
| `status` | `varchar(50)` | NOT NULL; Default: 'pending'; Valores/nota: pending, confirmed, processing, ready_for_pickup, completed, cancelled, refunded | Estado actual del registro dentro de su flujo. |
| `payment_status` | `varchar(50)` | NOT NULL; Default: 'pending'; Valores/nota: pending, paid, failed, partially_refunded, refunded | Estado del pago asociado a la orden. |
| `customer_notes` | `text` | — | Notas proporcionadas por el cliente. |
| `internal_notes` | `text` | — | Notas internas del personal. |
| `placed_at` | `timestamp` | — | Fecha y hora en que se confirmó/colocó la orden. |
| `completed_at` | `timestamp` | — | Fecha y hora de finalización de la orden. |
| `cancelled_at` | `timestamp` | — | Fecha y hora de cancelación. |
| `created_at` | `timestamp` | — | Fecha y hora de creación. |
| `updated_at` | `timestamp` | — | Fecha y hora de última actualización. |

### 4.12 `order_items`

Detalle histórico de productos comprados. Conserva snapshots de nombre, SKU y precio para no alterar órdenes pasadas.

| Campo | Tipo | Restricciones | Descripción |
| --- | --- | --- | --- |
| `id` | `bigint` | PK; Autoincremental | Identificador único autoincremental. |
| `order_id` | `bigint` | NOT NULL | Orden asociada. |
| `product_id` | `bigint` | — | Producto relacionado. |
| `product_name` | `varchar(255)` | NOT NULL | Snapshot del nombre del producto al realizar la compra. |
| `sku` | `varchar(100)` | — | Snapshot del SKU del producto al realizar la compra; el esquema fuente lo permite nulo. |
| `quantity` | `int` | NOT NULL | Cantidad afectada o solicitada. |
| `unit_price` | `decimal(12,2)` | NOT NULL | Precio unitario registrado en ese momento. |
| `total` | `decimal(12,2)` | NOT NULL | Importe final. |
| `created_at` | `timestamp` | — | Fecha y hora de creación. |
| `updated_at` | `timestamp` | — | Fecha y hora de última actualización. |

**Snapshot histórico:** `product_name`, `sku` y `unit_price` conservan los datos comerciales de la compra para evitar que cambios posteriores del catálogo alteren el histórico.

### 4.13 `pickups`

Control de preparación y entrega física de una orden en el punto de recolección.

| Campo | Tipo | Restricciones | Descripción |
| --- | --- | --- | --- |
| `id` | `bigint` | PK; Autoincremental | Identificador único autoincremental. |
| `order_id` | `bigint` | NOT NULL; UNIQUE | Orden asociada. |
| `pickup_code` | `varchar(100)` | NOT NULL; UNIQUE | Código único utilizado para identificar la recolección. |
| `status` | `varchar(50)` | NOT NULL; Default: 'pending'; Valores/nota: pending, preparing, ready, picked_up, cancelled | Estado actual del registro dentro de su flujo. |
| `ready_at` | `timestamp` | — | Fecha y hora en que la orden quedó lista para recolección. |
| `picked_up_at` | `timestamp` | — | Fecha y hora en que la orden fue entregada. |
| `created_at` | `timestamp` | — | Fecha y hora de creación. |
| `updated_at` | `timestamp` | — | Fecha y hora de última actualización. |

**Regla de negocio:** `order_id` es único, de modo que una orden tiene como máximo un registro de recolección.

## 5. Relaciones

| Origen | Destino | Cardinalidad | Descripción |
| --- | --- | --- | --- |
| `customers.user_id` | `users.id` | 1:1 | Cada cliente extiende exactamente una cuenta de usuario; `user_id` es único. |
| `products.tcg_id` | `tcgs.id` | N:1 | Un TCG puede clasificar múltiples productos; el TCG del producto es opcional. |
| `products.product_type_id` | `product_types.id` | N:1 | Cada producto pertenece a un tipo de producto. |
| `card_details.product_id` | `products.id` | 1:1 opcional | Un producto puede tener como máximo un `card_details`; si existe, representa una carta single. |
| `product_images.product_id` | `products.id` | N:1 | Un producto puede tener múltiples imágenes. |
| `inventory_movements.product_id` | `products.id` | N:1 | Un producto puede registrar múltiples movimientos de inventario. |
| `inventory_movements.created_by` | `users.id` | N:1 opcional | El movimiento puede registrar qué usuario administrativo lo generó. |
| `carts.customer_id` | `customers.id` | N:1 opcional | Un cliente puede tener carritos; también se admiten carritos de sesión. |
| `cart_items.cart_id` | `carts.id` | N:1 | Cada elemento pertenece a un carrito. |
| `cart_items.product_id` | `products.id` | N:1 | Cada elemento representa un producto. |
| `orders.customer_id` | `customers.id` | N:1 opcional | Una orden puede estar asociada a un cliente. |
| `order_items.order_id` | `orders.id` | N:1 | Cada detalle pertenece a una orden. |
| `order_items.product_id` | `products.id` | N:1 opcional | Referencia al producto original; puede ser nula. |
| `pickups.order_id` | `orders.id` | 1:1 | Cada orden puede tener un único registro de recolección debido al índice único. |

## 6. Reglas de negocio principales

1. **Producto como unidad inventariable.** No existen variantes en V1. Si dos presentaciones necesitan precio, stock o control independiente, son productos distintos.
2. **Identificación de singles.** Un `product` representa una carta cuando posee un registro relacionado en `card_details`.
3. **Stock reservado.** `products.reserved_stock` permite distinguir existencias físicas de unidades temporalmente comprometidas durante una compra.
4. **Trazabilidad de inventario.** Todo cambio relevante de existencias puede registrarse en `inventory_movements`, incluyendo su origen y el usuario que lo generó.
5. **Carrito sin duplicados por producto.** La combinación `(cart_id, product_id)` es única.
6. **Histórico de órdenes.** Los datos comerciales esenciales se copian a `order_items` para conservar el estado de la compra aunque el catálogo cambie.
7. **Entrega V1.** No existen direcciones ni envíos. La entrega se modela exclusivamente mediante `pickups`.
8. **Integraciones TCG.** `card_details` conserva identificadores, imagen, payload y fecha de sincronización del proveedor externo sin convertir a la API externa en la fuente del inventario comercial.

## 7. Estados definidos

| Contexto | Campo | Valores previstos |
| --- | --- | --- |
| Usuarios | `users.role` | `admin`, `staff`, `customer` |
| Productos | `products.status` | `draft`, `active`, `inactive`, `archived` |
| Condición de cartas | `card_details.condition` | `near_mint`, `lightly_played`, `moderately_played`, `heavily_played`, `damaged` |
| Movimientos de inventario | `inventory_movements.type` | `purchase`, `sale`, `return`, `adjustment`, `reservation`, `release`, `cancellation` |
| Carritos | `carts.status` | `active`, `converted`, `abandoned` |
| Órdenes | `orders.status` | `pending`, `confirmed`, `processing`, `ready_for_pickup`, `completed`, `cancelled`, `refunded` |
| Pagos de orden | `orders.payment_status` | `pending`, `paid`, `failed`, `partially_refunded`, `refunded` |
| Recolecciones | `pickups.status` | `pending`, `preparing`, `ready`, `picked_up`, `cancelled` |

## 8. Notas para futuras versiones

Este diccionario documenta exclusivamente el esquema V1 proporcionado. Funcionalidades como envíos, direcciones, múltiples sucursales, proveedores, variantes, cupones o devoluciones avanzadas no forman parte del modelo actual y deberían documentarse cuando se incorporen formalmente al esquema.
