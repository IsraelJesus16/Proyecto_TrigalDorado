SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "-04:00";

CREATE DATABASE IF NOT EXISTS `trigal_dorado`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `trigal_dorado`;

-- =====================================================================
-- MÓDULO DE SEGURIDAD Y ACCESO
-- =====================================================================

-- Tabla: rol (Roles dinámicos del sistema)
CREATE TABLE IF NOT EXISTS `rol` (
    `id_rol`      VARCHAR(30)  NOT NULL,
    `nombre`      VARCHAR(60)  NOT NULL,
    `descripcion` TEXT         DEFAULT NULL,
    `estatus`     TINYINT(1)   NOT NULL DEFAULT 1,
    `es_sistema`  TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = rol fijo del sistema, no eliminar',
    `fecha_crea`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_rol`),
    UNIQUE KEY `uq_rol_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Roles dinámicos del sistema. Los marcados como es_sistema=1 no pueden eliminarse.';

-- Tabla: permiso (Catálogo de acciones del sistema)
CREATE TABLE IF NOT EXISTS `permiso` (
    `id_permiso`  VARCHAR(30)  NOT NULL,
    `modulo`      VARCHAR(60)  NOT NULL COMMENT 'Ej: pedidos, inventario, clientes',
    `accion`      VARCHAR(60)  NOT NULL COMMENT 'Ej: ver, crear, editar, eliminar',
    `descripcion` TEXT         DEFAULT NULL,
    PRIMARY KEY (`id_permiso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: rol_permiso (Pivote M:N entre roles y permisos)
CREATE TABLE IF NOT EXISTS `rol_permiso` (
    `id_rol`      VARCHAR(30) NOT NULL,
    `id_permiso`  VARCHAR(30) NOT NULL,
    PRIMARY KEY (`id_rol`, `id_permiso`),
    CONSTRAINT `fk_rp_rol`     FOREIGN KEY (`id_rol`)     REFERENCES `rol`(`id_rol`)     ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permiso`(`id_permiso`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: persona (Entidad base para clientes y usuarios)
CREATE TABLE IF NOT EXISTS `persona` (
    `cedula`          VARCHAR(15)  NOT NULL,
    `nombre`          VARCHAR(80)  NOT NULL,
    `apellido`        VARCHAR(80)  NOT NULL,
    `fecha_nac`       DATE         DEFAULT NULL,
    `sexo`            ENUM('M','F','Otro') DEFAULT NULL,
    `telefono`        VARCHAR(20)  DEFAULT NULL,
    `correo`          VARCHAR(120) DEFAULT NULL,
    `direccion`       TEXT         DEFAULT NULL,
    `fecha_registro`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`cedula`),
    UNIQUE KEY `uq_persona_correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: usuario (Credenciales de acceso al sistema)
CREATE TABLE IF NOT EXISTS `usuario` (
    `id_usuario`      VARCHAR(36)  NOT NULL DEFAULT (UUID()),
    `cedula`          VARCHAR(15)  NOT NULL,
    `id_rol`          VARCHAR(30)  NOT NULL,
    `username`        VARCHAR(60)  NOT NULL,
    `password_hash`   VARCHAR(255) NOT NULL,
    `estatus`         TINYINT(1)   NOT NULL DEFAULT 1,
    `ultimo_acceso`   DATETIME     DEFAULT NULL,
    `foto_perfil`     VARCHAR(255) DEFAULT NULL,
    `fecha_crea`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_usuario`),
    UNIQUE KEY `uq_usuario_cedula`   (`cedula`),
    UNIQUE KEY `uq_usuario_username` (`username`),
    CONSTRAINT `fk_usu_persona` FOREIGN KEY (`cedula`)  REFERENCES `persona`(`cedula`) ON UPDATE CASCADE,
    CONSTRAINT `fk_usu_rol`     FOREIGN KEY (`id_rol`)  REFERENCES `rol`(`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- MÓDULO DE CLIENTES
-- =====================================================================

-- Tabla: cliente (Extiende a persona con datos comerciales)
CREATE TABLE IF NOT EXISTS `cliente` (
    `cedula`            VARCHAR(15)   NOT NULL,
    `rif`               VARCHAR(20)   DEFAULT NULL,
    `razon_social`      VARCHAR(120)  DEFAULT NULL COMMENT 'Para clientes empresariales',
    `tipo_cliente`      ENUM('NATURAL','JURIDICO') NOT NULL DEFAULT 'NATURAL',
    `limite_credito`    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `condicion_pago`    ENUM('CONTADO','CREDITO_15','CREDITO_30','CREDITO_45') NOT NULL DEFAULT 'CONTADO',
    `estatus`           TINYINT(1)    NOT NULL DEFAULT 1,
    `fecha_registro`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`cedula`),
    CONSTRAINT `fk_cli_persona` FOREIGN KEY (`cedula`) REFERENCES `persona`(`cedula`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- MÓDULO DE CATÁLOGO DE PRODUCTOS
-- =====================================================================

-- Tabla: categoria_producto (Dinámica: Galletas, Ponqués, Panes + otras)
CREATE TABLE IF NOT EXISTS `categoria_producto` (
    `id_categoria`  VARCHAR(30)  NOT NULL,
    `nombre`        VARCHAR(80)  NOT NULL,
    `descripcion`   TEXT         DEFAULT NULL,
    `icono_class`   VARCHAR(60)  DEFAULT NULL COMMENT 'Clase CSS del ícono (Bootstrap Icons)',
    `color_badge`   VARCHAR(20)  DEFAULT NULL COMMENT 'Color hex para el badge de la categoría',
    `estatus`       TINYINT(1)   NOT NULL DEFAULT 1,
    `orden`         INT          NOT NULL DEFAULT 0 COMMENT 'Orden de visualización en el catálogo',
    `fecha_crea`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_categoria`),
    UNIQUE KEY `uq_cat_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: producto (Catálogo maestro)
CREATE TABLE IF NOT EXISTS `producto` (
    `id_producto`   VARCHAR(36)   NOT NULL DEFAULT (UUID()),
    `id_categoria`  VARCHAR(30)   NOT NULL,
    `nombre`        VARCHAR(120)  NOT NULL,
    `descripcion`   TEXT          DEFAULT NULL,
    `precio_venta`  DECIMAL(12,2) NOT NULL,
    `peso_neto`     DECIMAL(8,2)  DEFAULT NULL COMMENT 'Peso en gramos',
    `unidad_venta`  VARCHAR(30)   NOT NULL DEFAULT 'Paquete',
    `imagen_url`    VARCHAR(255)  DEFAULT NULL,
    `destacado`     TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Aparece en sección destacados del catálogo',
    `estatus`       TINYINT(1)    NOT NULL DEFAULT 1,
    `fecha_crea`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_producto`),
    CONSTRAINT `fk_prod_cat` FOREIGN KEY (`id_categoria`) REFERENCES `categoria_producto`(`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- MÓDULO DE INVENTARIO (DOBLE NIVEL)
-- =====================================================================

-- Tabla: materia_prima (Catálogo de insumos)
CREATE TABLE IF NOT EXISTS `materia_prima` (
    `id_materia`    VARCHAR(36)   NOT NULL DEFAULT (UUID()),
    `nombre`        VARCHAR(120)  NOT NULL,
    `descripcion`   TEXT          DEFAULT NULL,
    `unidad_medida` VARCHAR(30)   NOT NULL DEFAULT 'kg',
    `estatus`       TINYINT(1)    NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_materia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: inventario_materia_prima (Stock de insumos)
CREATE TABLE IF NOT EXISTS `inventario_materia_prima` (
    `id_inv_mp`       VARCHAR(36)   NOT NULL DEFAULT (UUID()),
    `id_materia`      VARCHAR(36)   NOT NULL,
    `cantidad_actual` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    `cantidad_minima` DECIMAL(12,3) NOT NULL DEFAULT 0.000 COMMENT 'Stock mínimo para alerta',
    `ultima_entrada`  DATETIME      DEFAULT NULL,
    `fecha_update`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_inv_mp`),
    UNIQUE KEY `uq_inv_mp_materia` (`id_materia`),
    CONSTRAINT `fk_inv_mp_materia` FOREIGN KEY (`id_materia`) REFERENCES `materia_prima`(`id_materia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: inventario_producto (Stock de producto terminado)
-- CRÍTICA: Este índice es el que permite el FOR UPDATE eficiente (Regla 3)
CREATE TABLE IF NOT EXISTS `inventario_producto` (
    `id_inv_prod`     VARCHAR(36)   NOT NULL DEFAULT (UUID()),
    `id_producto`     VARCHAR(36)   NOT NULL,
    `cantidad_actual` INT           NOT NULL DEFAULT 0,
    `cantidad_minima` INT           NOT NULL DEFAULT 0 COMMENT 'Umbral mínimo para alerta de quiebre',
    `ultima_entrada`  DATETIME      DEFAULT NULL,
    `fecha_update`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_inv_prod`),
    UNIQUE KEY `uq_inv_prod_producto` (`id_producto`),
    KEY `idx_inv_prod_cantidad` (`id_producto`, `cantidad_actual`),
    CONSTRAINT `fk_inv_prod_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto`(`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: movimiento_inventario (Bitácora de movimientos)
CREATE TABLE IF NOT EXISTS `movimiento_inventario` (
    `id_movimiento`   VARCHAR(36)   NOT NULL DEFAULT (UUID()),
    `tipo`            ENUM('ENTRADA','SALIDA','AJUSTE') NOT NULL,
    `entidad`         ENUM('PRODUCTO','MATERIA_PRIMA')  NOT NULL,
    `id_entidad`      VARCHAR(36)   NOT NULL,
    `cantidad`        DECIMAL(12,3) NOT NULL,
    `motivo`          VARCHAR(255)  DEFAULT NULL,
    `cedula_usuario`  VARCHAR(15)   DEFAULT NULL,
    `fecha`           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_movimiento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- MÓDULO DE VENTAS (NÚCLEO TPS)
-- =====================================================================

-- Tabla: pedido (Cabecera de la orden de venta)
CREATE TABLE IF NOT EXISTS `pedido` (
    `id_pedido`         VARCHAR(36)   NOT NULL DEFAULT (UUID()),
    `numero_pedido`     INT           NOT NULL AUTO_INCREMENT COMMENT 'Número consecutivo visible',
    `cedula_cliente`    VARCHAR(15)   NOT NULL,
    `cedula_vendedor`   VARCHAR(15)   DEFAULT NULL,
    `condicion_pago`    ENUM('CONTADO','CREDITO') NOT NULL DEFAULT 'CONTADO',
    `metodo_pago`       ENUM('EFECTIVO','TRANSFERENCIA','PUNTO_DE_VENTA','CREDITO') NOT NULL DEFAULT 'EFECTIVO',
    `estado`            ENUM('PENDIENTE','CONFIRMADO','PROCESANDO','DESPACHADO','ENTREGADO','CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
    `subtotal`          DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `descuento`         DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `total`             DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `observacion`       TEXT          DEFAULT NULL,
    `fecha_pedido`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_entrega`     DATE          DEFAULT NULL,
    PRIMARY KEY (`id_pedido`),
    UNIQUE KEY `uq_pedido_numero` (`numero_pedido`),
    KEY `idx_pedido_cliente`  (`cedula_cliente`),
    KEY `idx_pedido_estado`   (`estado`),
    KEY `idx_pedido_fecha`    (`fecha_pedido`),
    CONSTRAINT `fk_ped_cliente`  FOREIGN KEY (`cedula_cliente`)  REFERENCES `cliente`(`cedula`),
    CONSTRAINT `fk_ped_vendedor` FOREIGN KEY (`cedula_vendedor`) REFERENCES `persona`(`cedula`)
) ENGINE=InnoDB AUTO_INCREMENT=1001 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: detalle_pedido (Ítems de cada pedido)
CREATE TABLE IF NOT EXISTS `detalle_pedido` (
    `id_detalle`      VARCHAR(36)   NOT NULL DEFAULT (UUID()),
    `id_pedido`       VARCHAR(36)   NOT NULL,
    `id_producto`     VARCHAR(36)   NOT NULL,
    `cantidad`        INT           NOT NULL,
    `precio_unitario` DECIMAL(12,2) NOT NULL,
    `subtotal`        DECIMAL(14,2) GENERATED ALWAYS AS (`cantidad` * `precio_unitario`) STORED,
    PRIMARY KEY (`id_detalle`),
    KEY `idx_det_pedido`   (`id_pedido`),
    KEY `idx_det_producto` (`id_producto`),
    CONSTRAINT `fk_det_pedido`   FOREIGN KEY (`id_pedido`)   REFERENCES `pedido`(`id_pedido`)   ON DELETE CASCADE,
    CONSTRAINT `fk_det_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto`(`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: factura (Para validación de morosidad — Regla de Negocio 1)
CREATE TABLE IF NOT EXISTS `factura` (
    `id_factura`        VARCHAR(36)   NOT NULL DEFAULT (UUID()),
    `numero_factura`    VARCHAR(20)   NOT NULL,
    `id_pedido`         VARCHAR(36)   NOT NULL,
    `cedula_cliente`    VARCHAR(15)   NOT NULL,
    `monto_total`       DECIMAL(14,2) NOT NULL,
    `monto_pendiente`   DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `estado`            ENUM('PAGADA','PENDIENTE','VENCIDA','ANULADA') NOT NULL DEFAULT 'PENDIENTE',
    `fecha_emision`     DATE          NOT NULL,
    `fecha_vencimiento` DATE          NOT NULL,
    `fecha_pago`        DATE          DEFAULT NULL,
    PRIMARY KEY (`id_factura`),
    UNIQUE KEY `uq_factura_numero` (`numero_factura`),
    KEY `idx_fac_cliente`     (`cedula_cliente`),
    KEY `idx_fac_estado`      (`estado`),
    KEY `idx_fac_vencimiento` (`fecha_vencimiento`),
    CONSTRAINT `fk_fac_pedido`  FOREIGN KEY (`id_pedido`)      REFERENCES `pedido`(`id_pedido`),
    CONSTRAINT `fk_fac_cliente` FOREIGN KEY (`cedula_cliente`) REFERENCES `cliente`(`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: imagen (Gestión de imágenes subidas — productos, etc.)
CREATE TABLE IF NOT EXISTS `imagen` (
    `id_imagen`    VARCHAR(36)  NOT NULL DEFAULT (UUID()),
    `entidad_tipo` VARCHAR(30)  NOT NULL COMMENT 'Ej: PRODUCTO, USUARIO',
    `entidad_id`   VARCHAR(36)  NOT NULL,
    `nombre_orig`  VARCHAR(255) NOT NULL,
    `nombre_disco` VARCHAR(255) NOT NULL,
    `ruta`         VARCHAR(255) NOT NULL,
    `mime_type`    VARCHAR(80)  DEFAULT NULL,
    `tamanio`      INT          DEFAULT NULL COMMENT 'Tamaño en bytes',
    `fecha_crea`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_imagen`),
    KEY `idx_img_entidad` (`entidad_tipo`, `entidad_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- DATOS SEMILLA (INSERTS INICIALES)
-- =====================================================================

-- Roles del sistema (es_sistema = 1 → no eliminar)
INSERT INTO `rol` (`id_rol`, `nombre`, `descripcion`, `es_sistema`) VALUES
('ROL_SUPERADMIN', 'SuperAdmin',  'Acceso total al sistema. No puede eliminarse.',  1),
('ROL_VENDEDOR',   'Vendedor',    'Gestión de pedidos, clientes y catálogo.',        1),
('ROL_CLIENTE',    'Cliente',     'Acceso al panel público y seguimiento de pedidos.',1);

-- Permisos del sistema
INSERT INTO `permiso` (`id_permiso`, `modulo`, `accion`, `descripcion`) VALUES
('PERM_PEDIDO_VER',        'pedidos',    'ver',      'Ver lista de pedidos'),
('PERM_PEDIDO_CREAR',      'pedidos',    'crear',    'Crear nuevos pedidos'),
('PERM_PEDIDO_EDITAR',     'pedidos',    'editar',   'Editar estado de pedidos'),
('PERM_PEDIDO_CANCELAR',   'pedidos',    'cancelar', 'Cancelar pedidos'),
('PERM_CLIENTE_VER',       'clientes',   'ver',      'Ver lista de clientes'),
('PERM_CLIENTE_CREAR',     'clientes',   'crear',    'Crear nuevos clientes'),
('PERM_CLIENTE_EDITAR',    'clientes',   'editar',   'Editar datos de clientes'),
('PERM_CLIENTE_ELIMINAR',  'clientes',   'eliminar', 'Deshabilitar clientes'),
('PERM_PROD_VER',          'productos',  'ver',      'Ver catálogo de productos'),
('PERM_PROD_CREAR',        'productos',  'crear',    'Crear nuevos productos'),
('PERM_PROD_EDITAR',       'productos',  'editar',   'Editar productos'),
('PERM_PROD_ELIMINAR',     'productos',  'eliminar', 'Deshabilitar productos'),
('PERM_INV_VER',           'inventario', 'ver',      'Ver inventario'),
('PERM_INV_EDITAR',        'inventario', 'editar',   'Ajustar inventario'),
('PERM_ROL_GESTIONAR',     'roles',      'gestionar','Crear/editar/eliminar roles y permisos'),
('PERM_REPORTE_VER',       'reportes',   'ver',      'Ver reportes y estadísticas'),
('PERM_USUARIO_GESTIONAR', 'usuarios',   'gestionar','Gestionar cuentas de usuarios');

-- Asignar todos los permisos al SuperAdmin
INSERT INTO `rol_permiso` (`id_rol`, `id_permiso`)
SELECT 'ROL_SUPERADMIN', `id_permiso` FROM `permiso`;

-- Permisos del Vendedor
INSERT INTO `rol_permiso` (`id_rol`, `id_permiso`) VALUES
('ROL_VENDEDOR', 'PERM_PEDIDO_VER'),
('ROL_VENDEDOR', 'PERM_PEDIDO_CREAR'),
('ROL_VENDEDOR', 'PERM_PEDIDO_EDITAR'),
('ROL_VENDEDOR', 'PERM_CLIENTE_VER'),
('ROL_VENDEDOR', 'PERM_CLIENTE_CREAR'),
('ROL_VENDEDOR', 'PERM_CLIENTE_EDITAR'),
('ROL_VENDEDOR', 'PERM_PROD_VER'),
('ROL_VENDEDOR', 'PERM_INV_VER');

-- Categorías de productos (dinámicas pero con seeds por defecto)
INSERT INTO `categoria_producto` (`id_categoria`, `nombre`, `descripcion`, `icono_class`, `color_badge`, `orden`) VALUES
('CAT_GALLETAS', 'Galletas',  'Galletas tradicionales de doble horneado (bis cotus)',  'bi-cookie',        '#D4AF37', 1),
('CAT_PONQUES',  'Ponqués',   'Ponqués artesanales con técnicas de la familia Mendoza','bi-cake2',         '#C8814E', 2),
('CAT_PANES',    'Panes',     'Panes de larga duración y alta calidad industrial',     'bi-egg-fried',     '#8B6914', 3);

-- Usuario SuperAdmin por defecto (password: Admin2024!)
-- IMPORTANTE: Cambiar la contraseña tras el primer acceso
INSERT INTO `persona` (`cedula`, `nombre`, `apellido`, `correo`) VALUES
('V-00000000', 'Administrador', 'Sistema', 'admin@trigaldorado.com');

INSERT INTO `usuario` (`id_usuario`, `cedula`, `id_rol`, `username`, `password_hash`) VALUES
('USR-ADMIN-00000001', 'V-00000000', 'ROL_SUPERADMIN', 'admin',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uRpFffO7i'); -- Admin2024!

-- --------------------------------------------------------
-- Nueva tabla: materia_prima
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `materia_prima` (
  `id_materia` varchar(36) NOT NULL DEFAULT uuid(),
  `nombre` varchar(120) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `unidad_medida` varchar(30) NOT NULL DEFAULT 'kg',
  `estatus` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_materia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `materia_prima` (`id_materia`, `nombre`, `unidad_medida`) VALUES 
('MAT-001', 'Harina de Trigo',      'kg'),
('MAT-002', 'Azúcar Refinada',      'kg'),
('MAT-003', 'Mantequilla sin sal',  'kg'),
('MAT-004', 'Huevos',               'unidad'),
('MAT-005', 'Levadura Fresca',      'kg'),
('MAT-006', 'Chips de Chocolate',   'kg'),
('MAT-007', 'Esencia de Vainilla',  'litro');

-- --------------------------------------------------------
-- Nueva tabla: receta
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `receta` (
  `id_producto` varchar(36) NOT NULL,
  `id_materia` varchar(36) NOT NULL,
  `cantidad` decimal(10,4) NOT NULL,
  PRIMARY KEY (`id_producto`, `id_materia`),
  CONSTRAINT `fk_receta_prod` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE,
  CONSTRAINT `fk_receta_mat` FOREIGN KEY (`id_materia`) REFERENCES `materia_prima` (`id_materia`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `receta` (`id_producto`, `id_materia`, `cantidad`) VALUES
('PRD-P001', 'MAT-001', 0.2500),
('PRD-P001', 'MAT-002', 0.2000),
('PRD-P001', 'MAT-003', 0.1500),
('PRD-P001', 'MAT-004', 3.0000),
('PRD-P001', 'MAT-007', 0.0100),
('PROD-00000004', 'MAT-001', 0.5000),
('PROD-00000004', 'MAT-002', 0.2000),
('PROD-00000004', 'MAT-003', 0.2500),
('PROD-00000004', 'MAT-004', 2.0000),
('PROD-00000004', 'MAT-006', 0.3000),
('PROD-00000006', 'MAT-001', 0.8000),
('PROD-00000006', 'MAT-002', 0.0500),
('PROD-00000006', 'MAT-003', 0.1000),
('PROD-00000006', 'MAT-005', 0.0200),
('PRD-G001', 'MAT-001', 0.6000),
('PRD-G001', 'MAT-002', 0.2500),
('PRD-G001', 'MAT-003', 0.3000),
('PRD-G001', 'MAT-004', 3.0000),
('PRD-G002', 'MAT-001', 0.5000),
('PRD-G002', 'MAT-002', 0.2000),
('PRD-G002', 'MAT-003', 0.4000),
('PRD-G002', 'MAT-004', 1.0000),
('PRD-N001', 'MAT-001', 1.0000),
('PRD-N001', 'MAT-005', 0.0300),
('PRD-N002', 'MAT-001', 1.2000),
('PRD-N002', 'MAT-005', 0.0400),
('PRD-P002', 'MAT-001', 0.3000),
('PRD-P002', 'MAT-002', 0.2500),
('PRD-P002', 'MAT-003', 0.2000),
('PRD-P002', 'MAT-004', 4.0000),
('PROD-00000005', 'MAT-001', 0.2500),
('PROD-00000005', 'MAT-002', 0.2000),
('PROD-00000005', 'MAT-003', 0.1500),
('PROD-00000005', 'MAT-004', 3.0000);

-- Actualizar imágenes
UPDATE `producto` SET `imagen_url` = 'galletas_mantequilla.png' WHERE `id_producto` = 'PRD-G002';
UPDATE `producto` SET `imagen_url` = 'campesino_bis_cotus.png' WHERE `id_producto` = 'PRD-N002';
UPDATE `producto` SET `imagen_url` = 'ponque_naranja_semillas.png' WHERE `id_producto` = 'PRD-P002';
UPDATE `producto` SET `imagen_url` = 'galletas_chips_chocolate.png' WHERE `id_producto` = 'PROD-00000004';
UPDATE `producto` SET `imagen_url` = 'ponque_naranja.png' WHERE `id_producto` = 'PROD-00000005';
UPDATE `producto` SET `imagen_url` = 'pan_sandwich.png' WHERE `id_producto` = 'PROD-00000006';

-- Stock inicial de Materia Prima (inventario_materia_prima)
INSERT IGNORE INTO `inventario_materia_prima` (`id_inv_mp`, `id_materia`, `cantidad_actual`, `cantidad_minima`, `ultima_entrada`) VALUES
('IMP-001', 'MAT-001', 250.000, 50.000,  NOW()),
('IMP-002', 'MAT-002', 180.000, 30.000,  NOW()),
('IMP-003', 'MAT-003', 120.000, 20.000,  NOW()),
('IMP-004', 'MAT-004', 500.000, 100.000, NOW()),
('IMP-005', 'MAT-005',   8.000,   5.000, NOW()),
('IMP-006', 'MAT-006',  60.000,  10.000, NOW()),
('IMP-007', 'MAT-007',  15.000,   2.000, NOW());

COMMIT;
