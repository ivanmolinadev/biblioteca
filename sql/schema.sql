-- Esquema de Base de Datos para Sistema de Biblioteca y Préstamos
-- Fecha de creación: 2025-11-18

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS biblioteca_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioteca_db;

-- Tabla de usuarios del sistema (credenciales)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'usuario') DEFAULT 'usuario',
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_sesion TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email)
);

-- Tabla de categorías
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de autores
CREATE TABLE autores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    biografia TEXT,
    fecha_nacimiento DATE,
    nacionalidad VARCHAR(50),
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre_completo (nombre, apellidos)
);

-- Tabla de lectores (perfiles de usuarios)
CREATE TABLE lectores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    dni VARCHAR(20) UNIQUE,
    telefono VARCHAR(15),
    direccion TEXT,
    fecha_nacimiento DATE,
    limite_prestamos INT DEFAULT 3,
    multa_total DECIMAL(8,2) DEFAULT 0.00,
    bloqueado BOOLEAN DEFAULT FALSE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_dni (dni),
    INDEX idx_nombre_completo (nombre, apellidos)
);

-- Tabla de libros
CREATE TABLE libros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20) UNIQUE,
    titulo VARCHAR(200) NOT NULL,
    subtitulo VARCHAR(200),
    año_publicacion YEAR,
    editorial VARCHAR(100),
    numero_paginas INT,
    idioma VARCHAR(50) DEFAULT 'Español',
    descripcion TEXT,
    categoria_id INT,
    copias_totales INT DEFAULT 1,
    copias_disponibles INT DEFAULT 1,
    ubicacion VARCHAR(100),
    activo BOOLEAN DEFAULT TRUE,
    fecha_ingreso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    INDEX idx_titulo (titulo),
    INDEX idx_isbn (isbn),
    INDEX idx_categoria (categoria_id),
    CHECK (copias_disponibles >= 0),
    CHECK (copias_disponibles <= copias_totales)
);

-- Tabla de relación muchos a muchos: libros-autores
CREATE TABLE libro_autores (
    libro_id INT,
    autor_id INT,
    PRIMARY KEY (libro_id, autor_id),
    FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE CASCADE,
    FOREIGN KEY (autor_id) REFERENCES autores(id) ON DELETE CASCADE
);

-- Tabla de préstamos
CREATE TABLE prestamos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lector_id INT NOT NULL,
    libro_id INT NOT NULL,
    fecha_prestamo DATE NOT NULL DEFAULT (CURRENT_DATE),
    fecha_vencimiento DATE NOT NULL,
    fecha_devolucion DATE NULL,
    dias_prestamo INT DEFAULT 14,
    estado ENUM('activo', 'devuelto', 'atrasado') DEFAULT 'activo',
    observaciones TEXT,
    usuario_registro_id INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lector_id) REFERENCES lectores(id) ON DELETE RESTRICT,
    FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_registro_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_lector (lector_id),
    INDEX idx_libro (libro_id),
    INDEX idx_estado (estado),
    INDEX idx_vencimiento (fecha_vencimiento)
);

-- Tabla de devoluciones y multas
CREATE TABLE devoluciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prestamo_id INT NOT NULL UNIQUE,
    fecha_devolucion DATE NOT NULL DEFAULT (CURRENT_DATE),
    dias_atraso INT DEFAULT 0,
    multa_por_atraso DECIMAL(8,2) DEFAULT 0.00,
    tarifa_diaria DECIMAL(4,2) DEFAULT 0.25,
    multa_pagada BOOLEAN DEFAULT FALSE,
    fecha_pago DATE NULL,
    observaciones TEXT,
    usuario_registro_id INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_registro_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_prestamo (prestamo_id),
    INDEX idx_multa_pagada (multa_pagada)
);

-- Tabla de configuración del sistema
CREATE TABLE configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) UNIQUE NOT NULL,
    valor VARCHAR(255) NOT NULL,
    descripcion TEXT,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de logs de actividad
CREATE TABLE logs_actividad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    accion VARCHAR(100) NOT NULL,
    detalles TEXT,
    ip VARCHAR(45),
    user_agent TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_accion (accion),
    INDEX idx_fecha (fecha_creacion)
);

-- Insertar configuraciones por defecto
INSERT INTO configuracion (clave, valor, descripcion) VALUES
('dias_prestamo_defecto', '14', 'Días de préstamo por defecto'),
('limite_prestamos_defecto', '3', 'Límite de préstamos activos por lector'),
('tarifa_multa_diaria', '0.25', 'Tarifa diaria por atraso en devolución'),
('limite_multa_bloqueo', '10.00', 'Límite de multa para bloquear préstamos'),
('timeout_sesion', '1800', 'Timeout de sesión en segundos (30 min)');

-- Triggers para automatizar reglas de negocio

-- Trigger para actualizar estado de préstamos atrasados
DELIMITER //
CREATE TRIGGER actualizar_prestamos_atrasados
BEFORE UPDATE ON prestamos
FOR EACH ROW
BEGIN
    IF NEW.fecha_devolucion IS NULL AND CURDATE() > NEW.fecha_vencimiento THEN
        SET NEW.estado = 'atrasado';
    END IF;
END //
DELIMITER ;

-- Trigger para calcular multa al crear devolución
DELIMITER //
CREATE TRIGGER calcular_multa_devolucion
BEFORE INSERT ON devoluciones
FOR EACH ROW
BEGIN
    DECLARE fecha_venc DATE;
    DECLARE tarifa DECIMAL(4,2);
    
    -- Obtener fecha de vencimiento del préstamo
    SELECT fecha_vencimiento INTO fecha_venc
    FROM prestamos 
    WHERE id = NEW.prestamo_id;
    
    -- Obtener tarifa diaria de configuración
    SELECT CAST(valor AS DECIMAL(4,2)) INTO tarifa
    FROM configuracion 
    WHERE clave = 'tarifa_multa_diaria';
    
    -- Calcular días de atraso
    IF NEW.fecha_devolucion > fecha_venc THEN
        SET NEW.dias_atraso = DATEDIFF(NEW.fecha_devolucion, fecha_venc);
        SET NEW.multa_por_atraso = NEW.dias_atraso * tarifa;
    ELSE
        SET NEW.dias_atraso = 0;
        SET NEW.multa_por_atraso = 0.00;
    END IF;
    
    SET NEW.tarifa_diaria = tarifa;
END //
DELIMITER ;

-- Trigger para actualizar copias disponibles al prestar
DELIMITER //
CREATE TRIGGER decrementar_copias_prestamo
AFTER INSERT ON prestamos
FOR EACH ROW
BEGIN
    UPDATE libros 
    SET copias_disponibles = copias_disponibles - 1
    WHERE id = NEW.libro_id;
END //
DELIMITER ;

-- Trigger para actualizar copias disponibles al devolver
DELIMITER //
CREATE TRIGGER incrementar_copias_devolucion
AFTER INSERT ON devoluciones
FOR EACH ROW
BEGIN
    DECLARE libro_prestamo INT;
    
    -- Obtener el libro del préstamo
    SELECT libro_id INTO libro_prestamo
    FROM prestamos 
    WHERE id = NEW.prestamo_id;
    
    -- Incrementar copias disponibles
    UPDATE libros 
    SET copias_disponibles = copias_disponibles + 1
    WHERE id = libro_prestamo;
    
    -- Actualizar estado del préstamo
    UPDATE prestamos 
    SET estado = 'devuelto', fecha_devolucion = NEW.fecha_devolucion
    WHERE id = NEW.prestamo_id;
END //
DELIMITER ;

-- Trigger para actualizar multa total del lector
DELIMITER //
CREATE TRIGGER actualizar_multa_lector
AFTER INSERT ON devoluciones
FOR EACH ROW
BEGIN
    DECLARE lector_prestamo INT;
    
    -- Obtener el lector del préstamo
    SELECT lector_id INTO lector_prestamo
    FROM prestamos 
    WHERE id = NEW.prestamo_id;
    
    -- Actualizar multa total del lector
    UPDATE lectores 
    SET multa_total = (
        SELECT COALESCE(SUM(multa_por_atraso), 0)
        FROM devoluciones d
        INNER JOIN prestamos p ON d.prestamo_id = p.id
        WHERE p.lector_id = lector_prestamo AND d.multa_pagada = FALSE
    )
    WHERE id = lector_prestamo;
    
    -- Verificar si debe bloquearse por mora
    UPDATE lectores l
    SET bloqueado = (
        l.multa_total >= (
            SELECT CAST(valor AS DECIMAL(8,2))
            FROM configuracion 
            WHERE clave = 'limite_multa_bloqueo'
        )
    )
    WHERE id = lector_prestamo;
END //
DELIMITER ;